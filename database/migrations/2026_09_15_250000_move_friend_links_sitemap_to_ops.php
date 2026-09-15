<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $now = now();
        DB::table('admin_permissions')->where('key', 'friend_links')->update([
            'group' => '运营',
            'sort' => 122,
            'updated_at' => $now,
        ]);
        DB::table('admin_permissions')->where('key', 'sitemap')->update([
            'name' => '地图',
            'group' => '运营',
            'sort' => 123,
            'updated_at' => $now,
        ]);
        DB::table('admin_permissions')->where('key', 'redirects')->update([
            'name' => '跳转',
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $now = now();
        DB::table('admin_permissions')->where('key', 'friend_links')->update([
            'group' => '内容',
            'sort' => 126,
            'updated_at' => $now,
        ]);
        DB::table('admin_permissions')->where('key', 'sitemap')->update([
            'name' => '站点地图',
            'group' => '内容',
            'sort' => 127,
            'updated_at' => $now,
        ]);
        DB::table('admin_permissions')->where('key', 'redirects')->update([
            'name' => '重定向',
            'updated_at' => $now,
        ]);
    }
};
