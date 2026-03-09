<?php
// +----------------------------------------------------------------------
// | HkCms 文件上传管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\common\library;

use app\admin\model\routine\Attachment;
use app\common\exception\UploadException;
use libs\image\Image;
use think\addons\Dir;
use think\facade\Db;
use think\facade\Validate;
use think\File;
use think\helper\Str;

class Upload
{
    protected $config = [
        'file_size'=>10485760, // 上传文件大小默认10m
        'savename'=>'/uploads/{year}{month}{day}/{md5}{suffix}', // 保存格式
        'chunk'=>2, // 1-开启，0关闭
        'chunk_size'=>2097152, // 分片大小默认2m
        'user_type'=>0,
        'user_id'=>0,
        'storage'=>'local' // 默认本地
    ];

    protected static $instance;

    /**
     * 单例模式
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    public function __construct($config = [])
    {
        $site = site();
        $this->config = array_merge($this->config, $site, $config);
    }

    /**
     * 清理分块文件
     * @param string $fileId 唯一标识
     */
    public function clear($fileId)
    {
        $fileId = md5($fileId);
        $path = app()->getRuntimePath() . 'storage'.DIRECTORY_SEPARATOR.'chunk'.DIRECTORY_SEPARATOR.$fileId;
        Dir::instance()->delDir($path);
    }

    /**
     * 合并文件
     * @param string $fileId 唯一ID
     * @param integer $count
     * @param array $chunkInfo
     * @return mixed
     * @throws UploadException
     */
    public function merge($fileId, $count, $chunkInfo)
    {
        $fileId = md5($fileId);

        // 临时文件
        $path = app()->getRuntimePath() . 'storage'.DIRECTORY_SEPARATOR.'chunk'.DIRECTORY_SEPARATOR.$fileId;

        $chunk = [];
        // 文件验证
        for ($i=1; $i<=$count; $i++) {
            $tmp = $path.DIRECTORY_SEPARATOR.$fileId.'-'.$i.'.'.$chunkInfo['ext'];
            if (!file_exists($tmp)) {
                throw new UploadException(__('File merge failed'));
            }
            $chunk[] = $tmp;
        }

        // 合并文件保存位置
        $tempFile = $path.DIRECTORY_SEPARATOR.$fileId.'.'.$chunkInfo['ext'];
        $fp = @fopen($tempFile,"ab");
        if (empty($fp)) {
            throw new UploadException(__('File merge failed'));
        }

        // 开始合并
        foreach ($chunk as $k=>$v) {
            if (!$handle = @fopen($v, 'rb')) {
                @fclose($fp);
                // 删除目录
                Dir::instance()->delDir($path);
                throw new UploadException(__('File merge failed'));
            }

            fwrite($fp, fread($handle, filesize($v)));
            @fclose($handle);
            @unlink($v);
        }
        @fclose($fp);

        // 获取文件信息,并移动
        $file = new File($tempFile);

        // 生成规则路径
        $path = $this->getFileName($file);

        // 移动文件
        $file = $file->move(dirname(public_path().$path), $path);
        $md5 = $file->md5();
        $size = $file->getsize();
        if (Validate::is($file->getMime(), '/^image\//') && $this->water(public_path().$path)) { // 生成水印成功后，获取新的路径
            $file = new File(public_path().$path);
            $path = $this->getFileName($file);
            $md5 = $file->md5();
            $size = $file->getsize();
            $file = $file->move(dirname(public_path().$path), $path);
        }

        $attr = Attachment::where(['path'=>$path,'storage'=>'local'])->find();
        $temp = [];
        if ($attr) {
            $attr = $attr->toArray();
            $attr['cdn_url'] = cdn_url($attr['path'], true);
            $infos[] = $attr;
        } else {
            $temp['title'] = Str::substr($chunkInfo['original_name'], 0, 50);
            $temp['md5'] = $md5;
            $temp['mime_type'] = $file->getMime();
            $temp['ext'] = $file->getExtension();
            $temp['size'] = $size;
            $temp['storage'] = $this->config['storage'];
            $temp['path'] = $path;
            $temp['user_type'] = $this->config['user_type'];
            $temp['user_id'] = $this->config['user_id']; // 后台用户
            $temp['cdn_url'] = cdn_url($path, true);
            $infos[] = $temp;
        }

        // 缩略图与水印
        $this->thumb($infos);

        if (!empty($temp)) {
            $bl = (new \app\admin\model\routine\Attachment)->save($temp);
            if (!$bl) {
                throw new UploadException(__('No rows added'));
            }
        }

        // 上传文件后的标签位
        hook('uploadAfter', $infos);
        return $infos;
    }

