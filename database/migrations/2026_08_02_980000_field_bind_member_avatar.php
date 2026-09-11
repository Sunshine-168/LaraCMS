<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_model_field_category', function (Blueprint $table) {
            $table->foreignId('model_field_id')->constrained('cms_model_fields')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('cms_categories')->cascadeOnDelete();
            $table->primary(['model_field_id', 'category_id']);
        });

        if (Schema::hasTable('cms_members') && ! Schema::hasColumn('cms_members', 'avatar')) {
            Schema::table('cms_members', function (Blueprint $table) {
                $table->string('avatar', 500)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cms_members', 'avatar')) {
            Schema::table('cms_members', function (Blueprint $table) {
                $table->dropColumn('avatar');
            });
        }
        Schema::dropIfExists('cms_model_field_category');
    }
};
