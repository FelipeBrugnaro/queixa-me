<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use Illuminate\Support\Str;

/**
 * Fonte unica de verdade dos metadados de cada pagina.
 *
 * Em vez de espalhar @section('title') e tags avulsas pelas views, cada
 * controller descreve a pagina declarativamente e o layout renderiza sempre a
 * mesma estrutura completa: title, description, canonical, robots, Open Graph,
 * Twitter Card, hreflang, paginacao e JSON-LD. Assim nenhuma pagina nova nasce
 * sem SEO, que e o modo habitual de um portal grande perder qualidade.
 */
class SeoManager
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $canonical = null;

    private ?string $image = null;

    private string $type = 'website';

    private bool $index = true;

    private bool $follow = true;

    private ?string $prev = null;

    private ?string $next = null;

    private ?string $publishedTime = null;

    private ?string $modifiedTime = null;

    /** @var array<int,array<string,mixed>> */
    private array $schemas = [];

    /** @var array<int,array{label:string,url:?string}> */
    private array $breadcrumbs = [];

    public function title(?string $title): self
    {
        $this->title = $title ? Str::limit(trim($title), 65, '') : null;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description
            ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($description)) ?? ''), 158, '')
            : null;

        return $this;
    }

    public function canonical(?string $url): self
    {
        $this->canonical = $url;

        return $this;
    }

    public function image(?string $url): self
    {
        $this->image = $url;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /** Areas privadas, administrativas e resultados filtrados. */
    public function noindex(bool $follow = true): self
    {
        $this->index = false;
        $this->follow = $follow;

        return $this;
    }

    public function pagination(?string $prev, ?string $next): self
    {
        $this->prev = $prev;
        $this->next = $next;

        return $this;
    }

    public function article(?string $publishedAt, ?string $modifiedAt = null): self
    {
        $this->type = 'article';
        $this->publishedTime = $publishedAt;
        $this->modifiedTime = $modifiedAt;

        return $this;
    }

    public function schema(array $schema): self
    {
        $this->schemas[] = $schema;

        return $this;
    }

    /** @param array<int,array{label:string,url:?string}> $items */
    public function breadcrumbs(array $items): self
    {
        $this->breadcrumbs = $items;

        return $this;
    }

    /** @return array<int,array{label:string,url:?string}> */
    public function breadcrumbItems(): array
    {
        return $this->breadcrumbs;
    }

    // -----------------------------------------------------------------
    // Leitura
    // -----------------------------------------------------------------

    public function fullTitle(): string
    {
        $brand = (string) config('queixame.brand.name');

        if ($this->title === null || $this->title === '') {
            return $brand.' — Reclamações, respostas e resoluções';
        }

        return Str::contains($this->title, $brand) ? $this->title : $this->title.' | '.$brand;
    }

    public function metaDescription(): string
    {
        return $this->description
            ?? 'Apresenta a tua reclamação, acompanha a resposta da empresa e consulta o histórico de milhares de marcas antes de comprar.';
    }

    public function canonicalUrl(): string
    {
        if ($this->canonical) {
            return $this->absolute($this->canonical);
        }

        // Por omissao o canonical ignora a query string: paginas com filtros
        // apontam para a versao limpa, evitando conteudo duplicado infinito.
        return $this->absolute(request()->url());
    }

    public function robots(): string
    {
        $directives = [
            $this->index ? 'index' : 'noindex',
            $this->follow ? 'follow' : 'nofollow',
        ];

        if ($this->index) {
            $directives[] = 'max-image-preview:large';
            $directives[] = 'max-snippet:-1';
        }

        return implode(', ', $directives);
    }

    public function imageUrl(): string
    {
        return $this->absolute($this->image ?? (string) config('queixame.seo.default_image'));
    }

    public function ogType(): string
    {
        return $this->type;
    }

    public function publishedTime(): ?string
    {
        return $this->publishedTime;
    }

    public function modifiedTime(): ?string
    {
        return $this->modifiedTime;
    }

    public function prevUrl(): ?string
    {
        return $this->prev;
    }

    public function nextUrl(): ?string
    {
        return $this->next;
    }

    /** @return array<int,array<string,mixed>> */
    public function allSchemas(): array
    {
        $schemas = $this->schemas;

        if ($this->breadcrumbs !== []) {
            $schemas[] = SchemaBuilder::breadcrumbs($this->breadcrumbs);
        }

        return $schemas;
    }

    private function absolute(string $url): string
    {
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return rtrim((string) config('queixame.brand.canonical_url'), '/').'/'.ltrim($url, '/');
    }
}
