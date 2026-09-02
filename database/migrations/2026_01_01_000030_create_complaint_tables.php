<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Referencia legivel comunicada ao consumidor e a empresa.
            $table->string('reference', 24)->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            // Quando o utilizador indica uma empresa que ainda nao existe,
            // guardamos o texto original ate a ficha ser validada e ligada.
            $table->string('company_name_raw')->nullable();
            $table->string('company_website_raw')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('company_categories')->nullOnDelete();

            $table->string('kind', 16)->default('consumer')->index();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->text('description');
            $table->date('occurred_on')->nullable();
            $table->text('desired_resolution')->nullable();
            $table->text('extra_info')->nullable();
            $table->string('purchase_reference')->nullable();
            $table->decimal('amount_involved', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();

            // Dois eixos de estado independentes (ver enums do dominio).
            $table->string('moderation_status', 24)->default('draft')->index();
            $table->string('stage', 24)->default('not_published')->index();
            $table->unsignedSmallInteger('priority')->default(0)->index();

            // Privacidade: o autor decide se o seu nome publico aparece.
            $table->boolean('is_identity_public')->default(true);
            // Consentimento explicito para transmitir dados a entidade visada.
            $table->boolean('share_contact_with_company')->default(false);

            // Localizacao da ocorrencia. Publicamos no maximo o distrito.
            $table->string('country', 2)->nullable();
            $table->string('district')->nullable();
            $table->string('locality')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('company_notified_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolution_proposed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();

            // Avaliacao final: so o consumidor pode avaliar, e so depois de a
            // empresa ter intervindo. Alimenta o indice de satisfacao.
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->text('rating_comment')->nullable();
            $table->timestamp('rated_at')->nullable();

            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->unsignedInteger('reports_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);

            // Resultado da deteccao automatica de dados sensiveis no texto.
            $table->json('sensitive_flags')->nullable();

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->boolean('is_indexable')->default(true);

            $table->string('submitted_ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'moderation_status', 'published_at']);
            $table->index(['company_id', 'stage']);
            $table->index(['moderation_status', 'priority', 'submitted_at']);
            $table->index(['user_id', 'moderation_status']);
            $table->index(['category_id', 'published_at']);
        });

        // SEPARACAO RGPD: os dados de contacto transmitidos a empresa vivem
        // numa tabela propria, cifrados, com data de expurgo independente do
        // conteudo publico da reclamacao. Assim e possivel apagar os dados
        // pessoais mantendo o registo publico anonimizado.
        Schema::create('complaint_contact_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('postal_code')->nullable();
            $table->text('locality')->nullable();
            $table->text('district')->nullable();
            $table->string('country', 2)->nullable();
            $table->text('document_number')->nullable();
            $table->timestamp('shared_with_company_at')->nullable();
            $table->timestamp('purge_after')->nullable()->index();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reply_id')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 32)->default('private');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 128);
            $table->unsignedInteger('size_bytes');
            $table->string('checksum', 64)->nullable()->index();
            // Anexos sao privados por omissao: so a moderacao e a empresa
            // visada lhes acedem, e apenas por rota autorizada.
            $table->boolean('is_public')->default(false);
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->index(['complaint_id', 'is_public']);
        });

        // Timeline auditavel. Cada mudanca relevante gera um evento imutavel.
        Schema::create('complaint_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->string('actor_type', 16)->default('system');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->text('summary')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['complaint_id', 'is_public', 'created_at']);
        });

        // Respostas publicas: da empresa e do consumidor, no fio da reclamacao.
        Schema::create('complaint_replies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('complaint_replies')->cascadeOnDelete();
            $table->string('author_type', 16);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_display_name')->nullable();
            $table->text('body');
            // Resposta que propoe formalmente uma solucao: abre a janela de
            // confirmacao ao consumidor.
            $table->boolean('is_resolution_proposal')->default(false);
            $table->string('moderation_status', 24)->default('approved')->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('reports_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['complaint_id', 'published_at']);
        });

        Schema::create('complaint_helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['complaint_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_helpful_votes');
        Schema::dropIfExists('complaint_replies');
        Schema::dropIfExists('complaint_events');
        Schema::dropIfExists('complaint_attachments');
        Schema::dropIfExists('complaint_contact_details');
        Schema::dropIfExists('complaints');
    }
};
