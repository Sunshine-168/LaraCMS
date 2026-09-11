<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_form_submissions', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('ip');
            $table->text('admin_note')->nullable()->after('is_read');
            $table->timestamp('replied_at')->nullable()->after('admin_note');
        });
    }

    public function down(): void
    {
        Schema::table('cms_form_submissions', function (Blueprint $table) {
            $table->dropColumn(['is_read', 'admin_note', 'replied_at']);
        });
    }
};
