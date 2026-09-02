<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Redirecionamentos permanentes geridos pela aplicacao. Indispensavel
        // num portal com centenas de milhares de URLs publicos: fusoes de
        // empresas, correcoes de slug e migracoes nunca devem gerar 404.
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        // Registo de URLs que devolveram 404, para recuperar trafego perdido.
        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->unsignedInteger('hits')->default(1);
            $table->string('last_referer')->nullable();
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        // Indices de texto integral apenas onde o motor os suporta.
        // Em SQLite (desenvolvimento) a pesquisa cai para LIKE; em producao
        // MySQL/MariaDB usa MATCH ... AGAINST.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE complaints ADD FULLTEXT complaints_fulltext (title, description)');
            DB::statement('ALTER TABLE companies ADD FULLTEXT companies_fulltext (name, legal_name, description)');
            DB::statement('ALTER TABLE posts ADD FULLTEXT posts_fulltext (title, excerpt, body)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE posts DROP INDEX posts_fulltext');
            DB::statement('ALTER TABLE companies DROP INDEX companies_fulltext');
            DB::statement('ALTER TABLE complaints DROP INDEX complaints_fulltext');
        }

        Schema::dropIfExists('not_found_logs');
        Schema::dropIfExists('redirects');
    }
};
