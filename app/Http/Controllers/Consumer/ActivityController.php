<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consumer;

use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Enums\ModerationStatus;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintEvent;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $this->seo()->title('A minha área');

        $complaints = Complaint::where('user_id', $user->id)
            ->with('company:id,name,slug,logo_path,status')
            ->latest()
            ->limit(5)
            ->get();

        return view('consumer.dashboard', [
            'user' => $user,
            'complaints' => $complaints,
            'counters' => $this->counters($user->id),
            'drafts' => Complaint::where('user_id', $user->id)
                ->where('moderation_status', ModerationStatus::Draft->value)
                ->with('company:id,name,slug')
                ->latest()
                ->get(),
            'actionRequired' => Complaint::where('user_id', $user->id)
                ->where(fn ($q) => $q
                    ->where('moderation_status', ModerationStatus::ChangesRequested->value)
                    ->orWhere(fn ($sub) => $sub
                        ->whereNotNull('resolution_proposed_at')
                        ->whereNull('resolved_at')
                        ->whereNotIn('stage', [ComplaintStage::Closed->value, ComplaintStage::Unresolved->value])))
                ->with('company:id,name,slug,logo_path,status')
                ->get(),
            'unreadMessages' => $user->unreadMessagesCount(),
        ]);
    }

    /**
     * Linha temporal unificada.
     *
     * Reúne todos os eventos das reclamações do utilizador num único fluxo
     * cronológico — é a vista que responde à pergunta "o que aconteceu desde
     * a última vez que aqui estive?".
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $this->seo()->title('A minha atividade');

        $events = ComplaintEvent::query()
            ->whereHas('complaint', fn ($q) => $q->where('user_id', $user->id))
            ->with(['complaint:id,uuid,title,slug,reference,company_id,moderation_status', 'complaint.company:id,name,slug,logo_path,status'])
            ->latest('created_at')
            ->paginate(30);

        return view('consumer.activity', [
            'events' => $events,
        ]);
    }

    /** @return array<string,int> */
    private function counters(int $userId): array
    {
        $base = Complaint::where('user_id', $userId);

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->pendingModeration()->count(),
            'published' => (clone $base)->where('moderation_status', ModerationStatus::Approved->value)->count(),
            'replied' => (clone $base)->whereNotNull('first_response_at')->count(),
            'resolved' => (clone $base)->where('stage', ComplaintStage::Resolved->value)->count(),
            'rejected' => (clone $base)->where('moderation_status', ModerationStatus::Rejected->value)->count(),
        ];
    }
}
