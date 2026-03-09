<?php
declare (strict_types=1);

namespace think\addons;

class Dir
{
    // 错误信息
    public $error = "";

    protected static $instance;

    /**
     * 单例模式
     * @param string $path
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 架构函数
     * @param string $path 目录路径
     * @param string $pattern 目录路径
     */
    public function __construct()
    {}

    /**
     * 判断文件、目录是否可写
     * @param $file
     * @return bool true=可写、false=不可写
     */
    public function isReallyWritable($file)
    {
        // 在Unix内核系统中关闭了safe_mode,可以直接使用is_writable()
        if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == false) {
            return is_writable($file);
        }

        // 在Windows系统中打开了safe_mode的情况
        if (is_dir($file)) {
            $file = rtrim($file, '/').'/'.md5(mt_rand(1,100).mt_rand(1,100));

            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }

    /**
     * 判断目录是否为空
     * @param $directory
     * @return bool
     */
    public function isEmpty($directory)
    {
        $handle = opendir($directory);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }

    /**
     * 取得目录中的结构信息,不包含下级
     * @param $directory
     * @return array|false
     */
    public function getList($directory)
    {
        return scandir($directory);
    }

    /**
     * 获取文件、文件夹列表（用于JsTree插件的多级结构数组、解析模板）
     * @param string $dir
     * @param string $filterExt 过滤文件后缀，$bool=true 表示只筛选$filterExt里面的后缀，false则为排除
     * @param boolean $bool
     * @return array
     */
    public function getJsTreeTpl(string $dir, string $filterExt = '', bool $bool = true)
    {
        $jstree = [];
        $filterExtArr = !empty($filterExt) ? explode(',', $filterExt) : '';
        $state = ['opened'=>false,'disabled'=>false,'selected'=>false];
        if (!is_dir($dir)) {
            return [];
        }
        $di = new \DirectoryIterator($dir);
        $i = 0;
        foreach ($di as $key=>$value) {
            if ($value->getBasename()=='.' || $value->getBasename()=='..') {
                continue;
            }
            if ($value->isDir()) {
                $jstree[$i]['icon'] = 'fas fa-folder';
                $jstree[$i]['data']['isdir'] = true;
                $jstree[$i]['children'] = $this->getJsTreeTpl($value->getRealPath() . DIRECTORY_SEPARATOR, $filterExt, $bool);
            } else {
                $ext = $value->getExtension();
                if ($filterExt && ($bool ? !in_array($ext,$filterExtArr) : in_array($ext,$filterExtArr))) {
                    continue;
                }
                $jstree[$i]['icon'] = 'far fa-file';
                $jstree[$i]['data']['isdir'] = false;
            }

            $title = $value->getFilename();
            $jstree[$i]['data']['org_text'] = $title;
            $jstree[$i]['data']['real'] = $value->getRealPath();

            if ($title=='info.ini') {
                $title .= '(模板信息)';
            }
            if ($title=='list') {
                $title .= '(列表)';
            }
            if ($title=='page') {
                $title .= '(单页)';
            }
            if ($title=='common') {
                $title .= '(公共文件)';
            }
            if ($title=='show') {
                $title .= '(详情)';
            }
            if ($title=='category') {
                $title .= '(栏目首页)';
            }
            if ($title=='search.html') {
                $title .= '(搜索)';
            }
            if ($title=='index.html') {
                $title .= '(首页)';
            }
            if ($title=='success.html') {
                $title .= '(成功)';
            }
            if ($title=='error.html') {
                $title .= '(错误)';
            }
            if ($title=='close.html') {
                $title .= '(站点关闭)';
            }
            if ($title=='config.json') {
                $title .= '(配置)';
            }
            $jstree[$i]['text'] = $title;
            $jstree[$i]['state'] = $state;
            $i++;
        }

        rsort($jstree);
        return $jstree;
    }

    /**
     * 遍历文件目录，返回目录下所有文件列表
     * @param $pattern string 路径及表达式
     * @param int $flags 附加选项
     * @param array $ignore 需要忽略的文件
     * @return array|false
     */
    public function rglob($pattern, $flags = 0, $ignore = [])
    {
        //获取子文件
        $files = glob($pattern, $flags);
        //修正部分环境返回 FALSE 的问题
        if (is_array($files) === FALSE)
            $files = array();
        //获取子目录
        $subdir = glob(dirname($pattern) .DIRECTORY_SEPARATOR. '*', GLOB_ONLYDIR | GLOB_NOSORT);
        if (is_array($subdir)) {
            foreach ($subdir as $dir) {
                if ($ignore && in_array($dir, $ignore))
                    continue;
                $files = array_merge($files, $this->rglob($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags, $ignore));
            }
        }
        return $files;
    }

