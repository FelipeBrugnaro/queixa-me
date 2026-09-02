<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consumer;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Services\ComplaintWorkflow;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MyComplaintController extends Controller
{
    public function __construct(private readonly ComplaintWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $this->seo()->title('As minhas reclamações');

        $complaints = Complaint::where('user_id', $request->user()->id)
            ->with('company:id,name,slug,logo_path,status')
            ->when($request->query('estado'), fn ($q, $stage) => $q->where('stage', $stage))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('consumer.complaints.index', ['complaints' => $complaints]);
    }

    public function show(Request $request, Complaint $complaint): View
    {
        $this->authorizeOwner($request, $complaint);

        $complaint->load([
            'company:id,name,slug,logo_path,status',
            'category:id,name',
            'attachments',
            'replies.company:id,name,slug,logo_path',
            'events' => fn ($q) => $q->latest('created_at'),
            'moderationReviews' => fn ($q) => $q->latest(),
            'contactDetails',
            'conversation',
        ]);

        $this->seo()->title($complaint->title ?: 'Reclamação '.$complaint->reference);

        return view('consumer.complaints.show', [
            'complaint' => $complaint,
            'latestReview' => $complaint->moderationReviews->first(),
        ]);
    }

    public function reply(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorizeOwner($request, $complaint);

        $data = $request->validate([
            'body' => [
                'required', 'string',
                'min:'.config('queixame.complaints.reply_min'),
                'max:'.config('queixame.complaints.reply_max'),
            ],
        ], [], ['body' => 'resposta']);

        $this->workflow->addConsumerReply($complaint, $request->user(), $data['body']);

        return back()->with('success', 'Resposta publicada.');
    }

    public function confirmResolution(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorizeOwner($request, $complaint);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'would_recommend' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], ['rating' => 'avaliação']);

        try {
            $this->workflow->confirmResolution(
                $complaint,
                $request->user(),
                (int) $data['rating'],
                $request->has('would_recommend') ? $request->boolean('would_recommend') : null,
                $data['comment'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Obrigado. A tua confirmação conta para o índice de satisfação da empresa.');
    }

    public function markUnresolved(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorizeOwner($request, $complaint);

        $data = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->markUnresolved(
            $complaint,
            $request->user(),
            isset($data['rating']) ? (int) $data['rating'] : null,
            $data['comment'] ?? null,
        );

        return back()->with('success', 'Registámos que o problema não ficou resolvido.');
    }

    public function reopen(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorizeOwner($request, $complaint);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
        ], [], ['reason' => 'motivo']);

        $this->workflow->reopen($complaint, $request->user(), $data['reason']);

        return back()->with('success', 'Reclamação reaberta. A empresa foi notificada.');
    }

    private function authorizeOwner(Request $request, Complaint $complaint): void
    {
        abort_unless($complaint->user_id === $request->user()?->id, 403);
    }
}
