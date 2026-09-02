<?php

declare(strict_types=1);

namespace App\Http\Controllers\Business;

use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Services\ComplaintWorkflow;
use App\Domain\Messaging\Services\ConversationService;
use App\Domain\Moderation\Enums\ReportReason;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BusinessComplaintController extends Controller
{
    public function __construct(
        private readonly ComplaintWorkflow $workflow,
        private readonly ConversationService $conversations,
    ) {}

    public function index(Request $request): View
    {
        $company = $this->company($request);
        $filter = (string) $request->query('filtro', 'todas');

        $this->seo()->title('Reclamações recebidas');

        $complaints = Complaint::published()
            ->where('company_id', $company->id)
            ->with('category:id,name')
            ->when($filter === 'por-responder', fn (Builder $q) => $q->whereNull('first_response_at'))
            ->when($filter === 'atrasadas', fn (Builder $q) => $q
                ->whereNull('first_response_at')
                ->where('published_at', '<', now()->subDays((int) config('queixame.complaints.response_sla_days'))))
            ->when($filter === 'em-curso', fn (Builder $q) => $q->whereIn('stage', [
                ComplaintStage::CompanyReplied->value,
                ComplaintStage::InFollowUp->value,
            ]))
            ->when($filter === 'resolvidas', fn (Builder $q) => $q->where('stage', ComplaintStage::Resolved->value))
            ->when($request->query('q'), fn (Builder $q, string $term) => $q->search($term))
            // Por responder primeiro, e dentro dessas as mais antigas: a fila
            // de trabalho da empresa deve refletir urgência, não recência.
            ->orderByRaw('CASE WHEN first_response_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('published_at')
            ->paginate(20)
            ->withQueryString();

        return view('business.complaints.index', [
            'company' => $company,
            'complaints' => $complaints,
            'filter' => $filter,
            'slaDays' => (int) config('queixame.complaints.response_sla_days'),
        ]);
    }

    public function show(Request $request, Complaint $complaint): View
    {
        $company = $this->company($request);
        $this->authorizeCompany($complaint, $company);

        $complaint->load([
            'category:id,name',
            'attachments',
            'replies.company:id,name,slug,logo_path',
            'publicEvents',
            'conversation',
            // Os dados de contacto só são carregados quando existe
            // consentimento explícito para a transmissão.
            'contactDetails' => fn ($q) => $q->when(! $complaint->share_contact_with_company, fn ($sub) => $sub->whereRaw('1 = 0')),
        ]);

        $this->seo()->title('Reclamação '.$complaint->reference);

        return view('business.complaints.show', [
            'company' => $company,
            'complaint' => $complaint,
            'canSeeContact' => $complaint->share_contact_with_company && $complaint->contactDetails && ! $complaint->contactDetails->isPurged(),
            'reportReasons' => ReportReason::options(),
        ]);
    }

    public function reply(Request $request, Complaint $complaint): RedirectResponse
    {
        $company = $this->company($request);
        $this->authorizeCompany($complaint, $company);
        $this->authorizePermission($request, $company, 'complaints.reply');

        $data = $request->validate([
            'body' => [
                'required', 'string',
                'min:'.config('queixame.complaints.reply_min'),
                'max:'.config('queixame.complaints.reply_max'),
            ],
            'display_name' => ['nullable', 'string', 'max:120'],
        ], [], ['body' => 'resposta']);

        try {
            $this->workflow->addCompanyReply(
                $complaint,
                $company,
                $request->user(),
                $data['body'],
                isResolutionProposal: false,
                displayName: $data['display_name'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Resposta publicada na reclamação.');
    }

    /**
     * Propor uma solução abre a janela em que o consumidor confirma (ou não)
     * a resolução. A empresa nunca marca a reclamação como resolvida.
     */
    public function proposeResolution(Request $request, Complaint $complaint): RedirectResponse
    {
        $company = $this->company($request);
        $this->authorizeCompany($complaint, $company);
        $this->authorizePermission($request, $company, 'complaints.reply');

        $data = $request->validate([
            'body' => ['required', 'string', 'min:20', 'max:'.config('queixame.complaints.reply_max')],
        ], [], ['body' => 'proposta de solução']);

        try {
            $this->workflow->addCompanyReply(
                $complaint,
                $company,
                $request->user(),
                $data['body'],
                isResolutionProposal: true,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'success',
            'Proposta de solução publicada. O consumidor tem '.config('queixame.complaints.resolution_confirmation_days').' dias para confirmar.'
        );
    }

    public function startConversation(Request $request, Complaint $complaint): RedirectResponse
    {
        $company = $this->company($request);
        $this->authorizeCompany($complaint, $company);
        $this->authorizePermission($request, $company, 'messages.send');

        $data = $request->validate([
            'body' => ['required', 'string', 'min:20', 'max:4000'],
        ], [], ['body' => 'mensagem']);

        try {
            $conversation = $this->conversations->openFromComplaint(
                $complaint, $company, $request->user(), $data['body']
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('business.messages.show', $conversation->uuid)
            ->with('success', 'Mensagem privada enviada ao consumidor.');
    }

    public function report(Request $request, Complaint $complaint): RedirectResponse
    {
        $company = $this->company($request);
        $this->authorizeCompany($complaint, $company);

        $data = $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', ReportReason::values())],
            'details' => ['required', 'string', 'min:30', 'max:2000'],
        ], [], ['reason' => 'motivo', 'details' => 'fundamentação']);

        $complaint->reports()->create([
            'reporter_id' => $request->user()->id,
            'reporter_company_id' => $company->id,
            'reason' => $data['reason'],
            'details' => $data['details'],
            'status' => ReportStatus::Open,
            'reporter_ip' => $request->ip(),
        ]);

        $complaint->increment('reports_count');

        return back()->with('success', 'Denúncia registada. A nossa equipa vai analisá-la.');
    }

    private function company(Request $request): Company
    {
        return $request->attributes->get('company');
    }

    private function authorizeCompany(Complaint $complaint, Company $company): void
    {
        abort_unless($complaint->company_id === $company->id, 404);
        abort_unless($complaint->isPublished(), 404);
    }

    private function authorizePermission(Request $request, Company $company, string $permission): void
    {
        abort_unless($request->user()->canForCompany($company, $permission), 403);
    }
}
