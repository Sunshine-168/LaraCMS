<?php

namespace App\Cms;

/** 自带内容模型定义。演示数据和主题包共用，避免各主题各写一套。 */
class BundledContentModels
{
    /**
     * @return array<string, array{
     *     name:string,
     *     kind:string,
     *     list_tpl:?string,
     *     show_tpl:string,
     *     allow_contribute?:bool,
     *     fields:list<array<string, mixed>>
     * }>
     */
    public static function definitions(): array
    {
        return [
            'article' => ['name' => '文章', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show', 'allow_contribute' => true, 'fields' => [
                ['name' => 'source_note', 'label' => '来源', 'type' => 'text'],
            ]],
            'page' => ['name' => '单页', 'kind' => 'single', 'list_tpl' => null, 'show_tpl' => 'show.show', 'fields' => []],
            'product' => [
                'name' => '产品', 'kind' => 'list', 'list_tpl' => 'list.list_product', 'show_tpl' => 'show.show_product',
                'fields' => [
                    ['name' => 'price', 'label' => '价格', 'type' => 'text', 'is_list' => true],
                    ['name' => 'buylink', 'label' => '购买链接', 'type' => 'url'],
                ],
            ],
            'job' => [
                'name' => '招聘', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'salary', 'label' => '薪资', 'type' => 'text', 'is_list' => true],
                    ['name' => 'city', 'label' => '城市', 'type' => 'text', 'is_filter' => true, 'options' => ['choices' => ['北京', '上海', '远程']]],
                ],
            ],
            'download' => [
                'name' => '下载', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'version', 'label' => '版本', 'type' => 'text', 'is_list' => true],
                    ['name' => 'size', 'label' => '大小', 'type' => 'text'],
                    ['name' => 'platform', 'label' => '平台', 'type' => 'select', 'is_filter' => true, 'is_list' => true, 'options' => ['choices' => ['windows', 'mac', 'linux', 'android']]],
                    ['name' => 'file', 'label' => '文件路径/URL', 'type' => 'file'],
                ],
            ],
            'link' => [
                'name' => '导航链接', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'url', 'label' => '网址', 'type' => 'url', 'is_required' => true],
                    ['name' => 'icon', 'label' => '图标', 'type' => 'image'],
                ],
            ],
            'source' => [
                'name' => '源码', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'demo_url', 'label' => '演示地址', 'type' => 'url'],
                    ['name' => 'license', 'label' => '授权', 'type' => 'select', 'is_filter' => true, 'options' => ['choices' => ['MIT', 'GPL', '商业']]],
                    ['name' => 'price', 'label' => '价格', 'type' => 'text', 'is_list' => true],
                    ['name' => 'package', 'label' => '源码包路径/URL', 'type' => 'file'],
                ],
            ],
            'manga' => [
                'name' => '漫画', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'author', 'label' => '作者', 'type' => 'text', 'is_list' => true],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'is_filter' => true, 'is_list' => true, 'options' => ['choices' => ['连载', '完结']]],
                    ['name' => 'area', 'label' => '地区', 'type' => 'select', 'is_filter' => true, 'options' => ['choices' => ['国漫', '日漫', '韩漫']]],
                ],
            ],
            'chapter' => [
                'name' => '章节', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.chapter',
                'fields' => [
                    ['name' => 'manga_id', 'label' => '所属漫画', 'type' => 'entry', 'is_required' => true, 'options' => ['related_model' => 'manga']],
                    ['name' => 'pages', 'label' => '图片URL（每行一张）', 'type' => 'textarea'],
                ],
            ],
            'signal' => [
                'name' => '体育信号', 'kind' => 'list', 'list_tpl' => 'list.list', 'show_tpl' => 'show.show',
                'fields' => [
                    ['name' => 'sport', 'label' => '项目', 'type' => 'select', 'is_filter' => true, 'is_list' => true, 'options' => ['choices' => ['足球', '篮球', '网球', '综合']]],
                    ['name' => 'kickoff', 'label' => '开赛时间', 'type' => 'text', 'is_list' => true],
                    ['name' => 'source_url', 'label' => '信号/资讯链接', 'type' => 'url', 'is_required' => true],
                    ['name' => 'quality', 'label' => '清晰度', 'type' => 'select', 'options' => ['choices' => ['高清', '标清', '文字']]],
                ],
            ],
        ];
    }

    /** @return array{version:int,model:array<string,mixed>,fields:list<array<string,mixed>>}|null */
    public static function exportPayload(string $slug): ?array
    {
        $def = static::definitions()[$slug] ?? null;
        if (! is_array($def)) {
            return null;
        }
        $fields = [];
        foreach ($def['fields'] as $i => $field) {
            $fields[] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => $field['options'] ?? null,
                'is_required' => ! empty($field['is_required']),
                'is_filter' => ! empty($field['is_filter']),
                'is_list' => ! empty($field['is_list']),
                'sort_order' => $i,
            ];
        }

        return [
            'version' => 1,
            'model' => [
                'slug' => $slug,
                'name' => $def['name'],
                'kind' => $def['kind'],
                'list_tpl' => $def['list_tpl'],
                'show_tpl' => $def['show_tpl'],
                'sort_order' => 0,
                'is_active' => true,
                'is_searchable' => true,
                'allow_contribute' => ! empty($def['allow_contribute']),
            ],
            'fields' => $fields,
        ];
    }
}
