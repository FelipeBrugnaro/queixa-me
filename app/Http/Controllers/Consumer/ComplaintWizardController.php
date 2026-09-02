<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consumer;

use App\Domain\Accounts\Enums\ConsentType;
use App\Domain\Accounts\Models\User;
use App\Domain\Accounts\Services\ConsentRecorder;
use App\Domain\Companies\Actions\ResolveOrCreateCompany;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Complaints\Enums\ActorType;
use App\Domain\Complaints\Enums\ComplaintEventType;
use App\Domain\Complaints\Enums\ComplaintKind;
use App\Domain\Complaints\Enums\ModerationStatus;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintAttachment;
use App\Domain\Complaints\Services\AttachmentUploader;
use App\Domain\Complaints\Services\ComplaintTimeline;
use App\Domain\Complaints\Services\ComplaintWorkflow;
use App\Domain\Complaints\Services\SensitiveDataScanner;
use App\Domain\Shared\Support\Districts;
use App\Http\Controllers\Controller;
use App\Http\Requests\Complaints\StoreComplaintContactRequest;
use App\Http\Requests\Complaints\StoreComplaintDescriptionRequest;
use App\Http\Requests\Complaints\StoreComplaintDetailsRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Assistente de nova reclamação.
 *
 * DECISÃO DE PRODUTO: o rascunho é gravado no servidor a cada passo, em vez
 * de viver na sessão ou no browser. Escrever uma reclamação com números de
 * encomenda e datas leva tempo real; perder tudo por um telemóvel que
 * bloqueou é a forma mais eficaz de perder o utilizador. Assim, cada passo
 * tem URL próprio, o utilizador pode sair e voltar, e o rascunho aparece
 * na sua área com um aviso de "por concluir".
 */
class ComplaintWizardController extends Controller
{
    private const STEPS = ['company', 'description', 'details', 'contact', 'review'];

    public function __construct(
        private readonly ComplaintWorkflow $workflow,
        private readonly ComplaintTimeline $timeline,
        private readonly SensitiveDataScanner $scanner,
    ) {}

    // ---------------------------------------------------------------
    // Passo 1 — Empresa
    // ---------------------------------------------------------------

    public function start(Request $request): View
    {
        $this->seo()
            ->title('Fazer uma reclamação')
            ->description('Descreve o teu problema em quatro passos. A reclamação é analisada pela nossa equipa e a empresa é notificada para poder responder.')
            ->canonical(route('complaints.create'));

        $this->breadcrumbs([['label' => 'Fazer uma reclamação', 'url' => route('complaints.create')]]);

        $draft = $request->user()
            ? Complaint::where('user_id', $request->user()->id)
                ->where('moderation_status', ModerationStatus::Draft->value)
                ->latest()
                ->first()
            : null;

        return view('consumer.wizard.company', [
            'step' => 1,
            'steps' => self::STEPS,
            'draft' => $draft,
            'prefill' => (string) $request->query('empresa', ''),
        ]);
    }

