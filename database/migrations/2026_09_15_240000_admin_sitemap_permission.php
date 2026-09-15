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
        $exists = DB::table('admin_permissions')->where('key', 'sitemap')->exists();
        if (! $exists) {
            DB::table('admin_permissions')->insert([
                'key' => 'sitemap',
                'name' => '地图',
                'group' => '运营',
                'sort' => 123,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permId = DB::table('admin_permissions')->where('key', 'sitemap')->value('id');
        $editorId = DB::table('admin_roles')->where('slug', 'editor')->value('id');
        if ($permId && $editorId && Schema::hasTable('admin_role_permission')) {
            $linked = DB::table('admin_role_permission')
                ->where('role_id', $editorId)
                ->where('permission_id', $permId)
                ->exists();
            if (! $linked) {
                DB::table('admin_role_permission')->insert([
                    'role_id' => $editorId,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_permissions')) {
            return;
        }
        $permId = DB::table('admin_permissions')->where('key', 'sitemap')->value('id');
        if ($permId && Schema::hasTable('admin_role_permission')) {
            DB::table('admin_role_permission')->where('permission_id', $permId)->delete();
        }
        DB::table('admin_permissions')->where('key', 'sitemap')->delete();
    }
};
