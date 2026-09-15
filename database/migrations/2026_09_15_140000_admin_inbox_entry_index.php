<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_entries') && Schema::hasColumn('cms_entries', 'site_id')) {
            Schema::table('cms_entries', function (Blueprint $table) {
                $table->index(['site_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_entries')) {
            Schema::table('cms_entries', function (Blueprint $table) {
                $table->dropIndex(['site_id', 'status']);
            });
        }
    }
};
