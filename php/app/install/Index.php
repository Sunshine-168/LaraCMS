<?php

    use GuzzleHttp\Client;
    use think\facade\Db;
    // 获取版本信息
    $ver = include ROOT_PATH.'config/ver.php';

    $Title   = $ver['cms_app'];
    $Powered = 'Powered by hkcms.cn';
    $version = $ver['cms_version'];
    $cms_build = $ver['cms_build'];

    $step = isset($_GET['step']) ? intval($_GET['step']) : 1;

    // 检测目录权限
    function dirTestWrite($path)
    {
        if ($fp = @fopen($path.'demo.txt','w')) {
            @fclose($fp);
            @unlink($path.'demo.txt');
            return true;
        }
        return false;
    }
    // 数据表前缀替换
    function sql_split($sql, $tablepre)
    {
        if ($tablepre != "hkcms_")
            $sql = str_replace("hkcms_", $tablepre, $sql);
        $sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8", $sql);

        $sql = str_replace("\r", "\n", $sql);
        $ret = array();
        $num = 0;
        $queriesarray = explode(";\n", trim($sql));
        unset($sql);
        foreach ($queriesarray as $query) {
            $ret[$num] = '';
            $queries = explode("\n", trim($query));
            $queries = array_filter($queries);
            foreach ($queries as $query) {
                $str1 = substr($query, 0, 1);
                if ($str1 != '#' && $str1 != '-')
                    $ret[$num] .= $query;
            }
            $num++;
        }
        return $ret;
    }
    // 获取客户端IP
    function get_client_ip()
    {
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            return '0.0.0.0';
        }
        $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        return $ip;
    }
    // 生成随机数
    function get_random_str($len = 6)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );
        $charsLen = count($chars) - 1;
        // 将数组打乱
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }

    // 安装数据库
    if (isset($_GET['action']) && $_GET['action']=='mysql') {
        header('Content-Type: application/json; charset=UTF-8');

        $res = ['code'=>-1000];
        if (empty($_POST['row'])) {
            $res['msg'] = '请求的信息不存在~';
            die(json_encode($res));
        }

        $row = $_POST['row'];
        $row['dbprefix'] = !empty($row['dbprefix']) ? trim($row['dbprefix']) : 'hkcms_';
        $row['sitename'] = !empty($row['sitename']) ? trim($row['sitename']) : 'HkCms开源内容管理系统';
        $row['sitekeywords'] = !empty($row['sitekeywords']) ? trim($row['sitekeywords']) : '开源免费、免授权、永久商用、开箱即用';
        $row['siteinfo'] = !empty($row['siteinfo']) ? trim($row['siteinfo']) : 'HkCms开源内容管理系统是一款基于ThinkPHP6.0开发的CMS系统。以免授权、永久商用、系统易安装升级、界面功能简洁轻便、易上手、插件与模板在线升级安装、建站联盟扶持计划等优势为一体的CMS系统。';
        $count = $_GET['n'] ?? 0;

        $config = [
            // 默认数据连接标识
            'default'     => 'hkcms',
            // 数据库连接信息
            'connections' => [
                'mysql' => [
                    // 数据库类型
                    'type'     => 'mysql',
                    // 主机地址
                    'hostname' => trim($row['dbhost']),
                    // 端口
                    'hostport' => trim($row['dbport']),
                    // 用户名
                    'username' => trim($row['dbuser']),
                    // 密码
                    'password' => trim($row['dbpw']),
                    // 数据库调试模式
                    'debug'    => true,
                ],
            ],
        ];
        $config['connections']['hkcms'] = $config['connections']['mysql'];
        $config['connections']['hkcms']['database'] = trim($row['dbname']);

        Db::setConfig($config);

        // 连接数据库并设置UTF8
        try {
            Db::connect('mysql')->query("SET NAMES 'utf8'");
        } catch (\Exception $exception) {
            $res['msg'] = '连接数据库失败：'.$exception->getMessage();
            die(json_encode($res));
        }

        if ($count==0) {
            $bl = Db::connect('mysql')->query("SHOW DATABASES LIKE '{$row['dbname']}'");
            if (empty($bl)) {
                // 创建数据库
                try {
                    Db::connect('mysql')->query("CREATE DATABASE IF NOT EXISTS `{$row['dbname']}` DEFAULT CHARACTER SET utf8;");
                } catch (\Exception $exception) {
                    $res['msg'] = '数据库 ' . $row['dbname'] . ' 不存在，也没权限创建新的数据库！错误信息：'.$exception->getMessage();
                    die(json_encode($res));
                }
                $res['n'] = 1;
                $res['msg'] = "成功创建数据库:{$row['dbname']}<br>";
                die(json_encode($res));
            }
        }

        //读取数据文件
        $dataPath = ROOT_PATH.'app'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;
        $sqlData = file_get_contents($dataPath . 'hkcms.sql');
        //读取测试数据
        if (isset($row['testdata']) && $row['testdata']==1) {
            $sqldataDemo = file_get_contents($dataPath . 'hkcms_demo.sql');
            $sqlData = $sqlData . "\r\n" . $sqldataDemo;
        } else if (isset($row['testdata']) && $row['testdata']==2) {
            $sqldataDemo = file_get_contents($dataPath . 'hkcms_demo_lang.sql');
            $sqlData = $sqlData . "\r\n" . $sqldataDemo;
        }
        $sqlFormat = sql_split($sqlData, $row['dbprefix']);

        // 执行SQL语句
        $counts = count($sqlFormat);
        for ($i = $count; $i < $counts; $i++) {
            $sql = trim($sqlFormat[$i]);
            if (strstr($sql, 'CREATE TABLE')) {
                preg_match('/CREATE TABLE `([^ ]*)`/', $sql, $matches);
                try {
                    Db::query($sql);
                    $res['msg'] = '<li class="pt-1 pb-1"><span class="text-success"><i class="fa fa-check-circle"></i></span>创建数据表' . $matches[1] . '，完成</li>';
                } catch (\Exception $exception) {
                    $res['msg'] = '<li class="pt-1 pb-1"><span class="text-danger"><i class="fa fa-exclamation"></i></span>创建数据表' . $matches[1] . '，失败【错误信息：'.$exception->getMessage().'】</li>';
                }

                $i++;
                $res['n'] = $i;
                die(json_encode($res));
            } else {
                Db::query($sql);
                $res['msg'] = '';
                $res['n'] = $i;
            }
        }

        //更新配置信息
        Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = '{$row['sitename']}' WHERE name='title' and lang='zh-cn'");
        Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = '{$row['sitekeywords']}' WHERE name='keyword' and lang='zh-cn'");
        Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = '{$row['siteinfo']}' WHERE name='description' and lang='zh-cn'");
        if (isset($row['testdata']) && $row['testdata']=1) {

            Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = '{$row['sitename']}' WHERE name='title' and lang='zh-cn'");
            Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = '{$row['sitekeywords']}' WHERE name='keyword' and lang='zh-cn'");
            Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = '{$row['siteinfo']}' WHERE name='description' and lang='zh-cn'");

            Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = 'en_{$row['sitename']}' WHERE name='title' and lang='en'");
            Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = 'en_{$row['sitekeywords']}' WHERE name='keyword' and lang='en'");
            Db::query("UPDATE `{$row['dbprefix']}config` SET  `value` = 'en_{$row['siteinfo']}' WHERE name='description' and lang='en'");
        }

        // 读取配置文件，并替换真实配置数据
        $strConfig = file_get_contents( $dataPath . 'example.env');
        $strConfig = str_replace('#APP_KEY#', strtoupper(\think\helper\Str::random(32)), $strConfig);
        $strConfig = str_replace('#DB_HOST#', trim($row['dbhost']), $strConfig);
        $strConfig = str_replace('#DB_NAME#', trim($row['dbname']), $strConfig);
        $strConfig = str_replace('#DB_USER#', trim($row['dbuser']), $strConfig);
        $strConfig = str_replace('#DB_PWD#', trim($row['dbpw']), $strConfig);
        $strConfig = str_replace('#DB_PORT#', trim($row['dbport']), $strConfig);
        $strConfig = str_replace('#DB_PREFIX#', trim($row['dbprefix']), $strConfig);
        @file_put_contents(ROOT_PATH . '.env', $strConfig);

        // 生成随机认证码
        $verify = get_random_str(6);
        $time = time();
        $ip = get_client_ip();
        // 插入管理员信息
        $password = md5(sha1($row['manager_pwd']) . md5($verify));
        $query = "INSERT INTO `{$row['dbprefix']}admin` VALUES ('1', '{$row['manager']}', '{$row['manager']}', 'admin@admin.com', '{$password}', '{$verify}', '', '', '{$time}', '{$ip}', '1', '{$time}', '{$time}');";
        Db::query($query);

        $message = '<li class="pt-1 pb-1"><span class="text-success"><i class="fa fa-check-circle"></i></span> 成功添加管理员</li><li class="pt-1 pb-1"><span class="text-success"><i class="fa fa-check-circle"></i></span> 成功写入配置文件</li><li><span class="text-success"><i class="fa fa-check-circle"></i> 安装完成．．．</span></li>';
        $res['n'] = 999999;
        $res['msg'] = $message;
        die(json_encode($res));
    }

    if ($step==2) { // 权限、环境检测
        // 目录、文件权限检测
        $errorInfo = [];
        $folder = [
            '.env',
            'runtime'.DIRECTORY_SEPARATOR,
            'app'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR,
            'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR,
        ];
        foreach ($folder as $dir) {
            $path = ROOT_PATH . $dir;
            if (file_exists($path) && is_dir($path) && !dirTestWrite($path)) {
                $errorInfo[] = $path.'，要求读写权限';
            } else if (file_exists($path) && (!is_readable($path) || !is_writable($path))) {
                $errorInfo[] = $path.'，要求读写权限';
            } else if (!file_exists($path)) {
                $errorInfo[] = $path.'，不存在';
            }
        }

        // 函数检测
        if (!function_exists('chmod')) {
            $errorInfo[] = 'chmod函数已被禁用，请启用它[<a target="_blank" href="https://www.hkcms.cn/help/question/113.html">详情</a>]';
        }

    } else if ($step==3) {
        if (isset($_POST['test'])) {
            header('Content-Type: application/json; charset=UTF-8');
            // 测试数据库
            $res = ['code' => -1000, 'msg' => ''];

            $dbuser = $_POST['dbuser'] ?? '';
            $dbpw = $_POST['dbpw'] ?? '';
            $dbhost = $_POST['dbhost'] ?? '';
            $dbport = $_POST['dbport'] ?? '';
            $dbname = $_POST['dbname'] ?? '';

            Db::setConfig([
                // 默认数据连接标识
                'default'     => 'mysql',
                // 数据库连接信息
                'connections' => [
                    'mysql' => [
                        // 数据库类型
                        'type'     => 'mysql',
                        // 主机地址
                        'hostname' => $dbhost,
                        // 端口
                        'hostport' => $dbport,
                        // 用户名
                        'username' => $dbuser,
                        // 密码
                        'password' => $dbpw,
                        // 数据库调试模式
                        'debug'    => true,
                    ],
                ],
            ]);

            try {
                $version = Db::query('SELECT VERSION() as version');
                if (empty($version[0])) {
                    $res['msg'] = '无法获取数据库版本信息';
                }
                $version = $version[0]['version'];
                if (stripos($version,'MariaDB')===false && version_compare($version, '5.6','<')) {
                    $res['msg'] = 'MySql版本要求>=5.6';
                } else {
                    $res['code'] = 200;
                    $res['ver'] = $version;

                    if ($dbname) {
                        $database = Db::query('SHOW DATABASES LIKE "'.$dbname.'"');
                        if (!empty($database)) {
                            $res['msg'] = '注意：【'.$dbname.'】数据库已存在，安装将覆盖数据库';
                        }
                    }
                }
            } catch (\Exception $exception) {
                $res['msg'] = '连接失败，用户名或密码错误';
            }
            die(json_encode($res));
        }
    } else if ($step==4) {
        if (empty($_POST['row'])) {
            die("请求的信息不存在~");
        }
    } else if ($step==5) {
        $host = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } else if (!empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }
        $host = strval($host);

        try {
            $client = new Client(['base_uri' => 'http://api.hkcms.cn/']);
            $client->request('POST', 'count/add', [
                'form_params' => [
                    'type' => 1,
                    'domain' => $host,
                    'ip' => get_client_ip(),
                    'version' => $version,
                ]
            ]);
        } catch (\Throwable $exception) {}

        // 生成安装标识
        @touch(ROOT_PATH.'app'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR . 'install.lock');
        // 清理旧缓存
        $pathArr = glob(ROOT_PATH . 'runtime' . DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR | GLOB_NOSORT);
        foreach ($pathArr as $key=>$value) {
            if (in_array(basename($value), ['session'])) {
                continue;
            }
            \think\addons\Dir::instance()->delDir($value);
        }

        function env(string $name = null, $default = null){return $default;}

        // 删除COOKIE
        $lang = include ROOT_PATH.'config/lang.php';
        setCookie('index_'.$lang['cookie_var'], "", time()-60);
        setCookie('admin_'.$lang['cookie_var'], "", time()-60);
    }