    public function storeCompany(Request $request, ResolveOrCreateCompany $resolver): RedirectResponse
    {
        // O website da empresa deixou de ser pedido: quem reclama raramente
        // o sabe de cor, e a moderação identifica a entidade melhor do que
        // um campo opcional preenchido à pressa.
        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'company_name' => ['required_without:company_id', 'nullable', 'string', 'min:2', 'max:160'],
            'kind' => ['required', 'in:consumer,employee'],
        ], [
            'company_name.required_without' => 'Indica a empresa sobre a qual queres reclamar.',
        ]);

        $company = $resolver->handle(
            isset($data['company_id']) ? (int) $data['company_id'] : null,
            $data['company_name'] ?? null,
            null,
            $request->user(),
        );

        abort_if($company === null, 422);

        $complaint = DB::transaction(function () use ($request, $company, $data): Complaint {
            $complaint = Complaint::create([
                'user_id' => $request->user()->id,
                // A reclamação liga-se sempre à ficha, mesmo quando esta ainda
                // está por validar. O nome em texto livre só é guardado nesse
                // caso, para a moderação poder confirmar ou fundir a ficha.
                'company_id' => $company->id,
                'company_name_raw' => $company->isPublic() ? null : $company->name,
                'category_id' => $company->category_id,
                'kind' => ComplaintKind::from($data['kind']),
                'title' => '',
                'description' => '',
                'country' => $request->user()->country ?? 'PT',
                'district' => $request->user()->district,
                'locality' => $request->user()->locality,
                'submitted_ip' => $request->ip(),
            ]);

            $this->timeline->record(
                $complaint,
                ComplaintEventType::Created,
                ActorType::Consumer,
                actorUser: $request->user(),
                isPublic: false,
            );

            return $complaint;
        });

        return redirect()->route('complaints.wizard.description', $complaint->uuid);
    }

    // ---------------------------------------------------------------
    // Passo 2 — Descrição
    // ---------------------------------------------------------------

    public function description(Request $request, Complaint $complaint): View
    {
        $this->authorizeDraft($request, $complaint);

        return view('consumer.wizard.description', [
            'step' => 2,
            'steps' => self::STEPS,
            'complaint' => $complaint->load('company'),
            'min' => (int) config('queixame.complaints.description_min'),
            'max' => (int) config('queixame.complaints.description_max'),
        ]);
    }

    public function storeDescription(StoreComplaintDescriptionRequest $request, Complaint $complaint): RedirectResponse
    {
        $this->authorizeDraft($request, $complaint);

        $complaint->update($request->validated());

        return redirect()->route('complaints.wizard.details', $complaint->uuid);
    }

    // ---------------------------------------------------------------
    // Passo 3 — Detalhes e anexos
    // ---------------------------------------------------------------

    public function details(Request $request, Complaint $complaint): View
    {
        $this->authorizeDraft($request, $complaint);

        return view('consumer.wizard.details', [
            'step' => 3,
            'steps' => self::STEPS,
            'complaint' => $complaint->load(['company', 'attachments']),
            'categories' => CompanyCategory::orderBy('name')->get(['id', 'name']),
            'attachmentConfig' => (array) config('queixame.complaints.attachments'),
            'warnings' => $this->scanner->warningsFor(
                $this->scanner->scan($complaint->description)
            ),
        ]);
    }

    public function storeDetails(
        StoreComplaintDetailsRequest $request,
        Complaint $complaint,
        AttachmentUploader $uploader,
    ): RedirectResponse {
        $this->authorizeDraft($request, $complaint);

        $complaint->update($request->safe()->except('attachments'));

        $errors = [];

        foreach ((array) $request->file('attachments', []) as $file) {
            try {
                $uploader->store($complaint, $file, $request->user());
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            return back()->withErrors(['attachments' => $errors])->withInput();
        }

        return redirect()->route('complaints.wizard.contact', $complaint->uuid);
    }

    public function destroyAttachment(
        Request $request,
        Complaint $complaint,
        ComplaintAttachment $attachment,
        AttachmentUploader $uploader,
    ): RedirectResponse {
        $this->authorizeDraft($request, $complaint);
        abort_unless($attachment->complaint_id === $complaint->id, 404);

        $uploader->delete($attachment);

        return back()->with('success', 'Anexo removido.');
    }

    // ---------------------------------------------------------------
    // Passo 4 — Dados do reclamante
    // ---------------------------------------------------------------

    public function contact(Request $request, Complaint $complaint): View
    {
        $this->authorizeDraft($request, $complaint);

        $user = $request->user();
        $details = $complaint->contactDetails;

        return view('consumer.wizard.contact', [
            'step' => 4,
            'steps' => self::STEPS,
            'complaint' => $complaint->load('company'),
            // Pré-preenchimento a partir do perfil: o utilizador não deve
            // reescrever aquilo que já nos deu.
            'values' => [
                'first_name' => $details?->first_name ?? $user->first_name,
                'last_name' => $details?->last_name ?? $user->last_name,
                'email' => $details?->email ?? $user->email,
                'phone' => $details?->phone ?? $user->phone,
                'country' => $details?->country ?? $user->country ?? 'PT',
                'district' => $details?->district ?? $user->district,
                'locality' => $details?->locality ?? $user->locality,
                'postal_code' => $details?->postal_code,
                'address' => $details?->address,
            ],
            'missingFields' => $user->missingComplaintProfileFields(),
            'districts' => Districts::all(),
        ]);
    }

    public function storeContact(StoreComplaintContactRequest $request, Complaint $complaint): RedirectResponse
    {
        $this->authorizeDraft($request, $complaint);

        $data = $request->validated();
        $identityIsPublic = $request->identityIsPublic();

        DB::transaction(function () use ($complaint, $data, $identityIsPublic): void {
            $complaint->contactDetails()->updateOrCreate(
                ['complaint_id' => $complaint->id],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'locality' => $data['locality'] ?? null,
                    'district' => $data['district'] ?? null,
                    'country' => $data['country'] ?? 'PT',
                ],
            );

            $complaint->update([
                'district' => $data['district'] ?? $complaint->district,
                'locality' => $data['locality'] ?? $complaint->locality,
                'country' => $data['country'] ?? $complaint->country,
                'is_identity_public' => $identityIsPublic,
            ]);
        });

        // Guardar no perfil deixou de ser uma caixa perdida no meio deste
        // formulário: é perguntado no fim, junto da submissão, quando a
        // pessoa já sabe que dados escreveu.

        return redirect()->route('complaints.wizard.review', $complaint->uuid);
    }

    // ---------------------------------------------------------------
    // Passo 5 — Revisão, consentimentos e submissão
    // ---------------------------------------------------------------

    public function review(Request $request, Complaint $complaint): View
    {
        $this->authorizeDraft($request, $complaint);

        $findings = $this->scanner->scan(
            $complaint->title,
            $complaint->description,
            (string) $complaint->desired_resolution,
        );

        return view('consumer.wizard.review', [
            'step' => 5,
            'steps' => self::STEPS,
            'complaint' => $complaint->load(['company', 'attachments', 'contactDetails', 'category']),
            'warnings' => $this->scanner->warningsFor($findings),
            'incomplete' => $this->missingRequirements($complaint),
        ]);
    }

    public function submit(Request $request, Complaint $complaint, ConsentRecorder $consents): RedirectResponse
    {
        $this->authorizeDraft($request, $complaint);

        $missing = $this->missingRequirements($complaint);

        if ($missing !== []) {
            return redirect()->route('complaints.wizard.review', $complaint->uuid)
                ->with('error', 'Faltam elementos obrigatórios: '.implode(', ', $missing));
        }

        $request->validate([
            'accept_terms' => ['accepted'],
            'accept_data_transfer' => ['accepted'],
            'confirm_truthful' => ['accepted'],
            'save_to_profile' => ['nullable', 'boolean'],
        ], [
            'accept_terms.accepted' => 'Tens de aceitar os Termos e Condições e a Política de Privacidade.',
            'accept_data_transfer.accepted' => 'Sem este consentimento não podemos transmitir a reclamação à empresa.',
            'confirm_truthful.accepted' => 'Tens de confirmar que a informação prestada é verdadeira.',
        ]);

        DB::transaction(function () use ($request, $complaint, $consents): void {
            $complaint->update(['share_contact_with_company' => true]);

            if ($request->boolean('save_to_profile')) {
                $this->copyContactToProfile($complaint, $request->user());
            }

            $complaint->contactDetails?->forceFill([
                'shared_with_company_at' => now(),
                // Retenção: os dados de contacto são expurgados dois anos
                // após o desfecho, mantendo-se a reclamação pública.
                'purge_after' => now()->addYears(2),
            ])->save();

            // Consentimentos ligados a ESTA reclamação (subject), não apenas
            // à conta: é assim que se prova o consentimento específico do
            // art. 6 n.1 a) para a transmissão dos dados à entidade visada.
            $consents->record(ConsentType::Terms, $request->user(), $complaint);
            $consents->record(ConsentType::Privacy, $request->user(), $complaint);
            $consents->record(ConsentType::DataTransferToCompany, $request->user(), $complaint);

            $this->workflow->submit($complaint, $request->user());
        });

        // A confirmação de sucesso é um ecrã próprio, não uma faixa verde
        // no topo: submeter uma reclamação é o momento que justifica todo
        // o esforço do formulário e merece um remate à altura.
        return redirect()
            ->route('consumer.complaints.show', $complaint->uuid)
            ->with('celebrate', [
                'title' => 'Reclamação submetida',
                'message' => 'Vamos analisá-la e avisamos-te assim que for publicada. Normalmente demora menos de 48 horas.',
                'reference' => $complaint->reference,
            ]);
    }

    /**
     * Copia para o perfil os dados de contacto desta reclamação.
     *
     * Só o que estiver preenchido: a resposta a "guardar no meu perfil" não
     * deve poder apagar dados que a pessoa já lá tinha.
     */
    private function copyContactToProfile(Complaint $complaint, User $user): void
    {
        $details = $complaint->contactDetails;

        if ($details === null) {
            return;
        }

        $user->fill(array_filter([
            'first_name' => $details->first_name,
            'last_name' => $details->last_name,
            'phone' => $details->phone,
            'country' => $details->country,
            'district' => $details->district,
            'locality' => $details->locality,
        ]))->save();
    }

    public function destroy(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorizeDraft($request, $complaint);

        $complaint->delete();

        return redirect()->route('consumer.complaints.index')->with('success', 'Rascunho eliminado.');
    }

    // ---------------------------------------------------------------

    private function authorizeDraft(Request $request, Complaint $complaint): void
    {
        abort_unless($complaint->user_id === $request->user()?->id, 403);
        abort_unless($complaint->isEditableByAuthor(), 403, 'Esta reclamação já não pode ser editada.');
    }

    /** @return array<int,string> */
    private function missingRequirements(Complaint $complaint): array
    {
        $missing = [];

        if (mb_strlen((string) $complaint->title) < (int) config('queixame.complaints.title_min')) {
            $missing[] = 'assunto';
        }

        if (mb_strlen((string) $complaint->description) < (int) config('queixame.complaints.description_min')) {
            $missing[] = 'descrição';
        }

        if ($complaint->company_id === null && blank($complaint->company_name_raw)) {
            $missing[] = 'empresa';
        }

        if ($complaint->contactDetails === null) {
            $missing[] = 'dados de contacto';
        }

        return $missing;
    }
}
