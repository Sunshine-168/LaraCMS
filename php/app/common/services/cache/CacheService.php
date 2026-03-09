<?php
// +----------------------------------------------------------------------
// | 缓存服务
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2023 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\services\cache;

use app\common\services\BaseService;
use app\common\services\config\ConfigService;
use think\facade\Cache;

class CacheService extends BaseService
{
    // 缓存标签
    const CACHE_TAG = [
        'system',
        'addons',
        'common',
        'menu', // 菜单
        'model', // 模型
        'model_field', // 模型字段
        'category_tag', // 栏目标签
        'recommend_tag', // 推荐位表单标签
        'guestbook_tag', // 留言表单标签
        'archives_content_tag', // archives内容标签
        'taglist_tag', // 标签
        'single_tag', // 单页
        'tagpre_tag', // 上下页
        'tagquery_tag', // 查询
        'fieldsService' // 字段管理
    ];
    // 缓存key
    const CACHE_KEY = [
        'category_doc_total', // 栏目文档数量
        'category_doc_total_sub', // 栏目文档数量包含子级
        'cloud_token' // 应用市场登录Token
    ];

    /**
     * 清理所有
     * @return void
     */
    public function clearAll(): void
    {
        // 清理文件
        $this->clearFile();
        // 刷新版本更新前端
        app()->make(ConfigService::class)->refreshVer();
        // 清理缓存
        $this->clearBucket();
    }

    /**
     * 清理统一缓存列表
     * @return void
     */
    public function clearBucket(): void
    {
        $bucket = Cache::get('common_bucket');
        if (!is_array($bucket)) {
            $bucket = [];
        }
        $bucket['tag'] = array_merge(self::CACHE_TAG, $bucket['tag']??[]);
        $bucket['key'] = array_merge(self::CACHE_KEY, $bucket['key']??[]);
        Cache::tag($bucket['tag'])->clear();
        foreach ($bucket['key'] as $value) {
            Cache::delete($value);
        }
    }

    /**
     * 清理文件
     * @param string $dir
     * @param string $module
     * @return void
     */
    public function clearFile(string $dir = '', string $module = ""): void
    {
        $runtimePath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;
        $pathArr = [];
        if (!empty($module) && !empty($dir)) {
            $pathArr[] = $runtimePath . $module . DIRECTORY_SEPARATOR . $dir;
        } else if (!empty($dir)) {
            $pathArr[] = $runtimePath . $dir . DIRECTORY_SEPARATOR;
        } else if (!empty($module)) {
            $pathArr[] = $runtimePath . $module . DIRECTORY_SEPARATOR;
        } else {
            $dirArr = glob($runtimePath.'*', GLOB_ONLYDIR | GLOB_NOSORT);
            foreach ($dirArr as $dir) {
                if (in_array(basename($dir), ['.','..','session','cache'])) {
                    // 清理空文件夹
                    if (basename($dir)=='cache') {
                        $files = scandir($dir);
                        foreach ($files as $file) {
                            if ('.' != $file && '..' != $file && 'log' != $file && is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
                                @rmdir($dir . DIRECTORY_SEPARATOR . $file);
                            }
                        }
                    }
                    continue;
                }
                $pathArr[] = $dir . DIRECTORY_SEPARATOR;
            }
        }
        foreach ($pathArr as $path) {
            $this->clearRun(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, true);
        }
    }

    /**
     * 执行清理文件
     * @param string $path
     * @param bool $rmdir
     * @return void
     */
    protected function clearRun(string $path, bool $rmdir): void
    {
        $files = is_dir($path) ? scandir($path) : [];
        foreach ($files as $file) {
            if ('.' != $file && '..' != $file && 'log' != $file && is_dir($path . $file)) {
                $this->clearRun($path . $file . DIRECTORY_SEPARATOR, $rmdir);
                if ($rmdir) {
                    @rmdir($path . $file);
                }
            } elseif ('.gitignore' != $file && is_file($path . $file)) {
                unlink($path . $file);
            }
        }
    }

    /**
     * 清理日志
     * @return void
     */
    public function clearLog(): void
    {
        $runtimePath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;
        $this->clearRun($runtimePath . 'admin' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR , true);
        $this->clearRun($runtimePath . 'index' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR , true);
        $this->clearRun($runtimePath . 'api' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR , true);
    }

    /**
     * 加入统一缓存清理列表
     * @param array | string $value
     * @param string $type
     * @return void
     */
    public function bucket($value, string $type = 'tag'): void
    {
        $bucket = Cache::get('common_bucket');
        if (!is_array($bucket)) {
            $bucket = [];
        }
        if ($type=='tag') {
            $bucket['tag'] = $value;
        } else {
            $bucket['key'] = $value;
        }
        Cache::set('common_bucket', $bucket, 604800);
    }
}