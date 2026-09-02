<?php

declare(strict_types=1);

namespace App\Http\ViewComposers;

use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Navegação das áreas autenticadas.
 *
 * Está num composer, e não repetida em cada view, porque os contadores de
 * pendências (mensagens por ler, reclamações por responder, fila de
 * moderação) têm de aparecer em todos os ecrãs do painel: duplicá-los em
 * vinte templates garantia que alguns ficariam desatualizados.
 */
class PanelNavigationComposer
{
    public function __construct(private readonly Request $request) {}

    public function compose(View $view): void
    {
        $user = $this->request->user();

        if ($user === null) {
            $view->with(['panelTitle' => 'Área reservada', 'panelNav' => []]);

            return;
        }

        if ($this->request->routeIs('admin.*')) {
            $view->with($this->adminNav());

            return;
        }

        if ($this->request->routeIs('business.*')) {
            $view->with($this->businessNav());

            return;
        }

        $view->with($this->consumerNav($user));
    }

    /** @return array<string,mixed> */
    private function consumerNav($user): array
    {
        return [
            'panelTitle' => 'A minha área',
            'panelNav' => [
                ['label' => 'Resumo', 'route' => 'consumer.dashboard'],
                ['label' => 'As minhas reclamações', 'route' => 'consumer.complaints.index'],
                ['label' => 'Mensagens', 'route' => 'consumer.messages.index', 'badge' => $user->unreadMessagesCount() ?: null],
                ['label' => 'Atividade', 'route' => 'consumer.activity'],
                ['label' => 'Perfil', 'route' => 'consumer.profile.edit'],
                ['label' => 'Privacidade e dados', 'route' => 'consumer.privacy'],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function businessNav(): array
    {
        $company = $this->request->attributes->get('company');

        if (! $company instanceof Company) {
            return ['panelTitle' => 'Área da empresa', 'panelNav' => []];
        }

        $awaiting = Complaint::published()
            ->where('company_id', $company->id)
            ->whereNull('first_response_at')
            ->count();

        $unread = (int) Conversation::where('company_id', $company->id)->sum('company_unread_count');

        return [
            'panelTitle' => $company->name,
            'panelNav' => [
                ['label' => 'Resumo', 'route' => 'business.dashboard'],
                ['label' => 'Reclamações', 'route' => 'business.complaints.index', 'badge' => $awaiting ?: null],
                ['label' => 'Mensagens', 'route' => 'business.messages.index', 'badge' => $unread ?: null],
                ['label' => 'Estatísticas', 'route' => 'business.stats'],
                ['label' => 'Perfil da empresa', 'route' => 'business.profile.edit'],
                ['label' => 'Equipa', 'route' => 'business.team.index'],
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function adminNav(): array
    {
        return [
            'panelTitle' => 'Administração',
            'panelNav' => [
                ['label' => 'Resumo', 'route' => 'admin.dashboard'],
                ['label' => 'Moderação', 'route' => 'admin.moderation.index', 'badge' => Complaint::pendingModeration()->count() ?: null],
                ['label' => 'Denúncias', 'route' => 'admin.reports.index', 'badge' => Report::where('status', ReportStatus::Open->value)->count() ?: null],
                ['label' => 'Empresas', 'route' => 'admin.companies.index'],
                ['label' => 'Utilizadores', 'route' => 'admin.users.index'],
            ],
        ];
    }
}
