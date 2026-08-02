<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateHomepageSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->command?->error('No admin user found. Run tallcms:setup first.');

            return;
        }

        // Replace any previous homepage so locale keys stay aligned with LocaleRegistry (zh_cn).
        DB::table('tallcms_pages')->where('is_homepage', true)->delete();

        $paragraph = static function (string $text): array {
            return [
                'type' => 'paragraph',
                'attrs' => [
                    'textAlign' => 'start',
                    'class' => null,
                    'id' => null,
                    'data' => null,
                    'style' => null,
                ],
                'content' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ];
        };

        $heading = static function (string $text): array {
            return [
                'type' => 'heading',
                'attrs' => [
                    'textAlign' => 'start',
                    'level' => 1,
                    'class' => null,
                    'id' => null,
                    'data' => null,
                    'style' => null,
                ],
                'content' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ];
        };

        $enContent = [
            'type' => 'doc',
            'content' => [
                $heading('Welcome to TallCMS'),
                $paragraph('A modern multilingual CMS built on Laravel, Livewire, Alpine.js, and Filament.'),
            ],
        ];

        $zhContent = [
            'type' => 'doc',
            'content' => [
                $heading('欢迎使用 TallCMS'),
                $paragraph('基于 Laravel、Livewire、Alpine.js 与 Filament 的现代化多语言内容管理系统。'),
            ],
        ];

        $row = [
            'author_id' => $user->id,
            'title' => json_encode([
                'en' => 'Home',
                'zh_cn' => '首页',
            ], JSON_UNESCAPED_UNICODE),
            'slug' => json_encode([
                'en' => 'home',
                'zh_cn' => 'home',
            ], JSON_UNESCAPED_UNICODE),
            'content' => json_encode([
                'en' => $enContent,
                'zh_cn' => $zhContent,
            ], JSON_UNESCAPED_UNICODE),
            'meta_title' => json_encode([
                'en' => 'Home | TallCMS',
                'zh_cn' => '首页 | TallCMS',
            ], JSON_UNESCAPED_UNICODE),
            'meta_description' => json_encode([
                'en' => 'Modern multilingual Laravel CMS',
                'zh_cn' => '现代化多语言 Laravel CMS',
            ], JSON_UNESCAPED_UNICODE),
            'status' => 'published',
            'is_homepage' => true,
            'sort_order' => 0,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('tallcms_pages', 'site_id')) {
            $row['site_id'] = DB::table('tallcms_sites')->where('is_default', true)->value('id')
                ?? DB::table('tallcms_sites')->value('id');
        }

        DB::table('tallcms_pages')->insert($row);

        $this->command?->info('Homepage created with en + zh_cn translations.');
    }
}
