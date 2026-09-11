<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_form_submissions', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('form_id')->constrained('cms_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cms_form_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
        });
    }
};
