<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Enums\ModerationStatus;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Services\ComplaintWorkflow;
use App\Domain\Moderation\Enums\RejectionReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Regras de negócio do ciclo de vida da reclamação.
 *
 * São as regras que distinguem este portal de um mural de queixas: quem pode
 * declarar um problema resolvido, o que conta para os indicadores e o que
 * acontece quando a moderação devolve uma reclamação.
 */
class ComplaintWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ComplaintWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = app(ComplaintWorkflow::class);
    }

    #[Test]
    public function aprovar_gera_slug_permanente_e_publica(): void
    {
        $complaint = $this->submittedComplaint();
        $moderator = $this->moderator();

        $this->workflow->approve($complaint, $moderator);
        $complaint->refresh();

        $this->assertSame(ModerationStatus::Approved, $complaint->moderation_status);
        $this->assertSame(ComplaintStage::Published, $complaint->stage);
        $this->assertNotNull($complaint->slug);

        $slug = $complaint->slug;

        // Uma segunda aprovação (ex.: após alterações) não pode mudar o URL,
        // sob pena de partir ligações externas já indexadas.
        $complaint->forceFill(['moderation_status' => ModerationStatus::Submitted])->save();
        $this->workflow->approve($complaint->refresh(), $moderator);

        $this->assertSame($slug, $complaint->refresh()->slug);
    }

    #[Test]
    public function empresa_responde_e_marca_primeira_resposta(): void
    {
        $complaint = $this->publishedComplaint();
        $agent = User::factory()->business()->create();

        $reply = $this->workflow->addCompanyReply(
            $complaint, $complaint->company, $agent, 'Vamos tratar já do seu caso.'
        );

        $complaint->refresh();

        $this->assertTrue($reply->isFromCompany());
        $this->assertNotNull($complaint->first_response_at);
        $this->assertSame(ComplaintStage::CompanyReplied, $complaint->stage);
        $this->assertSame(1, $complaint->replies_count);

        // A segunda resposta não pode reescrever o tempo de primeira resposta,
        // que é o que alimenta o indicador de rapidez.
        $first = $complaint->first_response_at;
        $this->workflow->addCompanyReply($complaint, $complaint->company, $agent, 'Uma atualização ao seu caso.');

        $this->assertEquals($first, $complaint->refresh()->first_response_at);
        $this->assertSame(ComplaintStage::InFollowUp, $complaint->stage);
    }

    #[Test]
    public function so_o_consumidor_pode_confirmar_a_resolucao(): void
    {
        $complaint = $this->publishedComplaint();
        $agent = User::factory()->business()->create();

        $this->workflow->addCompanyReply(
            $complaint, $complaint->company, $agent, 'Propomos o reembolso integral.', isResolutionProposal: true
        );

        // A empresa propõe, mas não fecha.
        $this->assertNotSame(ComplaintStage::Resolved, $complaint->refresh()->stage);

        // Outra pessoa qualquer também não.
        $this->expectException(RuntimeException::class);
        $this->workflow->confirmResolution($complaint, User::factory()->create(), 5);
    }

    #[Test]
    public function confirmacao_do_consumidor_resolve_e_avalia(): void
    {
        $complaint = $this->publishedComplaint();
        $agent = User::factory()->business()->create();

        $this->workflow->addCompanyReply(
            $complaint, $complaint->company, $agent, 'Propomos o reembolso integral.', isResolutionProposal: true
        );

        $this->workflow->confirmResolution($complaint->refresh(), $complaint->user, 5, true, 'Resolveram rápido.');
        $complaint->refresh();

        $this->assertSame(ComplaintStage::Resolved, $complaint->stage);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertSame(5, $complaint->rating);
        $this->assertTrue($complaint->would_recommend);
    }

    #[Test]
    public function pedido_de_alteracoes_devolve_o_controlo_ao_autor(): void
    {
        $complaint = $this->submittedComplaint();

        $this->workflow->requestChanges(
            $complaint, $this->moderator(), RejectionReason::PersonalData, 'Remove o IBAN do texto.'
        );

        $complaint->refresh();

        $this->assertSame(ModerationStatus::ChangesRequested, $complaint->moderation_status);
        $this->assertTrue($complaint->isEditableByAuthor());
        $this->assertNull($complaint->published_at);

        // Reenviar volta a colocá-la na fila.
        $this->workflow->submit($complaint, $complaint->user);

        $this->assertTrue($complaint->refresh()->moderation_status->isPending());
    }

    #[Test]
    public function reclamacoes_laborais_nao_contam_para_o_indice_comercial(): void
    {
        $complaint = $this->publishedComplaint();
        $complaint->forceFill(['kind' => 'employee'])->save();

        $this->assertFalse($complaint->refresh()->countsTowardsIndex());
    }

    #[Test]
    public function reclamacao_recente_sem_resposta_nao_viola_o_prazo(): void
    {
        $complaint = $this->publishedComplaint();

        $this->assertTrue($complaint->awaitsCompanyReply());
        $this->assertFalse($complaint->responseSlaBreached());

        $complaint->forceFill([
            'published_at' => now()->subDays((int) config('queixame.complaints.response_sla_days') + 5),
        ])->save();

        $this->assertTrue($complaint->refresh()->responseSlaBreached());
    }

    // -----------------------------------------------------------------

    private function moderator(): User
    {
        return User::factory()->moderator()->create();
    }

    private function submittedComplaint(): Complaint
    {
        $complaint = Complaint::create([
            'user_id' => User::factory()->create()->id,
            'company_id' => $this->company()->id,
            'title' => 'Encomenda não entregue após três semanas',
            'description' => str_repeat('Descrição do problema. ', 20),
            'kind' => 'consumer',
            'country' => 'PT',
        ]);

        return $this->workflow->submit($complaint, $complaint->user);
    }

    private function publishedComplaint(): Complaint
    {
        $complaint = $this->submittedComplaint();
        $this->workflow->approve($complaint, $this->moderator());

        return $complaint->refresh()->load('company', 'user');
    }

    private function company(): Company
    {
        return Company::factory()->create(['status' => CompanyStatus::Active]);
    }
}
