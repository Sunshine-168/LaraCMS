<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cms_posts');
        Schema::dropIfExists('cms_pages');
    }

    public function down(): void
    {
        if (! Schema::hasTable('cms_pages')) {
            Schema::create('cms_pages', function (Blueprint $table) {
                $table->id();
                $table->json('title')->nullable();
                $table->json('slug')->nullable();
                $table->json('content')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('cms_posts')) {
            Schema::create('cms_posts', function (Blueprint $table) {
                $table->id();
                $table->json('title')->nullable();
                $table->json('slug')->nullable();
                $table->json('content')->nullable();
                $table->timestamps();
            });
        }
    }
};
