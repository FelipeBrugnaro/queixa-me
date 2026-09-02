<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Login social. Guardar o email devolvido pelo fornecedor permite
        // ligar a conta existente em vez de criar duplicados.
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_user_id');
            $table->string('provider_email')->nullable();
            $table->boolean('provider_email_verified')->default(false);
            $table->string('avatar_url')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->index(['user_id', 'provider']);
        });

        // Alteracao de email em duas fases: o novo endereco so fica ativo
        // depois de confirmado, e o token e guardado com hash.
        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('new_email');
            $table->string('token_hash', 64)->index();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('requested_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'confirmed_at']);
        });

        // Verificacao de telefone: estrutura pronta, sem integracao SMS real.
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 32);
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'verified_at']);
        });

        // Registo probatorio de consentimentos (RGPD art. 7 n.1).
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 48)->index();
            $table->string('document_version', 16);
            $table->boolean('granted')->default(true);
            $table->nullableMorphs('subject');
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'granted']);
        });

        // Trilho de auditoria para acoes sensiveis (moderacao, admin, RGPD).
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64)->index();
            $table->nullableMorphs('auditable');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        // Tentativas de autenticacao: usado para bloqueio progressivo e
        // para mostrar ao utilizador acessos suspeitos.
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('ip_address', 45)->index();
            $table->boolean('successful')->default(false);
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('phone_verifications');
        Schema::dropIfExists('email_change_requests');
        Schema::dropIfExists('social_accounts');
    }
};
