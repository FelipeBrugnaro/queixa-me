<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Content\Models\Post;

/**
 * Dados estruturados schema.org.
 *
 * Nota de conformidade: NAO usamos Review/AggregateRating nas paginas de
 * empresa. O Google restringe rich snippets de avaliacoes geradas pelo proprio
 * site sobre entidades terceiras, e marcar reclamacoes como "reviews" seria
 * uma representacao incorreta - uma reclamacao nao e uma avaliacao voluntaria
 * de produto. Usamos Organization + DiscussionForumPosting, que descrevem
 * honestamente o conteudo e nao arriscam accao manual.
 */
class SchemaBuilder
{
    public static function organization(): array
    {
        $base = rtrim((string) config('queixame.brand.canonical_url'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $base.'/#organization',
            'name' => config('queixame.brand.name'),
            'url' => $base.'/',
            'description' => 'Portal independente de reclamações de consumo, respostas de empresas e acompanhamento de resoluções.',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'email' => config('queixame.brand.contact_email'),
                'contactType' => 'customer support',
                'availableLanguage' => ['pt'],
            ],
        ];
    }

    public static function website(): array
    {
        $base = rtrim((string) config('queixame.brand.canonical_url'), '/');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $base.'/#website',
            'name' => config('queixame.brand.name'),
            'url' => $base.'/',
            'inLanguage' => 'pt-PT',
            'publisher' => ['@id' => $base.'/#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $base.'/pesquisar?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @param array<int,array{label:string,url:?string}> $items */
    public static function breadcrumbs(array $items): array
    {
        $elements = [];
        $position = 1;

        foreach ($items as $item) {
            $element = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $item['label'],
            ];

            if (! empty($item['url'])) {
                $element['item'] = $item['url'];
            }

            $elements[] = $element;
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    public static function company(Company $company): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $company->name,
            'url' => $company->url(),
        ];

        if ($company->legal_name) {
            $schema['legalName'] = $company->legal_name;
        }

        if ($company->website) {
            $schema['sameAs'] = [$company->website];
        }

        if ($company->logoUrl()) {
            $schema['logo'] = $company->logoUrl();
        }

        if ($company->description) {
            $schema['description'] = $company->description;
        }

        if ($company->locality || $company->district) {
            $schema['address'] = array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $company->locality,
                'addressRegion' => $company->district,
                'postalCode' => $company->postal_code,
                'addressCountry' => $company->country,
            ]);
        }

        return $schema;
    }

    public static function complaint(Complaint $complaint): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'DiscussionForumPosting',
            'headline' => $complaint->title,
            'articleBody' => $complaint->excerpt(500),
            'url' => $complaint->url(),
            'datePublished' => $complaint->published_at?->toIso8601String(),
            'dateModified' => $complaint->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $complaint->authorDisplayName(),
            ],
            'inLanguage' => 'pt-PT',
            'isPartOf' => ['@id' => rtrim((string) config('queixame.brand.canonical_url'), '/').'/#website'],
        ];

        if ($complaint->replies_count > 0) {
            $schema['commentCount'] = $complaint->replies_count;
        }

        if ($complaint->company) {
            $schema['about'] = [
                '@type' => 'Organization',
                'name' => $complaint->company->name,
                'url' => $complaint->company->url(),
            ];
        }

        return $schema;
    }

    public static function post(Post $post): array
    {
        $base = rtrim((string) config('queixame.brand.canonical_url'), '/');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'image' => $post->coverUrl(),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->publicDisplayName() ?? config('queixame.brand.name'),
            ],
            'publisher' => ['@id' => $base.'/#organization'],
            'mainEntityOfPage' => $post->url(),
            'inLanguage' => 'pt-PT',
        ]);
    }

    /** @param iterable<object{question:string,answer:string}> $items */
    public static function faq(iterable $items): array
    {
        $entities = [];

        foreach ($items as $item) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $item->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($item->answer),
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /** @param array<int,array{position:int,name:string,url:string}> $items */
    public static function itemList(string $name, array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $name,
            'itemListElement' => array_map(static fn (array $item) => [
                '@type' => 'ListItem',
                'position' => $item['position'],
                'name' => $item['name'],
                'url' => $item['url'],
            ], $items),
        ];
    }
}
