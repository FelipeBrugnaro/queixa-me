<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            $table->string('cover_path')->nullable();
            $table->string('cover_alt')->nullable();

            $table->string('status', 16)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('reading_minutes')->nullable();
            $table->unsignedInteger('views_count')->default(0);

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('posts_count')->default(0);
            $table->timestamps();
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('faq_categories')->nullOnDelete();
            $table->string('question');
            $table->string('slug')->unique();
            $table->text('answer');
            // Publico-alvo: permite mostrar a FAQ certa a consumidores e a
            // empresas sem duplicar paginas.
            $table->string('audience', 16)->default('all')->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // Paginas institucionais editaveis (Sobre, Termos, Privacidade...).
        // Guardar em base de dados permite versionar os documentos legais,
        // que e exatamente o que os registos de consentimento referenciam.
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('key', 48);
            $table->string('title');
            $table->string('slug')->index();
            $table->string('version', 16);
            $table->longText('body');
            $table->string('meta_description', 320)->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['key', 'version']);
            $table->index(['key', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('faq_categories');
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_categories');
    }
};
