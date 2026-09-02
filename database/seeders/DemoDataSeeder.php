<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Accounts\Enums\UserStatus;
use App\Domain\Accounts\Enums\UserType;
use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Enums\CompanyRole;
use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyCategory;
use App\Domain\Complaints\Enums\ActorType;
use App\Domain\Complaints\Enums\ComplaintEventType;
use App\Domain\Complaints\Enums\ComplaintKind;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Enums\ModerationStatus;
use App\Domain\Complaints\Models\Complaint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Dados de demonstração.
 *
 * Gera um portal com aparência realista: empresas com perfis de comportamento
 * distintos (quem responde depressa, quem ignora), reclamações espalhadas ao
 * longo de 12 meses, respostas, resoluções confirmadas e avaliações. Sem isto
 * é impossível avaliar o produto — uma homepage vazia não mostra nada sobre
 * as decisões de desenho.
 */
class DemoDataSeeder extends Seeder
{
    /** Perfis de comportamento: [taxa de resposta, taxa de resolução, horas até responder, avaliação média] */
    private const PROFILES = [
        'exemplar' => [0.97, 0.88, 6, 4.6],
        'bom' => [0.88, 0.72, 20, 4.0],
        'medio' => [0.68, 0.48, 60, 3.2],
        'fraco' => [0.42, 0.22, 140, 2.2],
        'ausente' => [0.12, 0.05, 300, 1.5],
    ];

    /** @var array<int,string> Perfil de comportamento por id de empresa. */
    private array $profiles = [];

    public function run(): void
    {
        $categories = CompanyCategory::pluck('id', 'slug');

        $staff = $this->createStaff();
        $consumers = User::factory()->count(40)->create();
        $companies = $this->createCompanies($categories);

        $this->createBusinessAccounts($companies);
        $this->createComplaints($companies, $consumers, $staff);
    }