    /**
     * 分块上传
     * @param array $files 上传的文件
     * @param string $fileId 当前上传控件的唯一ID
     * @param string $fileIndex 文件序号
     * @return array 返回分块信息
     */
    public function chunk($files, $fileId, $fileIndex)
    {
        $chunkInfo = [];
        foreach ($files as $key=>$value) {
            $tmpExt = $value->getOriginalExtension();
            $sExt = explode(',',config('cms.script_ext'));
            if (in_array($tmpExt, $sExt)) {
                throw new UploadException(__('Do not allow uploading of script files'));
            }

            validate(
                [
                    'files' => [
                        // 限制文件大小(单位b)
                        'fileSize' => $this->config['chunk_size'],
                        // 限制文件后缀，多个后缀以英文逗号分割
                        'fileExt'  => $this->config['file_type']
                    ]
                ],
                [
                    'files.fileSize' => __('File cannot exceed %s', [($this->config['chunk_size']/1024/1024).'MB']),
                    'files.fileExt' => __('Unsupported file suffix'),
                ]
            )->check(['files'=>$value]);

            $name = md5($fileId);
            $value->move(app()->getRuntimePath() . 'storage'.DIRECTORY_SEPARATOR.'chunk'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR, $name.'-'.$fileIndex.'.'.$value->getOriginalExtension());

            //app()->filesystem->disk('local')->putFile('chunk', $value, function ($file) use($fileId, $fileIndex) {
            //    $name = md5($fileId);
            //    return $name.DIRECTORY_SEPARATOR.$name.'-'.$fileIndex;
            //});

            $chunkInfo['original_name'] = $value->getOriginalName();
            $chunkInfo['ext'] = $value->getOriginalExtension();
        }

        return $chunkInfo;
    }

    /**
     * 普通文件上传
     * @param array $files 文件上传数组
     * @return array 文件上传
     */
    public function upload($files)
    {
        $add = [];
        $infos = [];
        foreach ($files as $key=>$value) {
            $tmpExt = $value->getOriginalExtension();
            $sExt = explode(',',config('cms.script_ext'));
            if (in_array($tmpExt, $sExt)) {
                throw new UploadException(__('Do not allow uploading of script files'));
            }

            validate(
                [
                    'files' => [
                        // 限制文件大小(单位b)
                        'fileSize' => $this->config['file_size'],
                        // 限制文件后缀，多个后缀以英文逗号分割
                        'fileExt'  => $this->config['file_type']
                    ]
                ],
                [
                    'files.fileSize' => __('File cannot exceed %s', [($this->config['file_size']/1024/1024).'MB']),
                    'files.fileExt' => __('Unsupported file suffix'),
                ]
            )->check(['files'=>$value]);


            $name = $this->getFileName($value);
            $value->move(dirname(public_path().$name), $name);

            $fileInfo = new File(public_path().$name);
            $md5 = $fileInfo->md5();
            $size = $fileInfo->getsize();
            if (Validate::is($value->getOriginalMime(), '/^image\//') && $this->water(public_path().$name)) { // 生成水印成功后，获取新的路径
                $fileInfo = new File(public_path().$name);
                $name = $this->getFileName($fileInfo);
                $md5 = $fileInfo->md5();
                $size = $fileInfo->getsize();
                $fileInfo->move(dirname(public_path().$name), $name);
            }

            //$path = app()->filesystem->disk('public')->putFile('', $value, function ($file) use($name) {
            //    return str_replace('.'.$file->getOriginalExtension(), '', $name);
            //});
            //if (!$path) {
            //    throw new UploadException(__('File save failed'));
            //}

            $attr = Attachment::where(['path'=>$name,'storage'=>'local'])->find();
            if ($attr) {
                $attr = $attr->toArray();
                $attr['cdn_url'] = cdn_url($attr['path'], true);
                $infos[] = $attr;
            } else {
                $temp['title'] = Str::substr($value->getOriginalName(), 0, 40);
                $temp['md5'] = $md5;
                $temp['mime_type'] = $value->getOriginalMime();
                $temp['ext'] = $value->getOriginalExtension();
                $temp['size'] = $size;
                $temp['storage'] = $this->config['storage'];
                $temp['path'] = $name;
                $temp['user_type'] = $this->config['user_type'];
                $temp['user_id'] = $this->config['user_id']; // 后台用户
                $temp['cdn_url'] = cdn_url($name, true);
                $add[] = $temp;
                $infos[] = $temp;
            }
        }

        // 缩略图
        $this->thumb($infos);

        if (!empty($add)) {
            $bl = (new \app\admin\model\routine\Attachment)->saveAll($add);
            if (!$bl) {
                throw new UploadException(__('No rows added'));
            }
        }

        // 上传文件后的标签位
        hook('uploadAfter', $infos);
        return $infos;
    }

