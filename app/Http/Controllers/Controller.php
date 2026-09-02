<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Seo\Services\SeoManager;

abstract class Controller
{
    /** Metadados da pagina actual. Ver App\Domain\Seo\Services\SeoManager. */
    protected function seo(): SeoManager
    {
        return app(SeoManager::class);
    }

    /**
     * @param  array<int,array{label:string,url:?string}>  $items
     */
    protected function breadcrumbs(array $items): SeoManager
    {
        return $this->seo()->breadcrumbs(array_merge(
            [['label' => 'Início', 'url' => route('home')]],
            $items,
        ));
    }
}
