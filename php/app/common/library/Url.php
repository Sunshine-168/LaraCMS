<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\common\library;

use think\App;
use think\route\Url as UrlBuild;

/**
 * 路由地址生成
 */
class Url extends UrlBuild
{
    /**
     * 直接解析URL地址
     * @access protected
     * @param  string      $url URL
     * @param  string|bool $domain Domain
     * @return string
     */
    protected function parseUrl(string $url, &$domain): string
    {
        $request = $this->app->request;

        if (0 === strpos($url, '/')) {
            // 直接作为路由地址解析
            $url = substr($url, 1);
        } elseif (false !== strpos($url, '\\')) {
            // 解析到类
            $url = ltrim(str_replace('\\', '/', $url), '/');
        } elseif (0 === strpos($url, '@')) {
            // 解析到控制器
            $url = substr($url, 1);
        } elseif ('' === $url) {
            $url = $request->controller() . '/' . $request->action();
            if (!$this->app->http->isBind()) {
                $url = $this->getAppName() . '/' . $url;
            }
        } else {
            // 解析到 应用/控制器/操作
            $controller = $request->controller();
            $path       = explode('/', $url);
            $action     = array_pop($path);
            $controller = empty($path) ? $controller : array_pop($path);
            $app        = empty($path) ? $this->getAppName() : array_pop($path);
            $url        = $controller . '/' . $action;
            $bind       = $this->app->config->get('app.domain_bind', []);

            if ($key = array_search($this->app->http->getName(), $bind)) {
                isset($bind[$_SERVER['SERVER_NAME']]) && $domain = $_SERVER['SERVER_NAME'];

//                     $domain = is_bool($domain) ? $key : $domain;
                // luo：增加对*泛域名的处理
                if ($domain===true && $key!='*') {
                    $domain = $key;
                } else if ($domain===true && $key=='*') {
                    $subDomain = $this->app->request->subDomain();
                    $domain = $subDomain?:$this->app->request->rootDomain();
                }
            } elseif (!$this->app->http->isBind()) {
                $url = $app . '/' . $url;
            }
        }

        return $url;
    }