    /**
     * 生成保存文件路径
     * @param \think\file\UploadedFile $file
     * @param  $md5
     * @param  $sha1
     * @param  $suffix
     * @return string
     */
    public function getFileName($file, $md5 = '', $sha1 = '', $suffix = '')
    {
        $var = [
            '{year}'=> date('Y'),
            '{month}'=> date('m'),
            '{day}'=> date('d'),
            '{hour}'=> date('H'),
            '{minute}'=> date('i'),
            '{second}'=> date('s'),
            '{md5}'=> $md5?$md5:$file->md5(),
            '{sha1}'=> $sha1?$sha1:$file->sha1(),
            '{random}'=> get_random_str(16),
            '{suffix}'=> $suffix?$suffix:'.'.$file->extension(),
        ];

        return str_replace(array_keys($var),array_values($var), $this->config['savename']);
    }

    /**
     * 处理水印与缩略图
     * @param $param
     * @return bool
     * @throws UploadException
     */
    public function waterOrThumb($param)
    {
        $config = site();
        $path = public_path();
        if ($config['water_on']==1) { // 开启水印
            foreach ($param as $value) {
                $imgPath = str_replace('\\','/', $path.ltrim($value['path'],'/'));
                try {
                    $image = Image::open($imgPath);
                } catch (\Exception $exception) {
                    continue;
                }
                // 返回图片的宽度
                $width = $image->width();
                // 返回图片的高度
                $height = $image->height();

                // 高度判断
                if ($config['water_width']>$width || $config['water_height']>$height) {
                    continue;
                }

                if ($config['water_type']==1) { // 图片
                    $waterImgPath = str_replace('\\','/', $path.ltrim($config['water_img'],'/'));

                    try { // 图片水印
                        $image->water($waterImgPath, $config['water_img_position'], $config['water_img_opacity'])->save($imgPath);
                    } catch (\Exception $exception) {
                        throw new UploadException($exception->getMessage());
                    }
                } else if ($config['water_type']==2) { // 文字
                    try { // 文字水印
                        $font = str_replace('\\','/', root_path('extend/libs/captcha/assets/zhttfs').'1.ttf');
                        $image->text($config['water_text'], $font, $config['water_text_size'], $config['water_text_color'], $config['water_img_position'])->save($imgPath);
                    } catch (\Exception $exception) {
                        throw new UploadException($exception->getMessage());
                    }
                }
            }
        }

        // 缩略图
        if ($config['thumb_on']==1) {
            $add = [];
            foreach ($param as $value) {
                if (!Validate::is($value['mime_type'], '/^image\//')) {
                    continue;
                }
                $imgPath = str_replace('\\','/', $path.ltrim($value['path'],'/'));
                try {
                    $image = Image::open($imgPath);
                } catch (\Exception $exception) {
                    continue;
                }
                // 返回图片的宽度
                $width = $image->width();
                // 返回图片的高度
                $height = $image->height();
                // 高度判断
                if ($config['thumb_width']>$width || $config['thumb_height']>$height) {
                    continue;
                }

                $save = dirname($imgPath).'/'.basename($imgPath,'.'.$value['ext']).'_thumb.'.$value['ext'];
                $image->thumb($config['thumb_width'], $config['thumb_height'], $config['thumb_type'])->save($save);

                $value['path'] = dirname($value['path']).'/'.basename($value['path'],'.'.$value['ext']).'_thumb.'.$value['ext'];
                // 保存到数据库
                if (!Db::name('attachment')->where(['path'=>$value['path'],'storage'=>'local'])->find()) {
                    $value['md5'] = md5($save);
                    $value['size'] = filesize($save);
                    $value['title'] = $value['title'].'[缩略图]';
                    unset($value['update_time']);
                    unset($value['create_time']);
                    unset($value['id']);
                    $add[] = $value;
                }
            }

            if ($add) {
                (new \app\admin\model\routine\Attachment)->saveAll($add);
            }
        }
    }