    /**
     * 删除目录（包括下级的文件）
     * @param $directory
     * @param bool $subdir
     * @return bool
     */
    public function delDir($directory, $subdir=true)
    {
        if (is_dir($directory) == false) {
            $this->error = lang('Directory does not exist');
            return false;
        }
        $handle = opendir($directory);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                is_dir("$directory/$file") ? $this->delDir($directory.DIRECTORY_SEPARATOR.$file) : unlink($directory.DIRECTORY_SEPARATOR.$file);
            }
        }
        if (readdir($handle) == false) {
            closedir($handle);
            rmdir($directory);
        }
    }

    /**
     * 删除目录下面的所有文件，但不删除目录
     * @param $directory
     * @return bool
     */
    public function delFile($directory)
    {
        if (is_dir($directory) == false) {
            $this->error = lang('Directory does not exist');
            return false;
        }
        $handle = opendir($directory);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != ".." && is_file($directory.DIRECTORY_SEPARATOR.$file)) {
                unlink($directory.DIRECTORY_SEPARATOR.$file);
            }
        }
        closedir($handle);
        return true;
    }

    /**
     * 复制目录
     * @param $source
     * @param $destination
     * @return bool
     */
    public function copyDir($source, $destination)
    {
        if (is_dir($source) == false) {
            $this->error = lang('Source directory does not exist');
            return false;
        }
        if (is_dir($destination) == false) {
            mkdir($destination, 0700, true);
        }
        $handle = opendir($source);
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                is_dir($source . DIRECTORY_SEPARATOR .$file) ?
                                $this->copyDir($source . DIRECTORY_SEPARATOR .$file, $destination.DIRECTORY_SEPARATOR.$file) :
                                copy($source.DIRECTORY_SEPARATOR.$file, $destination.DIRECTORY_SEPARATOR.$file);
            }
        }
        closedir($handle);
    }

    /**
     * 移动文件目录
     * @param $tmpdir
     * @param $newdir
     * @param null $pack
     * @return bool
     */
    public function movedFile($tmpdir, $newdir, $pack = null)
    {
        $list = $this->rglob($tmpdir . '*', GLOB_BRACE);
        if (empty($list)) {
            $this->error = lang('Error moving files to the specified directory, reason: the file list is empty!');
            return false;
        }

        // 批量迁移文件
        foreach ($list as $file) {
            $newd = str_replace($tmpdir, $newdir, $file);
            // 目录名称
            $dirname = dirname($newd);
            if (file_exists($dirname) == false && mkdir($dirname, 0777, TRUE) == false) {
                $this->error = lang('Failed to create "%s" folder',[$dirname]);
                return false;
            }

            // 检查缓存包中的文件如果文件或者文件夹存在，但是不可写提示错误
            if (file_exists($file) && is_writable($file) == false) {
                $this->error = lang('File or directory, not writable')." [$file]";
                return false;
            }

            // 检查目标文件是否存在，如果文件或者文件夹存在，但是不可写提示错误
            if (file_exists($newd) && is_writable($newd) == false) {
                $this->error = lang('File or directory, not writable')." [$newd]";
                return false;
            }

            // 检查缓存包对应的文件是否文件夹，如果是，则创建文件夹
            if (is_dir($file)) {
                // 文件夹不存在则创建
                if (file_exists($newd) == false && mkdir($newd, 0777, TRUE) == false) {
                    $this->error = lang('Failed to create "%s" folder',[$newd]);
                    return false;
                }
            } else {
                if (file_exists($newd)) {
                    // 删除旧文件（winodws 环境需要）
                    if (!@unlink($newd)) {
                        $this->error = lang('Cannot delete file %s', [$newd]);
                        return false;
                    }
                }
                // 生成新文件，也就是把下载的，生成到新的路径中去
                if (!@rename($file, $newd)) {
                    $this->error = lang('Unable to generate %s file', [$newd]);
                    return false;
                }
            }
        }

        //删除临时目录
        $this->delDir($tmpdir);
        //删除文件包
        if (!empty($pack)) {
            @unlink($pack);
        }
        return true;
    }
}