    private function createStaff(): User
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@queixa.me'],
            [
                'uuid' => (string) Str::uuid(),
                'type' => UserType::Admin,
                'status' => UserStatus::Active,
                'name' => 'Administração queixa.me',
                'public_name' => 'equipa-qm',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_staff' => true,
                'country' => 'PT',
            ],
        );

        User::updateOrCreate(
            ['email' => 'moderador@queixa.me'],
            [
                'uuid' => (string) Str::uuid(),
                'type' => UserType::Moderator,
                'status' => UserStatus::Active,
                'name' => 'Moderação queixa.me',
                'public_name' => 'moderacao-qm',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_staff' => true,
                'country' => 'PT',
            ],
        );

        return $admin;
    }

    /** @return Collection<int,Company> */
    private function createCompanies($categories)
    {
        $definitions = [
            ['Nortel Comunicações', 'telecomunicacoes', 'exemplar'],
            ['Linha Azul Telecom', 'telecomunicacoes', 'fraco'],
            ['Banco Atlântico', 'banca-e-seguros', 'bom'],
            ['Seguros Horizonte', 'banca-e-seguros', 'medio'],
            ['LojaRápida.pt', 'comercio-online', 'medio'],
            ['MercadoDireto', 'comercio-online', 'ausente'],
            ['SuperCosta', 'retalho-e-supermercados', 'bom'],
            ['EntregaJá Transportes', 'encomendas-e-transportes', 'fraco'],
            ['Expresso Ibérico', 'encomendas-e-transportes', 'medio'],
            ['EnergiaViva', 'energia-e-agua', 'bom'],
            ['Voar Portugal', 'viagens-e-turismo', 'medio'],
            ['Hotéis do Sol', 'viagens-e-turismo', 'exemplar'],
            ['AutoCentro Norte', 'automovel', 'medio'],
            ['ClinicaBem', 'saude-e-bem-estar', 'bom'],
            ['TecnoMundo', 'tecnologia-e-eletronica', 'fraco'],
            ['Sabor & Companhia', 'restauracao', 'bom'],
            ['Academia Futuro', 'educacao-e-formacao', 'medio'],
            ['Casa Certa Imobiliária', 'habitacao-e-imobiliario', 'ausente'],
        ];

        $companies = collect();

        foreach ($definitions as [$name, $categorySlug, $profile]) {
            $company = Company::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'legal_name' => $name.', S.A.',
                    'category_id' => $categories[$categorySlug] ?? null,
                    'status' => CompanyStatus::Active,
                    'description' => 'Empresa de demonstração usada para ilustrar o funcionamento do queixa.me.',
                    'website' => 'https://'.Str::slug($name).'.pt',
                    'support_email' => 'apoio@'.Str::slug($name).'.pt',
                    'country' => 'PT',
                    'district' => fake()->randomElement(['Lisboa', 'Porto', 'Braga', 'Faro', 'Coimbra', 'Setúbal']),
                    'accepts_complaints' => true,
                ],
            );

            $this->profiles[$company->id] = $profile;
            $companies->push($company);
        }

        // Uma ficha por validar, para exercitar a fila de aprovação.
        Company::updateOrCreate(
            ['slug' => 'loja-nova-por-validar'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Loja Nova (por validar)',
                'status' => CompanyStatus::Pending,
                'country' => 'PT',
            ],
        );

        return $companies;
    }

    private function createBusinessAccounts($companies): void
    {
        foreach ($companies->take(6) as $company) {
            $manager = User::updateOrCreate(
                ['email' => 'gestor@'.Str::slug($company->name).'.pt'],
                [
                    'uuid' => (string) Str::uuid(),
                    'type' => UserType::Business,
                    'status' => UserStatus::Active,
                    'name' => 'Gestor '.$company->name,
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'country' => 'PT',
                ],
            );

            $company->members()->syncWithoutDetaching([
                $manager->id => ['role' => CompanyRole::Owner->value, 'accepted_at' => now(), 'revoked_at' => null],
            ]);

            $company->forceFill(['claimed_at' => now(), 'status' => CompanyStatus::Verified, 'verified_at' => now()])->save();
        }
    }

    private function createComplaints($companies, $consumers, User $staff): void
    {
        $subjects = [
            ['Encomenda não entregue e sem resposta ao pedido de reembolso', 'A encomenda tinha entrega prevista para 3 dias úteis. Passaram três semanas, o estado nunca mudou de "em trânsito" e o valor já foi cobrado.'],
            ['Cobrança de mensalidade depois do cancelamento do contrato', 'Cancelei o serviço com a antecedência exigida e recebi confirmação por escrito. Mesmo assim continuo a ser cobrado todos os meses.'],
            ['Equipamento avariado dentro da garantia sem substituição', 'O equipamento avariou dois meses depois da compra. Entreguei para assistência e desde então recebo respostas contraditórias sobre o prazo.'],
            ['Reembolso prometido há mais de dois meses ainda por creditar', 'Foi-me confirmado por escrito que o reembolso seria processado em 14 dias úteis. Já passaram mais de dois meses.'],
            ['Atendimento incorreto e recusa em aceitar a devolução legal', 'A devolução foi feita dentro do prazo legal e em perfeito estado. A loja recusou-se a aceitar, alegando uma política interna.'],
            ['Fatura com valores muito acima do contratado', 'A fatura deste mês tem um valor três vezes superior ao contratado, sem qualquer alteração da minha parte nem aviso prévio.'],
            ['Serviço interrompido durante uma semana sem aviso', 'O serviço esteve indisponível durante sete dias seguidos. Não recebi qualquer comunicação nem compensação.'],
            ['Produto entregue diferente do encomendado', 'Encomendei um modelo específico e recebi outro, de gama inferior. O pedido de troca continua sem resposta.'],
            ['Cancelamento de reserva no próprio dia sem alternativa', 'A reserva foi cancelada no dia, sem qualquer alternativa oferecida e sem devolução do valor pago.'],
            ['Contrato alterado unilateralmente sem comunicação prévia', 'As condições do contrato mudaram sem que eu fosse informado, e só percebi ao receber a fatura seguinte.'],
        ];

        $counter = 0;

        foreach ($companies as $company) {
            [$responseRate, $resolutionRate, $baseHours, $avgRating] = self::PROFILES[$this->profiles[$company->id] ?? 'medio'];

            $volume = random_int(8, 22);

            for ($i = 0; $i < $volume; $i++) {
                $counter++;
                [$title, $description] = $subjects[array_rand($subjects)];

                $publishedAt = Carbon::now()->subDays(random_int(2, 350))->setTime(random_int(8, 21), random_int(0, 59));
                $consumer = $consumers->random();

                $complaint = Complaint::create([
                    'uuid' => (string) Str::uuid(),
                    'reference' => 'QM-'.$publishedAt->format('Y').'-'.Str::upper(Str::random(6)),
                    'user_id' => $consumer->id,
                    'company_id' => $company->id,
                    'category_id' => $company->category_id,
                    'kind' => ComplaintKind::Consumer,
                    'title' => $title,
                    'slug' => Str::slug(Str::limit($title, 70, '').'-'.$company->name).'-'.Str::lower(Str::random(6)),
                    'description' => $description.' '.fake()->paragraph(4),
                    'occurred_on' => $publishedAt->copy()->subDays(random_int(1, 20))->toDateString(),
                    'desired_resolution' => fake()->randomElement([
                        'Pretendo o reembolso integral do valor pago.',
                        'Pretendo a substituição do equipamento por um novo.',
                        'Pretendo a correção da fatura e a devolução do valor cobrado a mais.',
                        'Pretendo que o contrato seja efetivamente cancelado, sem mais cobranças.',
                    ]),
                    'moderation_status' => ModerationStatus::Approved,
                    'stage' => ComplaintStage::Published,
                    'is_identity_public' => fake()->boolean(75),
                    'share_contact_with_company' => true,
                    'country' => 'PT',
                    'district' => $consumer->district,
                    'locality' => $consumer->locality,
                    'submitted_at' => $publishedAt->copy()->subHours(random_int(3, 40)),
                    'approved_at' => $publishedAt,
                    'published_at' => $publishedAt,
                    'company_notified_at' => $publishedAt,
                    'last_activity_at' => $publishedAt,
                    'views_count' => random_int(10, 2400),
                    'created_at' => $publishedAt->copy()->subHours(random_int(3, 40)),
                    'updated_at' => $publishedAt,
                ]);

                $complaint->events()->create([
                    'type' => ComplaintEventType::Published,
                    'actor_type' => ActorType::System,
                    'summary' => 'A reclamação foi publicada no portal.',
                    'is_public' => true,
                    'created_at' => $publishedAt,
                    'updated_at' => $publishedAt,
                ]);

                $this->simulateLifecycle($complaint, $company, $publishedAt, $responseRate, $resolutionRate, $baseHours, $avgRating);
            }

            $company->forceFill([
                'complaints_count' => Complaint::where('company_id', $company->id)->count(),
                'published_complaints_count' => Complaint::published()->where('company_id', $company->id)->count(),
                'is_indexable' => true,
            ])->save();
        }

        $this->command?->info("Criadas {$counter} reclamações de demonstração.");
    }

    private function simulateLifecycle(
        Complaint $complaint,
        Company $company,
        Carbon $publishedAt,
        float $responseRate,
        float $resolutionRate,
        int $baseHours,
        float $avgRating,
    ): void {
        // Reclamações muito recentes ainda podem legitimamente não ter resposta.
        $isRecent = $publishedAt->greaterThan(Carbon::now()->subDays(3));

        if ($isRecent || fake()->boolean((int) ((1 - $responseRate) * 100))) {
            return;
        }

        $respondedAt = $publishedAt->copy()->addHours(max(1, (int) round($baseHours * fake()->randomFloat(2, 0.3, 2.0))));

        if ($respondedAt->greaterThan(Carbon::now())) {
            $respondedAt = Carbon::now()->subHours(2);
        }

        $complaint->replies()->create([
            'uuid' => (string) Str::uuid(),
            'author_type' => ActorType::Company,
            'company_id' => $company->id,
            'author_display_name' => $company->name.' — Apoio ao Cliente',
            'body' => fake()->randomElement([
                'Lamentamos o sucedido e agradecemos o contacto. Já identificámos o seu processo e estamos a tratar do assunto com prioridade. Entraremos em contacto por mensagem privada para confirmar os dados necessários.',
                'Obrigado por nos dar a oportunidade de resolver. Verificámos o seu caso e vamos proceder à correção. Pedimos desculpa pelo transtorno causado.',
                'Pedimos desculpa pela experiência. O seu pedido foi encaminhado para a equipa responsável e terá resposta definitiva nos próximos dias úteis.',
            ]),
            'is_resolution_proposal' => false,
            'moderation_status' => ModerationStatus::Approved->value,
            'published_at' => $respondedAt,
            'created_at' => $respondedAt,
            'updated_at' => $respondedAt,
        ]);

        $complaint->events()->create([
            'type' => ComplaintEventType::CompanyReplied,
            'actor_type' => ActorType::Company,
            'actor_company_id' => $company->id,
            'is_public' => true,
            'created_at' => $respondedAt,
            'updated_at' => $respondedAt,
        ]);

        $complaint->forceFill([
            'first_response_at' => $respondedAt,
            'stage' => ComplaintStage::CompanyReplied,
            'replies_count' => 1,
            'last_activity_at' => $respondedAt,
        ])->save();

        if (! fake()->boolean((int) ($resolutionRate * 100))) {
            // Casos que ficaram em acompanhamento ou por resolver.
            if (fake()->boolean(35)) {
                $complaint->forceFill([
                    'stage' => ComplaintStage::Unresolved,
                    'rating' => max(1, (int) round($avgRating - fake()->randomFloat(1, 0.8, 2.0))),
                    'rated_at' => $respondedAt->copy()->addDays(random_int(3, 20)),
                ])->save();
            } else {
                $complaint->forceFill(['stage' => ComplaintStage::InFollowUp])->save();
            }

            return;
        }

        $resolvedAt = $respondedAt->copy()->addDays(random_int(1, 14));

        if ($resolvedAt->greaterThan(Carbon::now())) {
            $resolvedAt = Carbon::now()->subHour();
        }

        $rating = min(5, max(1, (int) round($avgRating + fake()->randomFloat(1, -0.8, 0.8))));

        $complaint->events()->create([
            'type' => ComplaintEventType::Resolved,
            'actor_type' => ActorType::Consumer,
            'summary' => 'O consumidor confirmou que o problema ficou resolvido.',
            'is_public' => true,
            'created_at' => $resolvedAt,
            'updated_at' => $resolvedAt,
        ]);

        $complaint->forceFill([
            'stage' => ComplaintStage::Resolved,
            'resolution_proposed_at' => $respondedAt,
            'resolved_at' => $resolvedAt,
            'rating' => $rating,
            'would_recommend' => $rating >= 4,
            'rated_at' => $resolvedAt,
            'last_activity_at' => $resolvedAt,
        ])->save();
    }
}
