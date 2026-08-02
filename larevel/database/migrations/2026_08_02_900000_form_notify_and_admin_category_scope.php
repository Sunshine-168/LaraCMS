<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_forms', function (Blueprint $table) {
            $table->string('notify_email', 255)->nullable()->after('is_active');
        });

        Schema::table('admin_users', function (Blueprint $table) {
            $table->json('allowed_category_ids')->nullable()->after('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('cms_forms', function (Blueprint $table) {
            $table->dropColumn('notify_email');
        });
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn('allowed_category_ids');
        });
    }
};
