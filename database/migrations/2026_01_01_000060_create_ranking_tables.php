<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fotografia periodica dos indicadores de cada empresa.
        // Guardar snapshots (em vez de calcular ao vivo) permite: graficos de
        // evolucao, rankings estaveis, auditoria de como o indice foi obtido
        // e paginas rapidas mesmo com milhoes de reclamacoes.
        Schema::create('company_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('period_type', 16);
            $table->date('period_start');
            $table->date('period_end');

            $table->unsignedInteger('complaints_count')->default(0);
            $table->unsignedInteger('answerable_count')->default(0);
            $table->unsignedInteger('replied_count')->default(0);
            $table->unsignedInteger('resolved_count')->default(0);
            $table->unsignedInteger('unresolved_count')->default(0);
            $table->unsignedInteger('rated_count')->default(0);

            $table->decimal('response_rate', 5, 2)->nullable();
            $table->decimal('resolution_rate', 5, 2)->nullable();
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->decimal('would_recommend_rate', 5, 2)->nullable();
            $table->unsignedInteger('avg_first_response_minutes')->nullable();
            $table->unsignedInteger('median_first_response_minutes')->nullable();
            $table->decimal('speed_score', 5, 2)->nullable();

            // Indice final (0-100) apos suavizacao bayesiana.
            $table->decimal('satisfaction_index', 5, 2)->nullable();
            // Indice bruto, sem suavizacao: publicado lado a lado por
            // transparencia metodologica.
            $table->decimal('raw_index', 5, 2)->nullable();
            $table->boolean('is_ranked')->default(false)->index();
            $table->unsignedInteger('rank_overall')->nullable();
            $table->unsignedInteger('rank_in_category')->nullable();
            $table->decimal('previous_index', 5, 2)->nullable();
            $table->decimal('index_delta', 6, 2)->nullable();

            $table->json('breakdown')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'period_type', 'period_start']);
            $table->index(['period_type', 'period_start', 'satisfaction_index']);
        });

        // Distincoes mensais (Marcas do Mes).
        Schema::create('brand_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('company_categories')->nullOnDelete();
            $table->string('award_type', 40)->index();
            $table->date('period_start');
            $table->unsignedSmallInteger('position')->default(1);
            $table->decimal('metric_value', 8, 2)->nullable();
            $table->text('editorial_note')->nullable();
            $table->boolean('is_editorial')->default(false);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();

            $table->unique(['award_type', 'period_start', 'position', 'category_id'], 'brand_awards_unique_slot');
            $table->index(['period_start', 'award_type']);
        });

        // Snapshot diario de metricas globais do portal (pagina de metodologia
        // e homepage: "X reclamacoes, Y% respondidas").
        Schema::create('platform_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('complaints_count')->default(0);
            $table->unsignedInteger('published_count')->default(0);
            $table->unsignedInteger('replied_count')->default(0);
            $table->unsignedInteger('resolved_count')->default(0);
            $table->unsignedInteger('companies_count')->default(0);
            $table->unsignedInteger('users_count')->default(0);
            $table->decimal('avg_response_rate', 5, 2)->nullable();
            $table->decimal('avg_resolution_rate', 5, 2)->nullable();
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_stats');
        Schema::dropIfExists('brand_awards');
        Schema::dropIfExists('company_stats');
    }
};
