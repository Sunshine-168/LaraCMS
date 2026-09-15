<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_users', 'reset_token')) {
                $table->string('reset_token', 64)->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('admin_users', 'reset_token_expires_at')) {
                $table->timestamp('reset_token_expires_at')->nullable()->after('reset_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            foreach (['reset_token', 'reset_token_expires_at'] as $col) {
                if (Schema::hasColumn('admin_users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
