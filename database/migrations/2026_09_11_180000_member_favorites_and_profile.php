<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_members', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_members', 'phone')) {
                $table->string('phone', 32)->nullable();
            }
            if (! Schema::hasColumn('cms_members', 'bio')) {
                $table->string('bio', 500)->nullable();
            }
            if (! Schema::hasColumn('cms_members', 'api_token')) {
                $table->string('api_token', 64)->nullable()->unique();
            }
        });

        if (! Schema::hasTable('cms_member_favorites')) {
            Schema::create('cms_member_favorites', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('site_id')->nullable()->index();
                $table->foreignId('member_id')->constrained('cms_members')->cascadeOnDelete();
                $table->foreignId('entry_id')->constrained('cms_entries')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['member_id', 'entry_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_member_favorites');
        Schema::table('cms_members', function (Blueprint $table) {
            foreach (['phone', 'bio', 'api_token'] as $col) {
                if (Schema::hasColumn('cms_members', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
