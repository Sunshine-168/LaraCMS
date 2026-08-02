<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->boolean('is_super')->default(false);
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name', 80);
            $table->string('group', 40)->default('内容');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('admin_role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('admin_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('admin_permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::table('admin_users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained('admin_roles')->nullOnDelete();
        });

        // 先建默认角色，再把旧字符串 role 映射过来
        $now = now();
        $superId = DB::table('admin_roles')->insertGetId([
            'name' => '超级管理员',
            'slug' => 'super_admin',
            'is_super' => true,
            'remark' => '拥有全部权限',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $editorId = DB::table('admin_roles')->insertGetId([
            'name' => '编辑',
            'slug' => 'editor',
            'is_super' => false,
            'remark' => '内容与运营基础权限',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('admin_roles')->insert([
            'name' => '运营',
            'slug' => 'operator',
            'is_super' => false,
            'remark' => '统计与留言处理',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (DB::table('admin_users')->select('id', 'role')->get() as $user) {
            $roleId = ($user->role ?? '') === 'super_admin' ? $superId : $editorId;
            DB::table('admin_users')->where('id', $user->id)->update(['role_id' => $roleId]);
        }

        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->string('role', 32)->default('editor')->after('password');
        });

        foreach (DB::table('admin_users')->select('id', 'role_id')->get() as $user) {
            $slug = DB::table('admin_roles')->where('id', $user->role_id)->value('slug') ?: 'editor';
            DB::table('admin_users')->where('id', $user->id)->update(['role' => $slug]);
        }

        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('admin_role_permission');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_roles');
    }
};
