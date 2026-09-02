<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Services\ComplaintWorkflow;
use App\Domain\Complaints\Services\SensitiveDataScanner;
use App\Domain\Moderation\Enums\RejectionReason;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Fila de moderação.
 *
 * A ordenação é deliberada: prioridade (elevada automaticamente quando o
 * scanner deteta dados sensíveis) e depois antiguidade. Uma reclamação com um
 * IBAN à espera de revisão é mais urgente do que uma reclamação banal
 * submetida uma hora antes, porque enquanto espera não está a expor nada —
 * mas se for aprovada à pressa, expõe.
 */
class ModerationController extends Controller
{
    public function __construct(
        private readonly ComplaintWorkflow $workflow,
        private readonly SensitiveDataScanner $scanner,
    ) {}

    public function index(Request $request): View
    {
        $this->seo()->title('Fila de moderação');

        $queue = Complaint::pendingModeration()
            ->with(['company:id,name,slug,status', 'user:id,uuid,public_name,status,created_at'])
            ->when($request->query('q'), fn (Builder $q, string $term) => $q->search($term))
            ->when($request->boolean('sensiveis'), fn (Builder $q) => $q->whereNotNull('sensitive_flags'))
            ->orderByDesc('priority')
            ->orderBy('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.moderation.index', [
            'queue' => $queue,
            'counters' => [
                'pending' => Complaint::pendingModeration()->count(),
                'sensitive' => Complaint::pendingModeration()->whereNotNull('sensitive_flags')->count(),
                'oldest_hours' => (int) (Complaint::pendingModeration()->min('submitted_at')
                    ? now()->diffInHours(Complaint::pendingModeration()->min('submitted_at'))
                    : 0),
            ],
        ]);
    }

    public function show(Request $request, Complaint $complaint): View
    {
        $complaint->load([
            'company', 'user', 'attachments', 'contactDetails',
            'events' => fn ($q) => $q->latest(),
            'moderationReviews.moderator:id,name',
        ]);

        $this->seo()->title('Moderação — '.$complaint->reference);

        return view('admin.moderation.show', [
            'complaint' => $complaint,
            'findings' => $this->scanner->scan(
                $complaint->title,
                $complaint->description,
                (string) $complaint->desired_resolution,
                (string) $complaint->extra_info,
            ),
            'reasons' => RejectionReason::options(),
            // Contexto do autor: um padrão de dezenas de reclamações contra a
            // mesma empresa em poucos dias é sinal de campanha, não de consumo.
            'authorContext' => [
                'total' => Complaint::where('user_id', $complaint->user_id)->count(),
                'last_30_days' => Complaint::where('user_id', $complaint->user_id)
                    ->where('created_at', '>=', now()->subDays(30))->count(),
                'same_company' => Complaint::where('user_id', $complaint->user_id)
                    ->where('company_id', $complaint->company_id)->count(),
                'rejected' => Complaint::where('user_id', $complaint->user_id)
                    ->where('moderation_status', 'rejected')->count(),
            ],
        ]);
    }

    public function startReview(Request $request, Complaint $complaint): RedirectResponse
    {
        return $this->run(fn () => $this->workflow->startReview($complaint, $request->user()));
    }

    public function approve(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        return $this->run(
            fn () => $this->workflow->approve($complaint, $request->user(), $data['notes'] ?? null),
            route('admin.moderation.index'),
            'Reclamação aprovada e publicada.',
        );
    }

    public function requestChanges(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $this->validateDecision($request);

        return $this->run(
            fn () => $this->workflow->requestChanges(
                $complaint,
                $request->user(),
                RejectionReason::from($data['reason']),
                $data['message'] ?? null,
            ),
            route('admin.moderation.index'),
            'Pedido de alterações enviado ao autor.',
        );
    }

    public function reject(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $this->validateDecision($request);

        return $this->run(
            fn () => $this->workflow->reject(
                $complaint,
                $request->user(),
                RejectionReason::from($data['reason']),
                $data['message'] ?? null,
            ),
            route('admin.moderation.index'),
            'Reclamação rejeitada.',
        );
    }

    public function remove(Request $request, Complaint $complaint): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [], ['reason' => 'motivo']);

        return $this->run(
            fn () => $this->workflow->remove($complaint, $request->user(), $data['reason']),
            route('admin.moderation.index'),
            'Reclamação removida do portal.',
        );
    }

    /** @return array<string,string> */
    private function validateDecision(Request $request): array
    {
        return $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', array_column(RejectionReason::cases(), 'value'))],
            // Mensagem obrigatória em "Outro motivo": um código genérico sem
            // explicação deixa o autor sem saber o que corrigir.
            'message' => ['nullable', 'required_if:reason,other', 'string', 'max:2000'],
        ], [
            'message.required_if' => 'Explica o motivo ao autor quando escolhes "Outro motivo".',
        ], ['reason' => 'motivo', 'message' => 'mensagem']);
    }

    private function run(callable $action, ?string $redirect = null, ?string $message = null): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $response = $redirect ? redirect($redirect) : back();

        return $message ? $response->with('success', $message) : $response;
    }
}
