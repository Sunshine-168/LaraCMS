<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain', 191)->unique();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('theme', 64)->nullable();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('admin_user_site', function (Blueprint $table) {
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('cms_sites')->cascadeOnDelete();
            $table->primary(['admin_user_id', 'site_id']);
        });

        $host = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
        $host = strtolower(preg_replace('/:\d+$/', '', (string) $host) ?: 'localhost');
        $now = now();
        $siteId = (int) DB::table('cms_sites')->insertGetId([
            'name' => '默认站点',
            'domain' => $host,
            'is_default' => true,
            'is_active' => true,
            'theme' => null,
            'remark' => '迁移自动创建',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tenantTables = [
            'cms_settings',
            'cms_categories',
            'cms_pages',
            'cms_posts',
            'cms_menus',
            'cms_media',
            'cms_models',
            'cms_entries',
            'cms_recommends',
            'cms_forms',
            'cms_form_submissions',
            'cms_tags',
            'cms_members',
            'cms_member_groups',
            'cms_locales',
            'cms_flags',
            'cms_collectors',
            'cms_stat_hits',
            'cms_admin_logs',
        ];

        foreach ($tenantTables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'site_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($siteId) {
                $blueprint->unsignedBigInteger('site_id')->default($siteId)->after('id');
            });
            DB::table($table)->whereNull('site_id')->orWhere('site_id', 0)->update(['site_id' => $siteId]);
        }

        $this->reunique('cms_settings', 'key', ['site_id', 'key']);
        $this->reunique('cms_models', 'slug', ['site_id', 'slug']);
        $this->reunique('cms_recommends', 'name', ['site_id', 'name']);
        $this->reunique('cms_forms', 'slug', ['site_id', 'slug']);
        $this->reunique('cms_members', 'email', ['site_id', 'email']);
        $this->reunique('cms_member_groups', 'slug', ['site_id', 'slug']);
        $this->reunique('cms_locales', 'code', ['site_id', 'code']);
        $this->reunique('cms_flags', 'name', ['site_id', 'name']);

        foreach ($tenantTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'site_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->index('site_id');
                $blueprint->foreign('site_id')->references('id')->on('cms_sites')->cascadeOnDelete();
            });
        }
    }

    /** @param list<string> $columns */
    private function reunique(string $table, string $oldColumn, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($oldColumn) {
            $blueprint->dropUnique([$oldColumn]);
        });
        Schema::table($table, function (Blueprint $blueprint) use ($columns) {
            $blueprint->unique($columns);
        });
    }

    public function down(): void
    {
        $tenantTables = [
            'cms_admin_logs',
            'cms_stat_hits',
            'cms_collectors',
            'cms_flags',
            'cms_locales',
            'cms_member_groups',
            'cms_members',
            'cms_tags',
            'cms_form_submissions',
            'cms_forms',
            'cms_recommends',
            'cms_entries',
            'cms_models',
            'cms_media',
            'cms_menus',
            'cms_posts',
            'cms_pages',
            'cms_categories',
            'cms_settings',
        ];

        foreach ($tenantTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'site_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['site_id']);
                $blueprint->dropColumn('site_id');
            });
        }

        Schema::dropIfExists('admin_user_site');
        Schema::dropIfExists('cms_sites');
    }
};
