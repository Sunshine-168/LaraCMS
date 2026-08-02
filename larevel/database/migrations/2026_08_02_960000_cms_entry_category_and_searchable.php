<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_entry_category', function (Blueprint $table) {
            $table->foreignId('entry_id')->constrained('cms_entries')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('cms_categories')->cascadeOnDelete();
            $table->primary(['entry_id', 'category_id']);
        });

        // 回填：主栏目写入关联表
        if (Schema::hasTable('cms_entries')) {
            $rows = DB::table('cms_entries')->whereNotNull('category_id')->select('id', 'category_id')->get();
            foreach ($rows as $row) {
                DB::table('cms_entry_category')->insertOrIgnore([
                    'entry_id' => $row->id,
                    'category_id' => $row->category_id,
                ]);
            }
        }

        if (Schema::hasTable('cms_models') && ! Schema::hasColumn('cms_models', 'is_searchable')) {
            Schema::table('cms_models', function (Blueprint $table) {
                $table->boolean('is_searchable')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cms_models', 'is_searchable')) {
            Schema::table('cms_models', function (Blueprint $table) {
                $table->dropColumn('is_searchable');
            });
        }
        Schema::dropIfExists('cms_entry_category');
    }
};
