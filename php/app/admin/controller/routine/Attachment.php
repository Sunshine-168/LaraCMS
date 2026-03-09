<?php
// +----------------------------------------------------------------------
// | HkCms 附件管理
// +----------------------------------------------------------------------
// | Copyright (c) 2020-2021 http://www.hkcms.cn, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 广州恒企教育科技有限公司 <admin@hkcms.cn>
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\controller\routine;

use app\admin\controller\BaseController;
use app\common\exception\UploadException;
use app\common\library\Upload;
use app\common\services\config\ConfigService;
use GuzzleHttp\Exception\ClientException;
use \League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use think\facade\Cache;
use think\facade\Validate;
use think\helper\Str;

class Attachment extends BaseController
{
    protected $middleware = [
        'login',
        'auth' => ['except'=>['select','download','editTitle','getTitle']]
    ];

    /**
     * @var \app\admin\model\routine\Attachment
     */
    protected $model;

    public function initialize()
    {
        parent::initialize();

        $this->model = new \app\admin\model\routine\Attachment;
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            list($map, $limit, $offset, $order, $sort) = $this->buildparams();

            $data = $this->model->where($map)->order($sort, $order)->limit($offset, $limit)->select()->append(['size_text','user_name','cdn_url'])->toArray();
            $total = $this->model->where($map)->order($sort, $order)->count();
            return json(['total'=>$total,'rows'=>$data]);
        }

