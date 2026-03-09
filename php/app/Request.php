<?php
namespace app;

use libs\Macroable;
use think\facade\Config;

/**
 * 请求类，可自定义方法或者覆盖已有方法
 * Class Request
 * @package app
 */
class Request extends \think\Request
{
    use Macroable;

    /**
     * 全局请求过滤，protected $filter = ['strip_tags'];
     * @var array
     */
    protected $filter = [];

    /**
     * 生成请求令牌
     * @access public
     * @param  string $name 令牌名称
     * @param  mixed  $type 令牌生成方法
     * @return string
     */
    public function buildToken(string $name = '__token__', $type = 'md5'): string
    {
        $type  = is_callable($type) ? $type : 'md5';
        $token = call_user_func($type, $this->server('REQUEST_TIME_FLOAT'));

        if ($this->isAjax()) {
            header($name . ': ' . $token);
        }
        $this->session->set($name, $token);
        return $token;
    }

    /**
     * 获取请求客户端类型
     * @return string
     */
    public function getFormClient(): string
    {
        $clientType = strtolower($this->header('X-Form-Client', 'h5'));
        $config = Config::get('jwt');
        if (isset($config['client'][$clientType])) {
            return $clientType;
        }
        return $config['default'];
    }
}
