<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintReply;
use App\Domain\Content\Models\Post;
use App\Domain\Messaging\Models\Message;
use App\Domain\Seo\Services\SeoManager;
use App\Http\ViewComposers\PanelNavigationComposer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Um SeoManager por pedido: os controllers descrevem a pagina e o
        // layout le a mesma instancia no momento de renderizar.
        $this->app->scoped(SeoManager::class);
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureMorphMap();
        $this->configureFactories();
        $this->configureUrls();

        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination-simple');

        View::composer('layouts.panel', PanelNavigationComposer::class);
    }

    private function configureModels(): void
    {
        // Falhar alto em desenvolvimento: atributos em falta e lazy loading
        // acidental sao a principal causa de N+1 em paginas publicas.
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false);
    }

    /**
     * Mapa morfico explicito: as colunas *_type guardam alias curtos e
     * estaveis em vez de nomes de classe. Renomear ou mover uma classe deixa
     * de partir dados ja gravados, o que e critico num esquema modular.
     */
    private function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'complaint' => Complaint::class,
            'complaint_reply' => ComplaintReply::class,
            'company' => Company::class,
            'message' => Message::class,
            'post' => Post::class,
            'user' => User::class,
        ]);
    }

    /**
     * Os modelos vivem em App\Domain\<Contexto>\Models, pelo que a convencao
     * do Laravel (App\Models\X -> Database\Factories\XFactory) nao se aplica.
     */
    private function configureFactories(): void
    {
        Factory::guessFactoryNamesUsing(
            static fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Factory::guessModelNamesUsing(static function (Factory $factory): string {
            $name = str_replace('Factory', '', class_basename($factory));

            foreach ([
                'App\\Domain\\Accounts\\Models\\',
                'App\\Domain\\Companies\\Models\\',
                'App\\Domain\\Complaints\\Models\\',
                'App\\Domain\\Content\\Models\\',
                'App\\Domain\\Messaging\\Models\\',
                'App\\Domain\\Moderation\\Models\\',
                'App\\Domain\\Ratings\\Models\\',
            ] as $namespace) {
                if (class_exists($namespace.$name)) {
                    return $namespace.$name;
                }
            }

            return $name;
        });
    }

    private function configureUrls(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
