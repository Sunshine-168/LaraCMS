<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('cms_sites')->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained('cms_entries')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('author_name', 80);
            $table->string('author_email', 120)->nullable();
            $table->text('content');
            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected|spam
            $table->unsignedBigInteger('moderated_by')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'entry_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_comments');
    }
};
