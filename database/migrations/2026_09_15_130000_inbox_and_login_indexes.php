<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_form_submissions') && Schema::hasColumn('cms_form_submissions', 'is_read')) {
            Schema::table('cms_form_submissions', function (Blueprint $table) {
                $table->index(['site_id', 'is_read']);
            });
        }

        if (Schema::hasTable('cms_comments')) {
            Schema::table('cms_comments', function (Blueprint $table) {
                $table->index(['site_id', 'status']);
            });
        }

        if (Schema::hasTable('cms_collector_logs')) {
            Schema::table('cms_collector_logs', function (Blueprint $table) {
                $table->index(['collector_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_form_submissions')) {
            Schema::table('cms_form_submissions', function (Blueprint $table) {
                $table->dropIndex(['site_id', 'is_read']);
            });
        }
        if (Schema::hasTable('cms_comments')) {
            Schema::table('cms_comments', function (Blueprint $table) {
                $table->dropIndex(['site_id', 'status']);
            });
        }
        if (Schema::hasTable('cms_collector_logs')) {
            Schema::table('cms_collector_logs', function (Blueprint $table) {
                $table->dropIndex(['collector_id', 'created_at']);
            });
        }
    }
};
