<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_users', 'ui_locale')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->string('ui_locale', 16)->nullable()->after('last_login_ip');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin_users', 'ui_locale')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->dropColumn('ui_locale');
            });
        }
    }
};