    public function build(): string
    {
        // 解析URL
        $url     = $this->url;
        $suffix  = $this->suffix;
        $domain  = $this->domain;
        $request = $this->app->request;
        $vars    = $this->vars;
        $isBindMod = !empty($domain) && is_string($domain);

        // 生成其他应用链接、url('index:/index/login.html')
        $toApp = '';
        if (false !== strpos($url, ':')) {
            $tmpArr = explode(':', $url);
            $appFile = $this->app->config->get('app.app_file', []);
            $toApp = isset($appFile[$tmpArr[0]]) ? '/'.$appFile[$tmpArr[0]].'.php':'/index.php';
            $url = $tmpArr[1];
        }

        if (str_starts_with($url, '[') && $pos = strpos($url, ']')) {
            // [name] 表示使用路由命名标识生成URL
            $name = substr($url, 1, $pos - 1);
            $url  = 'name' . substr($url, $pos + 1);
        }

        if (!str_contains($url, '://') && !str_starts_with($url, '/')) {
            $info = parse_url($url);
            $url  = !empty($info['path']) ? $info['path'] : '';

            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];

                if (str_contains($anchor, '?')) {
                    // 解析参数
                    [$anchor, $info['query']] = explode('?', $anchor, 2);
                }

                if (str_contains($anchor, '@')) {
                    // 解析域名
                    [$anchor, $domain] = explode('@', $anchor, 2);
                }
            } elseif (str_contains($url, '@') && !str_contains($url, '\\')) {
                // 解析域名
                [$url, $domain] = explode('@', $url, 2);
            }
        }

        if ($url) {
            $checkName   = isset($name) ? $name : $url . (isset($info['query']) ? '?' . $info['query'] : '');
            $checkDomain = $domain && is_string($domain) ? $domain : null;

            $rule = $this->route->getName($checkName, $checkDomain);

            if (empty($rule) && isset($info['query'])) {
                $rule = $this->route->getName($url, $checkDomain);
                // 解析地址里面参数 合并到vars
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
                unset($info['query']);
            }
        }

        if (!empty($rule) && $match = $this->getRuleUrl($rule, $vars, $domain)) {
            // 匹配路由命名标识
            $url = $match[0];

            if ($domain && !empty($match[1])) {
                $domain = $match[1];
            }

            if (!is_null($match[2])) {
                $suffix = $match[2];
            }

            if (!$this->app->http->isBind()) {
                $app = $this->getAppName();
                $url = $app . '/' . $url;
            }
        } elseif (!empty($rule) && isset($name)) {
            throw new \InvalidArgumentException('route name not exists:' . $name);
        } else {
            // 检测URL绑定
            $bind = $this->route->getDomainBind($domain && is_string($domain) ? $domain : null);

            if ($bind && str_starts_with($url, $bind)) {
                $url = substr($url, strlen($bind) + 1);
            }
            // else {
            //     $binds = $this->route->getBind();
            //
            //     foreach ($binds as $key => $val) {
            //         if (is_string($val) && 0 === strpos($url, $val) && substr_count($val, '/') > 1) {
            //             $url    = substr($url, strlen($val) + 1);
            //             $domain = $key;
            //             break;
            //         }
            //     }
            // }

            // 路由标识不存在 直接解析
            $url = $this->parseUrl($url, $domain);

            if (isset($info['query'])) {
                // 解析地址里面参数 合并到vars
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
            }
        }

        // 还原URL分隔符
        $depr = $this->route->config('pathinfo_depr');
        $url  = str_replace('/', $depr, $url);

        // 对入口文件的处理
        $file = $toApp?:$request->baseFile();
        $tmpUrl = $request->url();
        // 是否显示入口文件index.php文件
        $siteFile = site('url_mode')==0 ? site('url_file') : 0;
        if ($file && $siteFile!=1 && empty($toApp)) { // 应用内、域名绑定访问、去掉入口文件，
            // 获取域名应用绑定
            $domainBind = $this->app->config->get('app.domain_bind', []);
            // 子域名
            $subDomain = $request->subDomain();
            // 域名
            $tDomain = $request->host(true);
            // 域名+端口
            $tDomainP = $request->host();

            if (isset($domainBind[$domain]) && 0 === strpos($tmpUrl, $file) && $file!='/index.php') {
                $domain = $subDomain;
            }
            if ($isBindMod || isset($domainBind[$domain]) || isset($domainBind[$subDomain]) || isset($domainBind[$tDomain]) || isset($domainBind[$tDomainP]) || ($file=='/index.php' && $this->app->http->getName()=='index') ) { // 当前使用的是域名绑定访问，正常删除入口文件
                $file = str_replace('\\', '/', dirname($file));
            }
        } else if ($file && !empty($toApp)) { // 生成其他应用链接、入口文件形式
            // 获取域名应用绑定
            $domainBind = $this->app->config->get('app.domain_bind', []);
            // 入口文件绑定
            $appFile = $this->app->config->get('app.app_file', []);
            // 子域名跟入口文件不一致时使用当前主域名
            if (!is_bool($domain) && isset($domainBind[$domain]) && isset($appFile[$domainBind[$domain]]) && '/'.$appFile[$domainBind[$domain]].'.php'!=$toApp) {
                $domain = $request->subDomain();
            }
        }

        $url = rtrim($file, '/') . '/' . ltrim($url, '/');

        // URL后缀
        if ('/' == substr($url, -1) || '' == $url) {
            $suffix = '';
        } else {
            $suffix = $this->parseSuffix($suffix);
        }

        // 锚点
        $anchor = !empty($anchor) ? '#' . $anchor : '';

        // 参数组装
        if (!empty($vars)) {
            // 添加参数
            if ($this->route->config('url_common_param')) {
                $vars = http_build_query($vars);
                $url .= $suffix . '?' . $vars . $anchor;
            } else {
                foreach ($vars as $var => $val) {
                    $val = (string) $val;
                    if ('' !== $val) {
                        $url .= $depr . $var . $depr . urlencode($val);
                    }
                }

                $url .= $suffix . $anchor;
            }
        } else {
            $url .= $suffix . $anchor;
        }

        // 检测域名
        $domain = $this->parseDomain($url, $domain);

        // URL组装
        return $domain . rtrim($this->root, '/') . '/' . ltrim($url, '/');
    }

    /**
     * 匹配路由地址，重写父类getRuleUrl方式，原因：在生成域名如：local，有端口的情况下会导致添加两个端口
     * @access protected
     * @param  array $rule 路由规则
     * @param  array $vars 路由变量
     * @param  mixed $allowDomain 允许域名
     * @return array
     */
    protected function getRuleUrl(array $rule, array &$vars = [], $allowDomain = ''): array
    {
        $request = $this->app->request;
        if (is_string($allowDomain) && false === strpos($allowDomain, '.')) {
            $allowDomain .= '.' . $request->rootDomain();
            $allowDomain = explode(':',$allowDomain)[0]; // luo修改，去掉端口。
        }
        $port = $request->port();

        foreach ($rule as $item) {
            $url     = $item['rule'];
            $pattern = $this->parseVar($url);
            $domain  = $item['domain'];
            $suffix  = $item['suffix'];

            if ('-' == $domain) {
                $domain = is_string($allowDomain) ? $allowDomain : $request->host(true);
            }

            if (is_string($allowDomain) && $domain != $allowDomain) {
                continue;
            }

            if ($port && !in_array($port, [80, 443])) {
                $domain .= ':' . $port;
            }

            if (empty($pattern)) {
                return [rtrim($url, '?-'), $domain, $suffix];
            }

            $type = $this->route->config('url_common_param');
            $keys = [];

            foreach ($pattern as $key => $val) {
                if (isset($vars[$key])) {
                    $url    = str_replace(['[:' . $key . ']', '<' . $key . '?>', ':' . $key, '<' . $key . '>'], $type ? (string) $vars[$key] : urlencode((string) $vars[$key]), $url);
                    $keys[] = $key;
                    $url    = str_replace(['/?', '-?'], ['/', '-'], $url);
                    $result = [rtrim($url, '?-'), $domain, $suffix];
                } elseif (2 == $val) {
                    $url    = str_replace(['/[:' . $key . ']', '[:' . $key . ']', '<' . $key . '?>'], '', $url);
                    $url    = str_replace(['/?', '-?'], ['/', '-'], $url);
                    $result = [rtrim($url, '?-'), $domain, $suffix];
                } else {
                    $result = null;
                    $keys   = [];
                    break;
                }
            }

            $vars = array_diff_key($vars, array_flip($keys));

            if (isset($result)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * 获取URL的应用名
     * @access protected
     * @return string
     */
    protected function getAppName()
    {
        $app = $this->app->http->getName();
        $map = $this->app->config->get('app.app_map', []);

        if ($key = array_search($app, $map)) {
            $app = $key;
        }

        return $app;
    }
}
