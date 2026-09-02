<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Accounts\Enums\UserType;
use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Content\Models\Post;
use App\Domain\Messaging\Models\Conversation;
use Database\Seeders\CompanyCategorySeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\LegalDocumentSeeder;
use Database\Seeders\PostSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Teste de fumo de todas as páginas.
 *
 * Não substitui testes de comportamento, mas apanha a classe de erro mais
 * comum e mais cara num portal com muitas páginas: uma view em falta, uma
 * variável não passada pelo controller, uma relação carregada em falta com
 * lazy loading desativado, ou uma rota que deixou de existir. Percorre cada
 * página com o papel que a deve poder ver, e verifica também que quem não
 * deve ver não vê.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CompanyCategorySeeder::class,
            FaqSeeder::class,
            LegalDocumentSeeder::class,
            DemoDataSeeder::class,
            PostSeeder::class,
        ]);
    }

    #[Test]
    public function paginas_publicas_respondem(): void
    {
        $company = Company::public()->firstOrFail();
        $complaint = Complaint::published()->firstOrFail();
        $post = Post::published()->firstOrFail();

        $urls = [
            '/',
            '/reclamacoes',
            '/reclamacoes?estado=resolved&ordenar=populares&periodo=90',
            '/reclamacao/'.$complaint->slug,
            '/empresas',
            '/empresas?q=nortel&ordenar=indice',
            '/empresas/categoria/telecomunicacoes',
            '/empresa/'.$company->slug,
            '/empresa/'.$company->slug.'/reclamacoes',
            '/empresa/'.$company->slug.'/reclamacoes?estado=resolved',
            '/ranking',
            '/ranking?ordenar=resposta&categoria=telecomunicacoes',
            '/comparar',
            '/marcas-do-mes',
            '/noticias',
            '/noticias/'.$post->slug,
            '/noticias/feed',
            '/pesquisar?q=encomenda',
            '/sobre-nos',
            '/como-funciona',
            '/indices-de-satisfacao',
            '/perguntas-frequentes',
            '/perguntas-frequentes?publico=empresas',
            '/contactos',
            '/termos-e-condicoes',
            '/politica-de-privacidade',
            '/protecao-de-dados',
            '/politica-de-moderacao',
            '/sitemap.xml',
            '/sitemap-paginas-1.xml',
            '/sitemap-empresas-1.xml',
            '/robots.txt',
            '/entrar',
            '/registar',
            '/registar/empresa',
            '/recuperar-palavra-passe',
            '/reclamar',
            '/api/empresas/sugestoes?q=nor',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk("Falhou: {$url}");
        }
    }

    #[Test]
    public function area_do_consumidor_responde(): void
    {
        $user = Complaint::published()->firstOrFail()->user;
        $this->actingAs($user);

        $complaint = Complaint::where('user_id', $user->id)->firstOrFail();

        foreach ([
            '/conta',
            '/conta/atividade',
            '/conta/reclamacoes',
            '/conta/reclamacoes/'.$complaint->uuid,
            '/conta/mensagens',
            '/conta/perfil',
            '/conta/privacidade',
        ] as $url) {
            $this->get($url)->assertOk("Falhou: {$url}");
        }
    }

    #[Test]
    public function assistente_de_reclamacao_percorre_todos_os_passos(): void
    {
        $user = User::factory()->create();
        $company = Company::public()->firstOrFail();

        $this->actingAs($user);

        // Passo 1 — empresa
        $this->post('/reclamar/empresa', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'kind' => 'consumer',
        ])->assertRedirect();

        $complaint = Complaint::where('user_id', $user->id)->latest()->firstOrFail();

        // Passo 2 — descrição
        $this->get('/reclamar/'.$complaint->uuid.'/descricao')->assertOk();
        $this->post('/reclamar/'.$complaint->uuid.'/descricao', [
            'description' => str_repeat('Descrição detalhada do problema ocorrido. ', 10),
        ])->assertRedirect();

        // Passo 3 — detalhes (incluindo os campos que identificam o processo)
        $this->get('/reclamar/'.$complaint->uuid.'/detalhes')->assertOk();
        $this->post('/reclamar/'.$complaint->uuid.'/detalhes', [
            'title' => 'Encomenda não entregue após três semanas',
            'occurred_on' => now()->subDays(20)->toDateString(),
            'desired_resolution' => 'Pretendo o reembolso integral.',
            'purchase_reference' => 'ENC-99887',
            'amount_involved' => '149,90',
        ])->assertRedirect();

        $complaint->refresh();
        $this->assertSame('ENC-99887', $complaint->purchase_reference);
        $this->assertSame('149.90', $complaint->amount_involved);

        // Passo 4 — dados de contacto
        $this->get('/reclamar/'.$complaint->uuid.'/dados')->assertOk();
        $this->post('/reclamar/'.$complaint->uuid.'/dados', [
            'first_name' => 'Ana',
            'last_name' => 'Silva',
            'email' => 'ana@exemplo.pt',
            'country' => 'PT',
            'district' => 'Lisboa',
        ])->assertRedirect();

        // Passo 5 — revisão e submissão
        $this->get('/reclamar/'.$complaint->uuid.'/confirmar')->assertOk();
        $this->post('/reclamar/'.$complaint->uuid.'/submeter', [
            'accept_terms' => '1',
            'accept_data_transfer' => '1',
            'confirm_truthful' => '1',
        ])->assertRedirect();

        $complaint->refresh();
        $this->assertTrue($complaint->moderation_status->isPending());
        $this->assertTrue($complaint->share_contact_with_company);
    }

    #[Test]
    public function area_da_empresa_responde(): void
    {
        $company = Company::whereHas('members')->firstOrFail();
        $manager = $company->members()->firstOrFail();

        $this->actingAs($manager);

        $complaint = Complaint::published()->where('company_id', $company->id)->firstOrFail();

        foreach ([
            '/gestao',
            '/gestao/reclamacoes',
            '/gestao/reclamacoes?filtro=por-responder',
            '/gestao/reclamacoes/'.$complaint->uuid,
            '/gestao/mensagens',
            '/gestao/estatisticas',
            '/gestao/perfil',
            '/gestao/equipa',
        ] as $url) {
            $this->get($url)->assertOk("Falhou: {$url}");
        }
    }

    #[Test]
    public function conversa_privada_da_empresa_responde(): void
    {
        $company = Company::whereHas('members')->firstOrFail();
        $manager = $company->members()->firstOrFail();
        $complaint = Complaint::published()->where('company_id', $company->id)->firstOrFail();

        $this->actingAs($manager)
            ->post('/gestao/reclamacoes/'.$complaint->uuid.'/mensagem', [
                'body' => 'Bom dia, para tratarmos do reembolso precisamos de confirmar alguns dados consigo.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $conversation = Conversation::where('company_id', $company->id)->firstOrFail();

        $this->get('/gestao/mensagens/'.$conversation->uuid)->assertOk();

        // O consumidor vê a mesma conversa do seu lado.
        $this->actingAs($complaint->user)
            ->get('/conta/mensagens/'.$conversation->uuid)
            ->assertOk();
    }

    #[Test]
    public function area_de_administracao_responde(): void
    {
        // Criar a reclamação pendente autentica o consumidor que a submete,
        // por isso o admin só entra em cena depois.
        $pending = $this->submitPendingComplaint();

        $admin = User::where('type', UserType::Admin)->firstOrFail();
        $this->actingAs($admin);

        $company = Company::firstOrFail();
        $user = User::where('type', UserType::Consumer)->firstOrFail();

        foreach ([
            '/admin',
            '/admin/moderacao',
            '/admin/moderacao?sensiveis=1',
            '/admin/moderacao/'.$pending->uuid,
            '/admin/empresas',
            '/admin/empresas?estado=pending',
            '/admin/empresas/'.$company->slug,
            '/admin/utilizadores',
            '/admin/utilizadores?tipo=consumer',
            '/admin/utilizadores/'.$user->id,
            '/admin/denuncias',
        ] as $url) {
            $this->get($url)->assertOk("Falhou: {$url}");
        }
    }

    #[Test]
    public function moderacao_aprova_e_publica(): void
    {
        $admin = User::where('type', UserType::Admin)->firstOrFail();
        $complaint = $this->submitPendingComplaint();

        $this->actingAs($admin)
            ->post('/admin/moderacao/'.$complaint->uuid.'/aprovar', ['notes' => 'Conforme.'])
            ->assertRedirect();

        $complaint->refresh();

        $this->assertTrue($complaint->isPublished());
        $this->assertNotNull($complaint->slug, 'A aprovação tem de gerar o slug público.');

        // A página pública passa a existir no endereço definitivo.
        $this->get('/reclamacao/'.$complaint->slug)->assertOk();
    }

    #[Test]
    public function areas_privadas_estao_protegidas(): void
    {
        // Visitante é redirecionado para o login.
        foreach (['/conta', '/gestao', '/admin'] as $url) {
            $this->get($url)->assertRedirect('/entrar');
        }

        // Consumidor não entra na administração.
        $consumer = User::where('type', UserType::Consumer)->firstOrFail();
        $this->actingAs($consumer)->get('/admin')->assertForbidden();

        // Nem lê a reclamação de outra pessoa.
        $other = Complaint::published()->where('user_id', '!=', $consumer->id)->firstOrFail();
        $this->get('/conta/reclamacoes/'.$other->uuid)->assertForbidden();
    }

    #[Test]
    public function paginas_privadas_nunca_sao_indexaveis(): void
    {
        $consumer = User::where('type', UserType::Consumer)->firstOrFail();

        $this->actingAs($consumer)
            ->get('/conta')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Cria e submete uma reclamação, deixando-a na fila de moderação. */
    private function submitPendingComplaint(): Complaint
    {
        $user = User::factory()->create();
        $company = Company::public()->firstOrFail();

        $this->actingAs($user);

        $this->post('/reclamar/empresa', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'kind' => 'consumer',
        ]);

        $complaint = Complaint::where('user_id', $user->id)->latest()->firstOrFail();

        $this->post('/reclamar/'.$complaint->uuid.'/descricao', [
            'description' => str_repeat('Descrição detalhada do problema ocorrido. ', 10),
        ]);

        $this->post('/reclamar/'.$complaint->uuid.'/detalhes', [
            'title' => 'Problema por resolver com a encomenda',
        ]);

        $this->post('/reclamar/'.$complaint->uuid.'/dados', [
            'first_name' => 'Ana',
            'last_name' => 'Silva',
            'email' => 'ana@exemplo.pt',
        ]);

        $this->post('/reclamar/'.$complaint->uuid.'/submeter', [
            'accept_terms' => '1',
            'accept_data_transfer' => '1',
            'confirm_truthful' => '1',
        ]);

        return $complaint->refresh();
    }
}
