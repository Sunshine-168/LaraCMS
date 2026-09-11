<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_entries', 'member_id')) {
                $table->foreignId('member_id')->nullable()->constrained('cms_members')->nullOnDelete();
            }
        });

        Schema::table('cms_models', function (Blueprint $table) {
            if (! Schema::hasColumn('cms_models', 'allow_contribute')) {
                $table->boolean('allow_contribute')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('cms_entries', function (Blueprint $table) {
            if (Schema::hasColumn('cms_entries', 'member_id')) {
                $table->dropConstrainedForeignId('member_id');
            }
        });
        Schema::table('cms_models', function (Blueprint $table) {
            if (Schema::hasColumn('cms_models', 'allow_contribute')) {
                $table->dropColumn('allow_contribute');
            }
        });
    }
};
