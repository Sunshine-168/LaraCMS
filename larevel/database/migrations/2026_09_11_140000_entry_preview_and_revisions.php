<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_entries', function (Blueprint $table) {
            $table->string('preview_token', 64)->nullable()->unique()->after('published_at');
            $table->timestamp('preview_expires_at')->nullable()->after('preview_token');
        });

        Schema::create('cms_entry_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();
            $table->foreignId('entry_id')->constrained('cms_entries')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->json('snapshot');
            $table->string('title_hint', 191)->nullable();
            $table->timestamps();
            $table->index(['entry_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_entry_revisions');
        Schema::table('cms_entries', function (Blueprint $table) {
            $table->dropColumn(['preview_token', 'preview_expires_at']);
        });
    }
};
