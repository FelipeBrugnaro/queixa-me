<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('company_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon', 32)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'position']);
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained('company_categories')->nullOnDelete();

            $table->string('status')->default('pending')->index();
            // Fusao de duplicados sem perder URLs: a ficha antiga passa a
            // redirecionar (301) para a ficha destino.
            $table->foreignId('merged_into_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone', 32)->nullable();
            $table->string('vat_number', 32)->nullable()->index();

            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('brand_color', 9)->nullable();

            $table->string('country', 2)->default('PT');
            $table->string('district')->nullable();
            $table->string('locality')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 16)->nullable();

            // Denormalizacao deliberada: estes contadores sao lidos em quase
            // todas as paginas publicas e recalculados por job, evitando
            // agregacoes sobre milhoes de linhas em cada pedido.
            $table->unsignedInteger('complaints_count')->default(0);
            $table->unsignedInteger('published_complaints_count')->default(0);
            $table->unsignedInteger('replied_complaints_count')->default(0);
            $table->unsignedInteger('resolved_complaints_count')->default(0);
            $table->unsignedInteger('followers_count')->default(0);
            $table->decimal('satisfaction_index', 5, 2)->nullable()->index();
            $table->decimal('response_rate', 5, 2)->nullable();
            $table->decimal('resolution_rate', 5, 2)->nullable();
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->unsignedInteger('avg_first_response_minutes')->nullable();

            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->boolean('is_indexable')->default(false);
            $table->boolean('accepts_complaints')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category_id']);
            $table->index(['status', 'satisfaction_index']);
            $table->index(['country', 'district']);
        });

        // Historico de slugs: garante URLs permanentes. Uma empresa que muda
        // de nome mantem as ligacoes antigas vivas atraves de 301.
        Schema::create('company_slugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 24)->default('manager');
            $table->string('job_title')->nullable();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        // Reivindicacao de ficha de empresa por um gestor.
        Schema::create('company_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->string('work_email')->nullable();
            $table->string('vat_number', 32)->nullable();
            $table->text('evidence')->nullable();
            $table->text('decision_notes')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('company_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_follows');
        Schema::dropIfExists('company_claims');
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('company_slugs');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('company_categories');
    }
};
