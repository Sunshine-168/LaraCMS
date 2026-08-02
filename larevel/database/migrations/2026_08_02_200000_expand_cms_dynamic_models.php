<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_models', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('kind', 20)->default('list'); // list|single|form
            $table->string('list_tpl')->nullable();
            $table->string('show_tpl')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_model_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('cms_models')->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('label');
            $table->string('type', 32)->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filter')->default(false);
            $table->boolean('is_list')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['model_id', 'name']);
        });

        Schema::table('cms_categories', function (Blueprint $table) {
            $table->string('type', 20)->default('list')->after('slug'); // hub|list|single|link
            $table->foreignId('model_id')->nullable()->after('type')->constrained('cms_models')->nullOnDelete();
            $table->string('list_tpl')->nullable()->after('model_id');
            $table->string('show_tpl')->nullable();
            $table->string('category_tpl')->nullable();
            $table->boolean('is_menu')->default(true);
            $table->string('url')->nullable();
            $table->unsignedInteger('page_size')->default(10);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
        });

        Schema::create('cms_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('cms_categories')->nullOnDelete();
            $table->foreignId('model_id')->constrained('cms_models')->cascadeOnDelete();
            $table->json('title');
            $table->json('slug');
            $table->json('excerpt')->nullable();
            $table->json('content')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('attrs')->nullable();
            $table->string('cover')->nullable();
            $table->string('flags', 128)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->boolean('is_homepage')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('cms_recommends', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->string('title');
            $table->string('type', 20)->default('image'); // image|link|html|entry
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_recommend_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommend_id')->constrained('cms_recommends')->cascadeOnDelete();
            $table->json('title')->nullable();
            $table->json('notes')->nullable();
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->text('html')->nullable();
            $table->unsignedBigInteger('entry_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_forms', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->json('fields');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('cms_forms')->cascadeOnDelete();
            $table->json('data');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('cms_tags', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('slug');
            $table->timestamps();
        });

        Schema::create('cms_entry_tag', function (Blueprint $table) {
            $table->foreignId('entry_id')->constrained('cms_entries')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('cms_tags')->cascadeOnDelete();
            $table->primary(['entry_id', 'tag_id']);
        });

        Schema::table('cms_media', function (Blueprint $table) {
            $table->string('collection', 64)->nullable()->index()->after('alt');
        });
    }

    public function down(): void
    {
        Schema::table('cms_media', function (Blueprint $table) {
            $table->dropColumn('collection');
        });
        Schema::dropIfExists('cms_entry_tag');
        Schema::dropIfExists('cms_tags');
        Schema::dropIfExists('cms_form_submissions');
        Schema::dropIfExists('cms_forms');
        Schema::dropIfExists('cms_recommend_items');
        Schema::dropIfExists('cms_recommends');
        Schema::dropIfExists('cms_entries');
        Schema::table('cms_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('model_id');
            $table->dropColumn([
                'type', 'list_tpl', 'show_tpl', 'category_tpl',
                'is_menu', 'url', 'page_size', 'meta_title', 'meta_description',
            ]);
        });
        Schema::dropIfExists('cms_model_fields');
        Schema::dropIfExists('cms_models');
    }
};
