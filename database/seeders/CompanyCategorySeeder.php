<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Companies\Models\CompanyCategory;
use Illuminate\Database\Seeder;

class CompanyCategorySeeder extends Seeder
{
    /**
     * Taxonomia inicial de setores.
     *
     * Deliberadamente curta e ampla: categorias a mais fragmentam as páginas
     * de setor em conteúdo fino e obrigam o utilizador a adivinhar onde
     * classificar. Subcategorias entram quando o volume as justificar.
     */
    public function run(): void
    {
        $categories = [
            ['Telecomunicações', 'Operadoras de telemóvel, internet e televisão.'],
            ['Banca e seguros', 'Bancos, seguradoras, crédito e serviços financeiros.'],
            ['Comércio online', 'Lojas online, marketplaces e compras à distância.'],
            ['Retalho e supermercados', 'Superfícies comerciais, lojas físicas e cadeias de retalho.'],
            ['Encomendas e transportes', 'Transportadoras, entregas ao domicílio e logística.'],
            ['Energia e água', 'Eletricidade, gás natural e abastecimento de água.'],
            ['Viagens e turismo', 'Companhias aéreas, agências, hotelaria e alojamento.'],
            ['Automóvel', 'Concessionários, oficinas, aluguer e assistência em viagem.'],
            ['Saúde e bem-estar', 'Clínicas, seguros de saúde, farmácias e ginásios.'],
            ['Tecnologia e eletrónica', 'Equipamentos, software, assistência técnica e garantias.'],
            ['Restauração', 'Restaurantes, cafés e entregas de refeições.'],
            ['Educação e formação', 'Escolas, cursos, plataformas de ensino e explicações.'],
            ['Habitação e imobiliário', 'Mediação imobiliária, arrendamento, obras e condomínios.'],
            ['Serviços públicos', 'Serviços de âmbito público e concessionados.'],
            ['Outros serviços', 'Serviços que não se enquadram nas restantes categorias.'],
        ];

        foreach ($categories as $position => [$name, $description]) {
            CompanyCategory::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'position' => $position,
                    'meta_title' => 'Reclamações de '.$name.' — empresas e índices',
                    'meta_description' => 'Reclamações, taxas de resposta e índices de satisfação das empresas do setor '.mb_strtolower($name).'.',
                ],
            );
        }
    }
}
