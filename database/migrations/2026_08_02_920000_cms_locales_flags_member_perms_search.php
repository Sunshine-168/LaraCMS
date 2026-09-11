<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_locales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('label', 80);
            $table->string('native', 80)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('hide_default')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_flags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->string('title', 80);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('cms_member_groups', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('is_active');
        });

        Schema::table('cms_entries', function (Blueprint $table) {
            $table->text('search_text')->nullable()->after('flags');
        });

        $now = now();
        DB::table('cms_locales')->insert([
            ['code' => 'zh_cn', 'label' => '简体中文', 'native' => '简体中文', 'is_default' => true, 'hide_default' => true, 'is_active' => true, 'sort' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'en', 'label' => 'English', 'native' => 'English', 'is_default' => false, 'hide_default' => true, 'is_active' => true, 'sort' => 10, 'created_at' => $now, 'updated_at' => $now],
        ]);

        foreach ([
            ['name' => 'top', 'title' => '置顶', 'sort' => 10],
            ['name' => 'recommend', 'title' => '推荐', 'sort' => 20],
            ['name' => 'hot', 'title' => '热门', 'sort' => 30],
            ['name' => 'headline', 'title' => '头条', 'sort' => 40],
        ] as $row) {
            DB::table('cms_flags')->insert(array_merge($row, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        DB::table('cms_member_groups')->whereNull('permissions')->update([
            'permissions' => json_encode(['download', 'form_submit', 'view_member_content']),
        ]);
    }

    public function down(): void
    {
        Schema::table('cms_entries', function (Blueprint $table) {
            $table->dropColumn('search_text');
        });
        Schema::table('cms_member_groups', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
        Schema::dropIfExists('cms_flags');
        Schema::dropIfExists('cms_locales');
    }
};