?>
<!doctype html>
<html lang="zh-cn">
    <head>
        <meta charset="UTF-8" />
        <meta name="renderer" content="webkit" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?php echo $Title;?> - <?php echo $Powered;?></title>

        <link rel="stylesheet" type="text/css" href="/static/libs/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="/static/libs/fontawesome-free/css/all.min.css">
        <style>
            html {height: 100%}
            body {background-color: #f7f7f7;color:#333333;font-size: 14px;height: 100%}
            header {width: 100%;height: 80px;box-shadow: 0 3px 3px #ddd;color: #fff;background: #34d0b6;}
            header .d-flex {height: 63px}
            ul li {list-style: none}
            .text-xs {font-size: 13px}
            .version {margin-top: -8px}
            .footer {padding: 20px;display: block;text-align: center;font-size: 13px;color:#6c757d;}
            .pact-main {color:#444444;overflow-y: scroll;height: 450px;overflow-x: hidden;white-space: pre-wrap;word-wrap: break-word;background-color: #ffffff;margin: 0;padding: 8px 8px 10px 15px;}
            .pact-main p {margin: 0;line-height: 1.5}
            .pact-main p.xy {line-height: 2.2}
            .pact {width: 60%;margin: 0 auto;margin-top: 40px;min-height: calc(100% - 180px);}

            .pact .card-body {padding: 10px 0 10px 10px;background-color: #f3f3f3;}
            .submit-btn {background: #34d0b6;color: #ffffff;outline: none;width: 120px;border-radius: 25px;height: 35px;line-height: 35px;display: inline-block;font-size: 14px}
            .submit-btn:hover {background: #35c1a9;color: #ffffff;text-decoration: none}
            /*滚动条*/
            .pact-main::-webkit-scrollbar {width: 10px;height: 4px;}
            .pact-main::-webkit-scrollbar-thumb {
                border-radius: 5px;
                -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
                background: rgba(0, 0, 0, 0.1);
            }
            .pact-main::-webkit-scrollbar-track {
                -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
                border-radius: 0;
                background: rgba(234, 234, 234, 0.08);
            }
            /*进度条*/
            .progress {height: .5rem;position: absolute;width: 100%;top: 15px}
            .step {margin-bottom: 30px;position: relative}
            .step ul {padding: 0;position: relative;z-index: 99}
            .step ul li {padding: 0;float: left;list-style: none;text-align: center;width: 20%}
            .step ul li .ui-step-item-title {margin-top: 15px;font-size: 15px;color: #6c757d;}
            .step ul span {display: inline-block;background-color:#e9ecef;width: 35px;height: 35px;border-radius: 50%;line-height: 34px;color: #ffffff;font-size: 16px;font-weight: 500}
            .step ul .active span {background-color:#34d0b6;}
            .step ul .active .ui-step-item-title {color:#34d0b6;}
            .progress-bar {background-color: #34d0b6}
            .table tr th,.table tr td {text-align: center;vertical-align: middle;padding: 8px;color: #333333;}
            .table tr th {border: 1px solid #f4f4f4;border-bottom: 1px solid #ddd;padding-top: 10px;padding-bottom: 10px;background-color: #e5e5e5}
            .table tr td {text-align: center;vertical-align: middle;border: 1px solid #f4f4f4;}
            .table-hover > tbody > tr:hover {background-color: #f5f5f5;}
            .form-control {font-size: 13px;height: 33px}
            .form-control:focus {
                color: #495057;
                background-color: #fff;
                border-color: #80bdff;
                outline: 0;
                box-shadow: none;
            }
            .server {box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;border-radius: 10px;width: 50%;margin: 0 auto;margin-top: 60px}
            .server-yes-top {font-size: 18px;font-weight: bold;height: 50px;box-shadow: 0 3px 3px #ddd;position: relative;color: #fff;background: #34d0b6;text-align: center;line-height: 50px;border-top-left-radius: 10px;border-top-right-radius: 10px;}
            @media(max-width: 768px) {
                .pact {width: 99%;}
                .server {width: 99%;}
            }
            @media(min-width: 1921px) {
                .pact {width: 50%;margin-top: 50px}
                .step {margin-bottom: 45px;}
                .pact-main {height: 600px}
            }
        </style>
        <script src="/static/libs/jquery/jquery.min.js"></script>
        <script src="/static/libs/nice-validator/jquery.validator.min.js"></script>
        <script src="/static/libs/nice-validator/local/zh-cn.js"></script>
        <script src="/static/libs/layer/layer.js"></script>
    </head>
    <body>
        <header>
            <div class="container-fluid">
                <div class="d-flex justify-content-center align-items-end">
                    <span style="font-size: 20px">欢迎使用</span>
                    <div>
                        <img src="/static/install/image/logo.png">
                    </div>
                </div>
                <div class="float-right text-xs version">版本：HkCms_v<?php echo $version.'.'.$cms_build; ?></div>
            </div>
        </header>

        <?php if ($step==1): ?>
            <section class="pact">
                <div class="step">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 20%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <ul class="clearfix">
                        <li class="ui-step-item active">
                            <div class="ui-step-item-num"><span>1</span></div>
                            <div class="ui-step-item-title">使用协议</div>
                        </li>
                        <li class="ui-step-item">
                            <div class="ui-step-item-num"><span>2</span></div>
                            <div class="ui-step-item-title">检查环境</div>
                        </li>
                        <li class="ui-step-item">
                            <div class="ui-step-item-num"><span>3</span></div>
                            <div class="ui-step-item-title">配置系统</div>
                        </li>
                        <li class="ui-step-item">
                            <div class="ui-step-item-num"><span>4</span></div>
                            <div class="ui-step-item-title">安装数据库</div>
                        </li>
                        <li class="ui-step-item">
                            <div class="ui-step-item-num"><span>5</span></div>
                            <div class="ui-step-item-title">完成安装</div>
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-body">
                    <pre class="pact-main text-xs" readonly="readonly">
<h6  class="mt-2" style="font-size: 15px">HkCms软件许可使用协议</h6><hr style="margin: 10px 0 15px 0"><p>版权所有，广州恒企教育科技有限公司保留所有权利。</p>
<p>感谢您选择HkCms。希望我们的努力能为您提供一个高效快速和强大的开源内容管理系统及网站建设开发的解决方案。</p>
<p>HkCms中文全称为HkCms开源内容管理系统，以下简称：HkCms。</p>
<p>HkCms开源内容管理系统由广州恒企教育科技有限公司独立开发，全部核心技术归属广州恒企教育科技有限公司所有，HkCms官方网址为：<a href="http://www.hkcms.cn" target="_blank" style="color:#1A4A80">http://www.hkcms.cn</a>，官方问答网址为：<a href="https://www.hkcms.cn/help/" style="color:#1A4A80" target="_blank">https://www.hkcms.cn/help/</a>。
</p>
本许可使用协议适用于HkCms任何版本。

<b>一、协议许可的权利</b><p class="xy mt-2">1、您可以在完全遵守本协议的基础上，将HkCms应用于商业用途。
2、您可以在本协议规定的约束和限制范围内修改HkCms源代码或界面风格以适应您的网站要求。
3、您拥有使用本软件构建的网站全部内容所有权，并独立承担与这些内容相关的法律义务。
4、您必在遵守国家法律法规的前提下使用HkCms，禁止使用HkCms进行任何违法犯罪的活动。
</p>
<b>二、协议许可的权利和限制</b><p class="xy mt-2">1、HkCms著作权已在中华人民共和国国家版权局注册，受到中国法律法规和国际公约保护。
2、无论您在您的网站整体或部份栏目中使用了HkCms，均应在使用了HkCms的网站主页上加上 HkCms官方网址(www.hkcms.cn)的链接。
3、未经官方许可，禁止在 HkCms的整体或任何部分基础上以发展任何派生版本、修改版本或第三方版本用于重新分发。
</p>
<b>三、HkCms免责声明</b><p class="xy mt-2">1、使用 HkCms构建的网站的任何信息内容以及导致的任何版权纠纷等法律争议及后果的由使用方承担，HkCms官方不承担任何责任。
2、对于HkCms的损坏，包括程序的使用(或无法再使用)中所有一般化、特殊化、偶然性的或必然性的损坏(包括但不限于数据的丢失，自己或第三方所维护数据的不正确修改，和其他程序协作过程中程序的崩溃等)，HkCms官方不承担任何责任。
3、您一旦安装或使用HkCms，即被视为完全理解并接受本协议的各项条款，在享有上述条款授予的权利的同时，受到相关的约束和限制。
协议许可范围以外的行为，将直接违反本协议并构成侵权，HkCms官方有权责令其停止损害，并保留追究相关责任的权利。
</p>
</pre>
                    </div>
                </div>
                <div class="text-center mt-5"><a href="/install.php?step=2" class="submit-btn">接 受</a></div>
            </section>
        <?php elseif($step==2): ?>
            <section class="pact">
                <div class="step">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 40%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <ul class="clearfix">
                        <li class="ui-step-item active">
                            <div class="ui-step-item-num"><span>1</span></div>
                            <div class="ui-step-item-title">使用协议</div>
                        </li>
                        <li class="ui-step-item active">
                            <div class="ui-step-item-num"><span>2</span></div>
                            <div class="ui-step-item-title">检查环境</div>
                        </li>
                        <li class="ui-step-item">
                            <div class="ui-step-item-num"><span>3</span></div>
                            <div class="ui-step-item-title">配置系统</div>
                        </li>
                        <li class="ui-step-item">
                            <div class="ui-step-item-num"><span>4</span></div>
                            <div class="ui-step-item-title">安装数据库</div>
                        </li>
                        <li class="ui-step-item">
                            <div class="ui-step-item-num"><span>5</span></div>
                            <div class="ui-step-item-title">完成安装</div>
                        </li>
                    </ul>
                </div>


                <?php if (!empty($errorInfo)):?>
                <ul class="list-group my-3">
                <?php foreach ($errorInfo as $key=>$value):?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div> <?php echo $value;?> </div>
                    <span class="badge badge-danger">失败</span>
                </li>
                <?php endforeach;?>
                </ul>
                <?php else:?>
                <ul class="list-group my-3" style="height: 300px">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>检测环境 </div>
                    <span class="badge badge-success">通过</span>
                </li>
                </ul>
                <?php endif;?>

                <div class="text-center mt-5"><a href="/install.php?step=2" class="submit-btn">重新检测</a><?php if (empty($errorInfo)):?><a href="/install.php?step=3" class="submit-btn ml-2">下一步</a><?php endif;?></div>
            </section>
        <?php elseif($step==3): ?>
        <section class="pact">
            <div class="step">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 60%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <ul class="clearfix">
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>1</span></div>
                        <div class="ui-step-item-title">使用协议</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>2</span></div>
                        <div class="ui-step-item-title">检查环境</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>3</span></div>
                        <div class="ui-step-item-title">配置系统</div>
                    </li>
                    <li class="ui-step-item">
                        <div class="ui-step-item-num"><span>4</span></div>
                        <div class="ui-step-item-title">安装数据库</div>
                    </li>
                    <li class="ui-step-item">
                        <div class="ui-step-item-num"><span>5</span></div>
                        <div class="ui-step-item-title">完成安装</div>
                    </li>
                </ul>
            </div>
            <div class="bg-white">
                <form action="/install.php?step=4" method="post" id="J-frm">
                    <table class="table table-striped table-bordered table-hover table-nowrap">
                        <tbody>
                        <tr style="background-color: #ffffff">
                            <th colspan="3">数据库信息</th>
                        </tr>
                        <tr>
                            <td class="text-right">数据库服务器</td>
                            <td><input type="text" class="form-control" value="127.0.0.1" name="row[dbhost]" data-rule="required"></td>
                            <td class="text-left text-muted msg-box">一般为127.0.0.1或者localhost</td>
                        </tr>
                        <tr style="background-color: #f9f9f9">
                            <td class="text-right">数据库端口</td>
                            <td><input type="text" class="form-control" value="3306" name="row[dbport]" data-rule="required;integer"></td>
                            <td class="text-left text-muted msg-box">一般为3306</td>
                        </tr>
                        <tr>
                            <td class="text-right">数据库用户名</td>
                            <td><input type="text" class="form-control" name="row[dbuser]" data-rule="required"></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr style="background-color: #f9f9f9">
                            <td class="text-right">数据库密码</td>
                            <td><input type="text" class="form-control" name="row[dbpw]" data-rule="required"></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr>
                            <td class="text-right">数据库名</td>
                            <td>
                                <input type="text" class="form-control" value="hkcms" name="row[dbname]" data-rule="required">
                                <p class="m-0 pt-2 text-warning text-left"></p>
                            </td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr style="background-color: #f9f9f9">
                            <td class="text-right">数据库表前缀</td>
                            <td><input type="text" class="form-control" value="hkcms_" name="row[dbprefix]" data-rule="required"></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr style="background-color: #ffffff">
                            <th colspan="3" style="padding-top: 10px;padding-bottom: 10px">网站配置</th>
                        </tr>
                        <tr style="background-color: #ffffff">
                            <td class="text-right">网站名称</td>
                            <td><input type="text" class="form-control" value="HkCms开源内容管理系统" name="row[sitename]" data-rule="required"></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr style="background-color: #f9f9f9">
                            <td class="text-right">关键词</td>
                            <td><input type="text" class="form-control" value="开源、可商用、免授权、开箱即用" name="row[sitekeywords]" data-rule="required"></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr style="background-color: #ffffff">
                            <td class="text-right">描述</td>
                            <td><textarea class="form-control" rows="3" name="row[siteinfo]" data-rule="required">HkCms开源内容管理系统是一款基于ThinkPHP6.0开发的CMS系统。以免授权、永久商用、系统易安装升级、界面功能简洁轻便、易上手、插件与模板在线升级安装、建站联盟扶持计划等优势为一体的CMS系统。</textarea></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr style="background-color: #ffffff">
                            <th colspan="3">管理员信息</th>
                        </tr>
                        <tr style="background-color: #f9f9f9">
                            <td class="text-right">管理员帐号</td>
                            <td><input type="text" class="form-control" value="admin" name="row[manager]" data-rule="required"></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr>
                            <td class="text-right">管理员密码</td>
                            <td><input type="text" class="form-control" value="" name="row[manager_pwd]" data-rule="管理员密码:required;length(6~)"></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr style="background-color: #f9f9f9">
                            <td class="text-right">确认密码</td>
                            <td><input type="text" class="form-control" value="" name="row[manager_ckpwd]" data-rule="required;length(6~);match(row[manager_pwd])"></td>
                            <td class="text-left text-muted msg-box"></td>
                        </tr>
                        <tr>
                            <td class="text-right" style="border-bottom: 1px solid #ddd;">演示数据</td>
                            <td style="border-bottom: 1px solid #ddd;padding-top: 10px;padding-bottom: 10px">
                                <div class="d-flex align-items-center">
                                    <input name="row[testdata]" type="radio" id="demoData0" value="0" checked>
                                    <label for="demoData0" class="mb-0 ml-2">无</label>
                                </div>
                                <div class="d-flex align-items-center">
                                    <input name="row[testdata]" type="radio" id="demoData1" value="1">
                                    <label for="demoData1" class="mb-0 ml-2">默认演示数据</label>
                                </div>
                                <div class="d-flex align-items-center">
                                    <input name="row[testdata]" type="radio" id="demoData2" value="2">
                                    <label for="demoData2" class="mb-0 ml-2">多语言演示数据</label>
                                </div>
                            </td>
                            <td class="text-left text-muted msg-box">
                                默认演示数据，了解HkCms开源内容管理系统！
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <div class="text-center mt-4"><a href="/install.php?step=2" class="submit-btn">上一步</a><a href="#" class="submit-btn ml-1 J-submit">下一步</a></div>
            <script>
                $('.J-submit').click(function (e) {
                    e.preventDefault();
                    $('#J-frm').submit();
                });
                $('#J-frm').validator({
                    valid: function(form) {
                        form.submit();
                    },
                    invalidClass: 'is-invalid',
                    msgMaker: false,
                    validation: function (e, info) {
                        if ($('input[name="'+info.key+'"]').parents('tr').find('.is_error').length==1) {
                            return false;
                        }
                        if (info.isValid==true && info.key=='row[dbhost]') {
                            $('input[name="'+info.key+'"]').parents('tr').find('.msg-box').html("一般为127.0.0.1或者localhost");
                        } else if (info.isValid==true && info.key=='row[dbport]') {
                            $('input[name="'+info.key+'"]').parents('tr').find('.msg-box').html("一般为3306");
                        } else if(info.isValid==true) {
                            $('input[name="'+info.key+'"]').parents('tr').find('.msg-box').html("");
                        } else {
                            $('input[name="'+info.key+'"]').parents('tr').find('.msg-box').html("<span class='text-danger'>"+info.msg+"</span>");
                        }
                    }
                });

                function testMysql()
                {
                    var dbuser = $('input[name="row[dbuser]"]').val();
                    var dbpw = $('input[name="row[dbpw]"]').val();
                    var dbhost = $('input[name="row[dbhost]"]').val();
                    var dbport = $('input[name="row[dbport]"]').val();
                    var dbname = $('input[name="row[dbname]"]').val();
                    if (dbpw.length==0 || dbuser.length==0 || dbhost.length==0 || dbport.length==0 || dbname.length==0) {
                        return ;
                    }

                    $.post('/install.php?step=3', {test:1,dbuser:dbuser,dbpw:dbpw,dbhost:dbhost,dbport:dbport,dbname:dbname}, function (data) {
                        if (data.code==-1000) {
                            $('input[name="row[dbpw]"]').parents('tr').find('.msg-box').html("<span class='text-danger'>"+data.msg+"</span>");
                            $('input[name="row[dbpw]"]').parents('tr').find('.msg-box').addClass('is_error');
                            $('input[name="row[dbpw]"]').val('');
                        } else {
                            $('input[name="row[dbpw]"]').parents('tr').find('.is_error').html("");
                            $('input[name="row[dbpw]"]').parents('tr').find('.is_error').removeClass('is_error');
                            if (data.msg) {
                                $('input[name="row[dbname]"]').parents('tr').find('.text-warning').html(data.msg);
                            } else {
                                $('input[name="row[dbname]"]').parents('tr').find('.text-warning').html("");
                            }
                        }
                    });
                }
                $('input[name="row[dbuser]"]').change(function (e) {
                    testMysql();
                });
                $('input[name="row[dbpw]"]').change(function (e) {
                    testMysql();
                });
                $('input[name="row[dbname]"]').change(function (e) {
                    testMysql();
                });
            </script>
        </section>
        <?php elseif($step==4): ?>
        <section class="pact">
            <div class="step">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 80%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <ul class="clearfix">
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>1</span></div>
                        <div class="ui-step-item-title">使用协议</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>2</span></div>
                        <div class="ui-step-item-title">检查环境</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>3</span></div>
                        <div class="ui-step-item-title">配置系统</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>4</span></div>
                        <div class="ui-step-item-title">安装数据库</div>
                    </li>
                    <li class="ui-step-item">
                        <div class="ui-step-item-num"><span>5</span></div>
                        <div class="ui-step-item-title">完成安装</div>
                    </li>
                </ul>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="pact-main text-xs" style="white-space: normal;">
                        <ul class="p-0 m-0" id="loginner">
                        </ul>
                    </div>
                </div>
            </div>
            <script>
                var data = '<?php echo json_encode($_POST["row"]);?>';
                data = JSON.parse(data);

                var n=0;
                $(function (e) {
                    if (!data) {
                        return false;
                    }
                    function reloads(n) {
                        $.ajax({
                            type: "POST",
                            url: '/install.php?action=mysql&n='+n,
                            data: {row:data},
                            dataType: 'json',
                            cache: false,
                            success: function(msg){
                                if(msg.n=='999999'){
                                    $('#loginner').append(msg.msg);
                                    setTimeout(function () {
                                        window.location.href="/install.php?step=5";
                                    }, 2000);
                                    return false;
                                }
                                if(msg.n){
                                    $('#loginner').append(msg.msg);
                                    reloads(msg.n);
                                }else{
                                    layer.alert(msg.msg)
                                }
                            }
                        });
                    }
                    reloads(n);
                })
            </script>
            <div class="text-center mt-5"><a href="#" class="submit-btn disabled" style="cursor: not-allowed;"><div class="spinner-border text-light" role="status" style="height: 16px;width: 16px"><span class="sr-only">Loading...</span></div><span class="ml-1">正在安装...</span></a></div>
        </section>
        <?php elseif($step==5): ?>
        <section class="pact">
            <div class="step">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <ul class="clearfix">
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>1</span></div>
                        <div class="ui-step-item-title">使用协议</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>2</span></div>
                        <div class="ui-step-item-title">检查环境</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>3</span></div>
                        <div class="ui-step-item-title">配置系统</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>4</span></div>
                        <div class="ui-step-item-title">安装数据库</div>
                    </li>
                    <li class="ui-step-item active">
                        <div class="ui-step-item-num"><span>5</span></div>
                        <div class="ui-step-item-title">完成安装</div>
                    </li>
                </ul>
            </div>

            <div class="server">
                <div class="server-yes-top">安装完成</div>
                <div class="success_tip">
                    <div class="row m-0">
                        <div class="col-md-4 text-center"><span class="fas fa-check-circle" style="line-height: 140px;font-size: 80px;color: #34d0b6"></span></div>
                        <div class="col-md-8 pt-4">
                            <a href="/admin.php/Index/index" target="_blank" class="text-dark"><i class="fa fa-user"></i> 后台管理</a>
                            <span class="ml-1 mr-1">|</span>
                            <a href="/" target="_blank" class="text-dark"><i class="fa fa-home"></i> 站点首页</a>
                            <p class="mt-3">
                                在线手册：<a href="https://www.kancloud.cn/hkcms/hkcms_tp6/2252597" class="text-dark" target="_blank">http://doc.hkcms.cn</a> <br>
                                官方网站：<a href="http://www.hkcms.cn" class="text-dark" target="_blank">http://www.hkcms.cn</a> <br>
                                社区问答：<a href="https://www.hkcms.cn/help" class="text-dark" target="_blank">https://www.hkcms.cn/help</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row text-danger m-0 pb-3 pt-4">
                    <div class="col-md-12">
                        <div class="alert alert-danger" role="alert">我们致力于打造一套开源免费的CMS系统。<br>如果本程序对您有所帮助，那么我们非常期待您能够参与到HkCms的开发和建设中。</div>
                    </div>
                </div>
            </div>

        </section>
        <?php endif; ?>

        <div class="footer">Copyright &copy; 2012 - <?php echo date('Y');?> <a href="http://www.hkcms.cn" class="text-muted" target="_blank">HkCms开源内容管理系统</a></div>
    </body>
</html>
