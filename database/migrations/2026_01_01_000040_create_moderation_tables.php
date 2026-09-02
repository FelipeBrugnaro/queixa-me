<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada decisao de moderacao fica registada com autor, motivo
        // normalizado e tempo de analise. Serve de prova em caso de disputa
        // e permite medir a consistencia da equipa.
        Schema::create('moderation_reviews', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32)->index();
            $table->string('reason_code', 48)->nullable();
            $table->text('notes')->nullable();
            $table->text('message_to_author')->nullable();
            $table->json('flags')->nullable();
            $table->unsignedInteger('review_seconds')->nullable();
            $table->timestamps();

            $table->index(['moderator_id', 'created_at']);
        });

        // Bloqueio otimista da fila: evita dois moderadores na mesma peca.
        Schema::create('moderation_locks', function (Blueprint $table) {
            $table->id();
            $table->morphs('lockable');
            $table->foreignId('moderator_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->morphs('reportable');
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reporter_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('reason', 40)->index();
            $table->text('details')->nullable();
            $table->string('status', 24)->default('open')->index();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('reporter_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Pedidos RGPD (acesso, portabilidade, apagamento, retificacao).
        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->string('status', 24)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->string('export_path')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_requests');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('moderation_locks');
        Schema::dropIfExists('moderation_reviews');
    }
};