    /**
     * 处理缩略图
     * @param $param
     * @return bool
     */
    public function thumb($param)
    {
        // 缩略图
        $config = site();
        $path = public_path();
        if ($config['thumb_on']==1) {
            $add = [];
            foreach ($param as $value) {
                if (!Validate::is($value['mime_type'], '/^image\//')) {
                    continue;
                }
                $imgPath = str_replace('\\','/', $path.ltrim($value['path'],'/'));
                try {
                    $image = Image::open($imgPath);
                } catch (\Exception $exception) {
                    continue;
                }
                // 返回图片的宽度
                $width = $image->width();
                // 返回图片的高度
                $height = $image->height();
                // 高度判断
                if ($config['thumb_width']>$width || $config['thumb_height']>$height) {
                    continue;
                }

                $save = dirname($imgPath).'/'.basename($imgPath,'.'.$value['ext']).'_thumb.'.$value['ext'];
                $image->thumb($config['thumb_width'], $config['thumb_height'], $config['thumb_type'])->save($save);

                $value['path'] = dirname($value['path']).'/'.basename($value['path'],'.'.$value['ext']).'_thumb.'.$value['ext'];
                // 保存到数据库
                if (!Db::name('attachment')->where(['path'=>$value['path'],'storage'=>'local'])->find()) {
                    $value['md5'] = md5($save);
                    $value['size'] = filesize($save);
                    $value['title'] = $value['title'].'[缩略图]';
                    unset($value['update_time']);
                    unset($value['create_time']);
                    unset($value['id']);
                    $add[] = $value;
                }
            }

            if ($add) {
                (new \app\admin\model\routine\Attachment)->saveAll($add);
            }
        }
    }

    /**
     * 处理水印与缩略图
     * @param $param
     * @return bool
     * @throws UploadException
     */
    public function water($path)
    {
        $config = site();
        if ($config['water_on']==1) { // 开启水印
            $imgPath = str_replace('\\','/', $path);
            try {
                $image = Image::open($imgPath);
            } catch (\Exception $exception) {
                \think\facade\Log::log('error','水印生成失败：'.$exception->getMessage());
                return false;
            }
            // 返回图片的宽度
            $width = $image->width();
            // 返回图片的高度
            $height = $image->height();

            // 高度判断
            if ($config['water_width']>$width || $config['water_height']>$height) {
                return false;
            }

            if ($config['water_type']==1) { // 图片
                $waterImgPath = str_replace('\\','/', public_path().ltrim($config['water_img'],'/'));

                try { // 图片水印
                    $image->water($waterImgPath, $config['water_img_position'], $config['water_img_opacity'])->save($imgPath);
                    return true;
                } catch (\Exception $exception) {
                    throw new UploadException($exception->getMessage());
                }
            } else if ($config['water_type']==2) { // 文字
                try { // 文字水印
                    $font = str_replace('\\','/', root_path('extend/libs/captcha/assets/zhttfs').'1.ttf');
                    $image->text($config['water_text'], $font, $config['water_text_size'], $config['water_text_color'], $config['water_img_position'])->save($imgPath);
                    return true;
                } catch (\Exception $exception) {
                    throw new UploadException($exception->getMessage());
                }
            }
        }
        return false;
    }
}