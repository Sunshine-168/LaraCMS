<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('admin_users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
        });

        Schema::table('cms_admin_logs', function (Blueprint $table) {
            $table->index(['site_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('cms_admin_logs', function (Blueprint $table) {
            $table->dropIndex(['site_id', 'created_at']);
        });
        Schema::table('admin_users', function (Blueprint $table) {
            foreach (['last_login_at', 'last_login_ip'] as $col) {
                if (Schema::hasColumn('admin_users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
