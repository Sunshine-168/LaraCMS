<?php
/**
 * +----------------------------------------------------------------------
 * | think-addons [thinkphp6]
 * +----------------------------------------------------------------------
 *  .--,       .--,             | FILE: Route.php
 * ( (  \.---./  ) )            | AUTHOR: byron
 *  '.__/o   o\__.'             | EMAIL: xiaobo.sun@qq.com
 *     {=  ^  =}                | QQ: 150093589
 *     /       \                | DATETIME: 2019/11/5 09:57
 *    //       \\               |
 *   //|   .   |\\              |
 *   "'\       /'"_.-~^`'-.     |
 *      \  _  /--'         `    |
 *    ___)( )(___               |-----------------------------------------
 *   (((__) (__)))              | 高山仰止,景行行止.虽不能至,心向往之。
 * +----------------------------------------------------------------------
 * | Copyright (c) 2019 http://www.zzstudio.net All rights reserved.
 * +----------------------------------------------------------------------
 */
declare(strict_types=1);

namespace think\addons;

use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use think\helper\Str;
use think\facade\Event;
use think\facade\Config;
use think\exception\HttpException;
use ReflectionClass;
use think\Response;
use ReflectionMethod;

class Route
{
    /**
     * 插件路由请求
     * @param null $addon
     * @param null $controller
     * @param null $action
     * @return mixed
     */
    public static function execute($addon = null, $controller = null, $action = null)
    {
        $app = app();
        $request = $app->request;
        self::loadFile(empty($addon)?'':$addon);

        Event::trigger('addonsBegin', $request);

        // 默认控制器Index，默认操作方法index
        $controller = empty($controller) ? 'Index' : $controller;
        $action = empty($action) ? 'index' : $action;
        if (empty($addon) || empty($controller) || empty($action)) {
            throw new HttpException(500, lang('Addon can not be empty'));
        }

        $request->addon = $addon;
        // 设置当前请求的控制器、操作
        $request->setController($controller)->setAction($action);

        // 获取插件基础信息
        $info = get_addons_info($addon);
        if (!$info) {
            throw new HttpException(404, lang('Addon %s not found', [$addon]));
        }
        if ($info['status']==-1) {
            throw new HttpException(500, lang('Addon %s is disabled', [$addon]));
        } else if ($info['status']==0) {
            throw new HttpException(404, lang('Addon %s not found', [$addon]));
        }

        // 监听addon_module_init
        Event::trigger('addonModuleInit', $request);
        $class = get_addons_class($addon, 'controller', $controller);
        if (!$class) {
            throw new HttpException(404, lang('Addon controller %s not found', [Str::studly($controller)]));
        }

        // 重写视图基础路径
        $config = Config::get('view');
        $config['view_path'] = $app->addons->getAddonsPath() . $addon . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        Config::set($config, 'view');

        // 生成控制器对象
        $instance = new $class($app);
        // 注册控制器中间件
        self::registerControllerMiddleware($instance, $action);

        return app('middleware')->pipeline('controller')->send($request)->then(function () use ($instance, $action) {
            $app = app();
            if (is_callable([$instance, $action])) {
                $vars = $app->request->param();
                try {
                    $reflect = new ReflectionMethod($instance, $action);
                    // 严格获取当前操作方法名
                    $actionName = $reflect->getName();

                    $app->request->setAction($actionName);
                } catch (ReflectionException $e) {
                    $reflect = new ReflectionMethod($instance, '__call');
                    $vars    = [$action, $vars];
                    $app->request->setAction($action);
                }
                Event::trigger('addonsActionBegin', [$instance, $action]);
            } else {
                // 操作不存在
                throw new HttpException(404, lang('Addon action %s not found', [get_class($instance).'->'.$action.'()']));
            }

            $data = $app->invokeReflectMethod($instance, $reflect, $vars);

            return self::autoResponse($data);
        });

        $vars = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$action];
        } else {
            // 操作不存在
            throw new HttpException(404, lang('Addon action %s not found', [get_class($instance).'->'.$action.'()']));
        }

        Event::trigger('addonsActionBegin', $call);

        return call_user_func_array($call, $vars);
    }

    /**
     * 使用反射机制注册控制器中间件
     * @access public
     * @param object $controller 控制器实例
     * @return void
     */
    protected static function registerControllerMiddleware($controller,$action): void
    {
        $class = new ReflectionClass($controller);

        if ($class->hasProperty('middleware')) {
            $reflectionProperty = $class->getProperty('middleware');
            $reflectionProperty->setAccessible(true);

            $middlewares = $reflectionProperty->getValue($controller);

            foreach ($middlewares as $key => $val) {
                if (!is_int($key)) {
                    $middleware = $key;
                    $options    = $val;
                } elseif (isset($val['middleware'])) {
                    $middleware = $val['middleware'];
                    $options    = $val['options'] ?? [];
                } else {
                    $middleware = $val;
                    $options    = [];
                }

                if (isset($options['only']) && !in_array($action, self::parseActions($options['only']))) {
                    continue;
                } elseif (isset($options['except']) && in_array($action, self::parseActions($options['except']))) {
                    continue;
                }

                if (is_string($middleware) && strpos($middleware, ':')) {
                    $middleware = explode(':', $middleware);
                    if (count($middleware) > 1) {
                        $middleware = [$middleware[0], array_slice($middleware, 1)];
                    }
                }

                app()->middleware->controller($middleware);
            }
        }
    }

    /**
     * 解析操作方法
     * @param $actions
     * @return array|string[]
     */
    protected static function parseActions($actions)
    {
        return array_map(function ($item) {
            return strtolower($item);
        }, is_string($actions) ? explode(",", $actions) : $actions);
    }

    /**
     * 自动识别响应数据类型
     * @param $data
     * @return Response
     */
    protected static function autoResponse($data): Response
    {
        if ($data instanceof Response) {
            $response = $data;
        } elseif ($data instanceof ResponseInterface) {
            $response = Response::create($data->getBody()->getContents(), 'html', $data->getStatusCode());

            foreach ($data->getHeaders() as $header => $values) {
                $response->header([$header => implode(", ", $values)]);
            }
        } elseif (!is_null($data)) {
            // 默认自动识别响应输出类型
            $type     = app()->request->isJson() ? 'json' : 'html';
            $response = Response::create($data, $type);
        } else {
            $data = ob_get_clean();

            $content  = false === $data ? '' : $data;
            $status   = '' === $content && app()->request->isJson() ? 204 : 200;
            $response = Response::create($content, 'html', $status);
        }

        return $response;
    }

    /**
     * 加载应用文件
     * @param string $addon 插件名
     * @return void
     */
    protected static function loadFile(string $addon): void
    {
        $appPath = app()->addons->getAddonsPath() . $addon . DIRECTORY_SEPARATOR;
        if (is_file($appPath . 'common.php')) {
            include_once $appPath . 'common.php';
        }
    }
}