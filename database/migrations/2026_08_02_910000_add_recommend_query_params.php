<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_recommends', function (Blueprint $table) {
            $table->json('query_params')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('cms_recommends', function (Blueprint $table) {
            $table->dropColumn('query_params');
        });
    }
};