        $ext = $this->model->group('ext')->column('ext');
        return $this->view->fetch('', compact('ext'));
    }

    /**
     * 文件选择
     */
    public function select()
    {
        if ($this->request->isAjax()) {
            list($map, $limit, $offset, $order, $sort) = $this->buildparams();

            $data = $this->model->where($map)->order($sort, $order)->limit($offset, $limit)->select()->append(['size_text','user_name','cdn_url'])->toArray();
            $total = $this->model->where($map)->order($sort, $order)->count();
            return json(['total'=>$total,'rows'=>$data]);
        }
        $param = $this->request->param();
        $this->view->assign($param);
        $this->view->assign('ext',$this->model->group('ext')->column('ext'));
        return $this->view->fetch();
    }

    /**
     * 远程文件下载
     */
    public function download()
    {
        if (!has_rule('common/upload')) {
            $this->error(__('No permission'));
        }

        $url = $this->request->post('url');
        if (empty($url)) {
            $this->error(__('Please enter the remote attachment address'));
        }
        if (!Validate::is($url, 'url')) {
            $this->error(__('URL is malformed'));
        }

        // 获取url头部信息
        if(version_compare(PHP_VERSION,'8.0.0','<')) {
            $head = get_headers($url,1);
        } else {
            $head = get_headers($url,true);
        }
        $headTmp = [];
        foreach ($head as $key => $value) {
            if($key != '0') $key = ucwords($key,'-');
            $headTmp[$key] = $value;
        }
        $head = $headTmp;
        if (!isset($head['Content-Type']) || !stristr($head[0],'200')) {
            $this->error(__('Failed to obtain file header information, please change other URLs'));
        }
        // 文件大小判断
        //if (!isset($head['Content-Length'])) {
        //    $this->error(__('Failed to get file size information, please change to another URL'));
        //}
        if (isset($head['Content-Length']) && $head['Content-Length']>site('file_size')) {
            $this->error(__('File cannot exceed %s', [(site('file_size')/1024/1024).'MB']));
        }

        // 解析url
        $pu = parse_url($url);
        // 获取文件后缀
        $ext = '';
        if (isset($pu['path'])) {
            $ext = strtolower(ltrim((string)strrchr($pu['path'],'.'),'.'));
        }
        $to = getExtToMime($head['Content-Type'],'mime');
        if ($to && $ext && in_array($ext, $to)) {
            $to = [$ext];
        }
        // 文件后缀判断是否允许的格式
        $sExt = explode(',',config('cms.script_ext'));
        $ft = explode(',',site('file_type'));
        foreach ($to as $key=>$value) {
            if (in_array($value, $sExt)) {
                $this->error(__('Do not allow uploading of script files'));
            }
            if (!in_array($value,$ft)) {
                $this->error(__('Unsupported file suffix'));
            }
        }
        if (empty($to)) {
            $this->error(__('Failed to get file extension'));
        }

        // 文件下载
        $client = new \GuzzleHttp\Client([
            'headers' => []
        ]);
        try {
            $response = $client->request('get', $url);
            $content = $response->getBody()->getContents();
        }  catch (ClientException $exception) {
            $this->error($exception->getMessage());
        }  catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
        if ($response->getStatusCode()!=200) {
            $this->error(__('Operation failed'));
        }

        // 文件名
        $filename = isset($pu['path']) ? basename($pu['path'],$to[0]).'.'.$to[0] : md5((string)time());
        $filename = str_replace('..','.',$filename);
        // 保存路径
        $zip = runtime_path().$filename;
        if (file_exists($zip)) {
            @unlink($zip);
        }
        $w = fopen($zip, 'w');
        fwrite($w, $content);
        fclose($w);

        $adapter = new Local(runtime_path());
        $filesystem = new Filesystem($adapter);
        $mimetype = $filesystem->mimeType($filename);

        $info = new \SplFileInfo($zip);
        if ($info->getSize()>site('file_size')) {
            @unlink($zip);
            $this->error(__('File cannot exceed %s', [(site('file_size')/1024/1024).'MB']));
        }
        $md5 = md5_file($zip);
        $sha1 = sha1_file($zip);

        // 生成访问路径
        $name = Upload::instance()->getFileName(null, $md5, $sha1,'.'.$info->getExtension());

        // 查询是否存在
        $attr = \app\admin\model\routine\Attachment::where(['path'=>$name,'storage'=>'local'])->find();
        if ($attr) {
            $attr = $attr->toArray();
            $attr['cdn_url'] = cdn_url($attr['path'], true);
            $temp = $attr;
        } else {
            $temp['title'] = Str::substr($filename, 0, 50);
            $temp['md5'] = $md5;
            $temp['mime_type'] = $mimetype;
            $temp['ext'] = $info->getExtension();
            $temp['size'] = $info->getSize();
            $temp['storage'] = 'local';
            $temp['path'] = $name;
            $temp['user_type'] = 1;
            $temp['user_id'] = $this->user->id; // 后台用户
            $temp['cdn_url'] = cdn_url($name, true);
            $bl = (new \app\admin\model\routine\Attachment)->save($temp);
            if (!$bl) {
                throw new UploadException(__('No rows added'));
            }
        }

        // 移动文件
        $path = str_replace('\\','/', public_path().ltrim($name, '/'));
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path));
        }
        rename($zip, $path);

        // 上传文件后的标签位
        hook('uploadAfter', $temp);

        $this->success();
    }

    /**
     * 水印
     * @return string|void
     */
    public function water()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row');
            foreach ($row as $key=>$value) {
                \app\admin\model\routine\Config::where(['group'=>'water','name'=>$key])->update(['value'=>$value]);
            }
            Cache::tag(ConfigService::CACHE_TAG)->clear();
            $this->success();
        }
        $config = \app\admin\model\routine\Config::where(['group'=>'water'])->select()->toArray();
        $data = [];
        foreach ($config as $key=>$value) {
            $data[$value['name']] = $value['value'];
        }
        $this->view->assign('sdata',$data);
        return $this->view->fetch();
    }

    /**
     * 生成缩略图
     * @return string|void
     */
    public function thumb()
    {
        if ($this->request->isPost()) {
            $row = $this->request->post('row');
            foreach ($row as $key=>$value) {
                \app\admin\model\routine\Config::where(['group'=>'thumb','name'=>$key])->update(['value'=>$value]);
            }
            Cache::tag(ConfigService::CACHE_TAG)->clear();
            $this->success();
        }
        $config = \app\admin\model\routine\Config::where(['group'=>'thumb'])->select()->toArray();
        $data = [];
        foreach ($config as $key=>$value) {
            $data[$value['name']] = $value['value'];
        }
        $this->view->assign('sdata',$data);
        return $this->view->fetch();
    }

    /**
     * 修改标题
     */
    public function editTitle()
    {
        $path = $this->request->post('path','');
        $title = $this->request->post('title','');
        if (empty($path) || empty($title)) {
            $this->error(__('Illegal request'));
        }

        $this->model->where(['path'=>$path])->save(['title'=>trim($title)]);
        $this->success();
    }

    /**
     * 获取附件列表
     */
    public function getTitle()
    {
        $path = $this->request->post('paths','');
        if (empty($path)) {
            $this->error(__('Illegal request'));
        }
        $path = explode(',', $path);
        $data = $this->model->whereIn('path',$path)->select();
        $this->success('','',$data);
    }
}