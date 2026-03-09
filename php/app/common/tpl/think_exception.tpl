<?php
/** @var array $traces */
if (!function_exists('parse_padding')) {
    function parse_padding($source)
    {
        $length  = strlen(strval(count($source['source']) + $source['first']));
        return 40 + ($length - 1) * 8;
    }
}

if (!function_exists('parse_class')) {
    function parse_class($name)
    {
        $names = explode('\\', $name);
        return '<abbr title="'.$name.'">'.end($names).'</abbr>';
    }
}

if (!function_exists('parse_file')) {
    function parse_file($file, $line)
    {
        return '<a class="toggle" title="'."{$file} line {$line}".'">'.basename($file)." line {$line}".'</a>';
    }
}

if (!function_exists('parse_args')) {
    function parse_args($args)
    {
        $result = [];
        foreach ($args as $key => $item) {
            switch (true) {
                case is_object($item):
                    $value = sprintf('<em>object</em>(%s)', parse_class(get_class($item)));
                    break;
                case is_array($item):
                    if (count($item) > 3) {
                        $value = sprintf('[%s, ...]', parse_args(array_slice($item, 0, 3)));
                    } else {
                        $value = sprintf('[%s]', parse_args($item));
                    }
                    break;
                case is_string($item):
                    if (strlen($item) > 20) {
                        $value = sprintf(
                            '\'<a class="toggle" title="%s">%s...</a>\'',
                            htmlentities($item),
                            htmlentities(substr($item, 0, 20))
                        );
                    } else {
                        $value = sprintf("'%s'", htmlentities($item));
                    }
                    break;
                case is_int($item):
                case is_float($item):
                    $value = $item;
                    break;
                case is_null($item):
                    $value = '<em>null</em>';
                    break;
                case is_bool($item):
                    $value = '<em>' . ($item ? 'true' : 'false') . '</em>';
                    break;
                case is_resource($item):
                    $value = '<em>resource</em>';
                    break;
                default:
                    $value = htmlentities(str_replace("\n", '', var_export(strval($item), true)));
                    break;
            }

            $result[] = is_int($key) ? $value : "'{$key}' => {$value}";
        }

        return implode(', ', $result);
    }
}
if (!function_exists('echo_value')) {
    function echo_value($val)
    {
        if (is_array($val) || is_object($val)) {
            echo htmlentities(json_encode($val, JSON_PRETTY_PRINT));
        } elseif (is_bool($val)) {
            echo $val ? 'true' : 'false';
        } elseif (is_scalar($val)) {
            echo htmlentities($val);
        } else {
            echo 'Resource';
        }
    }
}
?>

<?php if (\think\facade\App::isDebug()) { ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>系统发生错误</title>
    <meta name="robots" content="noindex,nofollow" />
    <style>
        /* Base */
        body {
            color: #333;
            font: 16px Verdana, "Helvetica Neue", helvetica, Arial, 'Microsoft YaHei', sans-serif;
            margin: 0;
            padding: 0 20px 20px;
        }
        h1{
            margin: 10px 0 0;
            font-size: 28px;
            font-weight: 500;
            line-height: 32px;
        }
        h2{
            color: #4288ce;
            font-weight: 400;
            padding: 6px 0;
            margin: 6px 0 0;
            font-size: 18px;
            border-bottom: 1px solid #eee;
        }
        h3{
            margin: 12px;
            font-size: 16px;
            font-weight: bold;
        }
        abbr{
            cursor: help;
            text-decoration: underline;
            text-decoration-style: dotted;
        }
        a{
            color: #868686;
            cursor: pointer;
        }
        a:hover{
            text-decoration: underline;
        }
        .line-error{
            background: #f8cbcb;
        }
        .echo table {
            width: 100%;
        }
        .echo pre {
            padding: 16px;
            overflow: auto;
            font-size: 85%;
            line-height: 1.45;
            background-color: #f7f7f7;
            border: 0;
            border-radius: 3px;
            font-family: Consolas, "Liberation Mono", Menlo, Courier, monospace;
        }
        .echo pre > pre {
            padding: 0;
            margin: 0;
        }
        /* Exception Info */
        .exception {
            margin-top: 20px;
        }
        .exception .message{
            padding: 12px;
            border: 1px solid #ddd;
            border-bottom: 0 none;
            line-height: 18px;
            font-size:16px;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            font-family: Consolas,"Liberation Mono",Courier,Verdana,"微软雅黑",serif;
        }
        .exception .code{
            float: left;
            text-align: center;
            color: #fff;
            margin-right: 12px;
            padding: 16px;
            border-radius: 4px;
            background: #999;
        }
        .exception .source-code{
            padding: 6px;
            border: 1px solid #ddd;

            background: #f9f9f9;
            overflow-x: auto;

        }
        .exception .source-code pre{
            margin: 0;
        }
        .exception .source-code pre ol{
            margin: 0;
            color: #4288ce;
            display: inline-block;
            min-width: 100%;
            box-sizing: border-box;
            font-size:14px;
            font-family: "Century Gothic",Consolas,"Liberation Mono",Courier,Verdana,serif;
            padding-left: <?php echo (isset($source) && !empty($source)) ? parse_padding($source) : 40;  ?>px;
        }
        .exception .source-code pre li{
            border-left: 1px solid #ddd;
            height: 18px;
            line-height: 18px;
        }
        .exception .source-code pre code{
            color: #333;
            height: 100%;
            display: inline-block;
            border-left: 1px solid #fff;
            font-size:14px;
            font-family: Consolas,"Liberation Mono",Courier,Verdana,"微软雅黑",serif;
        }
        .exception .trace{
            padding: 6px;
            border: 1px solid #ddd;
            border-top: 0 none;
            line-height: 16px;
            font-size:14px;
            font-family: Consolas,"Liberation Mono",Courier,Verdana,"微软雅黑",serif;
        }
        .exception .trace h2:hover {
            text-decoration: underline;
            cursor: pointer;
        }
        .exception .trace ol{
            margin: 12px;
        }
        .exception .trace ol li{
            padding: 2px 4px;
        }
        .exception div:last-child{
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 4px;
        }

        /* Exception Variables */
        .exception-var table{
            width: 100%;
            margin: 12px 0;
            box-sizing: border-box;
            table-layout:fixed;
            word-wrap:break-word;
        }
        .exception-var table caption{
            text-align: left;
            font-size: 16px;
            font-weight: bold;
            padding: 6px 0;
        }
        .exception-var table caption small{
            font-weight: 300;
            display: inline-block;
            margin-left: 10px;
            color: #ccc;
        }
        .exception-var table tbody{
            font-size: 13px;
            font-family: Consolas, "Liberation Mono", Courier, "微软雅黑",serif;
        }
        .exception-var table td{
            padding: 0 6px;
            vertical-align: top;
            word-break: break-all;
        }
        .exception-var table td:first-child{
            width: 28%;
            font-weight: bold;
            white-space: nowrap;
        }
        .exception-var table td pre{
            margin: 0;
        }

        /* Copyright Info */
        .cms_copyright{
            margin-top: 24px;
            padding: 10px 0 0 0;
        }
        .copyright{
            margin-top: 10px;
            padding: 12px 0;
            border-top: 1px solid #eee;
        }

        /* SPAN elements with the classes below are added by prettyprint. */
        pre.prettyprint .pln { color: #000 }  /* plain text */
        pre.prettyprint .str { color: #080 }  /* string content */
        pre.prettyprint .kwd { color: #008 }  /* a keyword */
        pre.prettyprint .com { color: #800 }  /* a comment */
        pre.prettyprint .typ { color: #606 }  /* a type name */
        pre.prettyprint .lit { color: #066 }  /* a literal value */
        /* punctuation, lisp open bracket, lisp close bracket */
        pre.prettyprint .pun, pre.prettyprint .opn, pre.prettyprint .clo { color: #660 }
        pre.prettyprint .tag { color: #008 }  /* a markup tag name */
        pre.prettyprint .atn { color: #606 }  /* a markup attribute name */
        pre.prettyprint .atv { color: #080 }  /* a markup attribute value */
        pre.prettyprint .dec, pre.prettyprint .var { color: #606 }  /* a declaration; a variable name */
        pre.prettyprint .fun { color: red }  /* a function name */
    </style>
</head>
<body>
<?php if (\think\facade\App::isDebug()) { ?>
<?php foreach ($traces as $index => $trace) { ?>
<div class="exception">
    <div class="message">
        <div class="info">
            <div>
                <h2><?php echo "#{$index} [{$trace['code']}]" . sprintf('%s in %s', parse_class($trace['name']), parse_file($trace['file'], $trace['line'])); ?></h2>
            </div>
            <div><h1><?php echo nl2br(htmlentities($trace['message'])); ?></h1></div>
        </div>
    </div>
    <?php if (!empty($trace['source'])) { ?>
    <div class="source-code">
        <pre class="prettyprint lang-php"><ol start="<?php echo $trace['source']['first']; ?>"><?php foreach ((array) $trace['source']['source'] as $key => $value) { ?><li class="line-<?php echo "{$index}-"; echo $key + $trace['source']['first']; echo $trace['line'] === $key + $trace['source']['first'] ? ' line-error' : ''; ?>"><code><?php echo htmlentities($value); ?></code></li><?php } ?></ol></pre>
    </div>
    <?php }?>
    <div class="trace">
        <h2 data-expand="<?php echo 0 === $index ? '1' : '0'; ?>">Call Stack</h2>
        <ol>
            <li><?php echo sprintf('in %s', parse_file($trace['file'], $trace['line'])); ?></li>
            <?php foreach ((array) $trace['trace'] as $value) { ?>
            <li>
                <?php
                        // Show Function
                        if ($value['function']) {
                            echo sprintf(
                                'at %s%s%s(%s)',
                                isset($value['class']) ? parse_class($value['class']) : '',
                                isset($value['type'])  ? $value['type'] : '',
                                $value['function'],
                                isset($value['args'])?parse_args($value['args']):''
                            );
                        }

                        // Show line
                        if (isset($value['file']) && isset($value['line'])) {
                            echo sprintf(' in %s', parse_file($value['file'], $value['line']));
                        }
                        ?>
            </li>
            <?php } ?>
        </ol>
    </div>
</div>
<?php } ?>
<?php } ?>

<?php if (!empty($datas)) { ?>
<div class="exception-var">
    <h2>Exception Datas</h2>
    <?php foreach ((array) $datas as $label => $value) { ?>
    <table>
        <?php if (empty($value)) { ?>
        <caption><?php echo $label; ?><small>empty</small></caption>
        <?php } else { ?>
        <caption><?php echo $label; ?></caption>
        <tbody>
        <?php foreach ((array) $value as $key => $val) { ?>
        <tr>
            <td><?php echo htmlentities($key); ?></td>
            <td><?php echo_value($val); ?></td>
        </tr>
        <?php } ?>
        </tbody>
        <?php } ?>
    </table>
    <?php } ?>
</div>
<?php } ?>

<?php if (!empty($tables)) { ?>
<div class="exception-var">
    <h2>Environment Variables</h2>
    <?php foreach ((array) $tables as $label => $value) { ?>
    <table>
        <?php if (empty($value)) { ?>
        <caption><?php echo $label; ?><small>empty</small></caption>
        <?php } else { ?>
        <caption><?php echo $label; ?></caption>
        <tbody>
        <?php foreach ((array) $value as $key => $val) { ?>
        <tr>
            <td><?php echo htmlentities($key); ?></td>
            <td><?php echo_value($val); ?></td>
        </tr>
        <?php } ?>
        </tbody>
        <?php } ?>
    </table>
    <?php } ?>
</div>
<?php } ?>

<div class="cms_copyright">
    <span>HkCms V<?php echo config('ver.cms_version');?>，尊敬的用户：为了让我们HkCms更完善，你可以加入QQ群或问答社区，把错误信息反馈给我们。</span>
</div>
<div class="copyright">
    <span><a title="官方网站" href="http://www.hkcms.cn/" target="_blank">官方网站</a></span>
    <span>- <a title="官方手册" href="https://www.kancloud.cn/hkcms/hkcms_tp6/2252597" target="_blank">官方手册</a></span>
    <span>- <a title="官方问答社区" href="https://www.hkcms.cn/help/" target="_blank">官方问答社区</a></span>
    <span>- <a title="QQ群808251031" href="https://jq.qq.com/?_wv=1027&k=8YzV4elJ" target="_blank">QQ群808251031</a></span>
</div>
<?php if (\think\facade\App::isDebug()) { ?>
<script>
    function $(selector, node){
        var elements;

        node = node || document;
        if(document.querySelectorAll){
            elements = node.querySelectorAll(selector);
        } else {
            switch(selector.substr(0, 1)){
                case '#':
                    elements = [node.getElementById(selector.substr(1))];
                    break;
                case '.':
                    if(document.getElementsByClassName){
                        elements = node.getElementsByClassName(selector.substr(1));
                    } else {
                        elements = get_elements_by_class(selector.substr(1), node);
                    }
                    break;
                default:
                    elements = node.getElementsByTagName();
            }
        }
        return elements;

        function get_elements_by_class(search_class, node, tag) {
            var elements = [], eles,
                pattern  = new RegExp('(^|\\s)' + search_class + '(\\s|$)');

            node = node || document;
            tag  = tag  || '*';

            eles = node.getElementsByTagName(tag);
            for(var i = 0; i < eles.length; i++) {
                if(pattern.test(eles[i].className)) {
                    elements.push(eles[i])
                }
            }

            return elements;
        }
    }

    $.getScript = function(src, func){
        var script = document.createElement('script');

        script.async  = 'async';
        script.src    = src;
        script.onload = func || function(){};

        $('head')[0].appendChild(script);
    }

    ;(function(){
        var files = $('.toggle');
        var ol    = $('ol', $('.prettyprint')[0]);
        var li    = $('li', ol[0]);

        // 短路径和长路径变换
        for(var i = 0; i < files.length; i++){
            files[i].ondblclick = function(){
                var title = this.title;

                this.title = this.innerHTML;
                this.innerHTML = title;
            }
        }

        (function () {
            var expand = function (dom, expand) {
                var ol = $('ol', dom.parentNode)[0];
                expand = undefined === expand ? dom.attributes['data-expand'].value === '0' : undefined;
                if (expand) {
                    dom.attributes['data-expand'].value = '1';
                    ol.style.display = 'none';
                    dom.innerText = 'Call Stack (展开)';
                } else {
                    dom.attributes['data-expand'].value = '0';
                    ol.style.display = 'block';
                    dom.innerText = 'Call Stack (折叠)';
                }
            };
            var traces = $('.trace');
            for (var i = 0; i < traces.length; i ++) {
                var h2 = $('h2', traces[i])[0];
                expand(h2);
                h2.onclick = function () {
                    expand(this);
                };
            }
        })();

        $.getScript('//cdn.bootcss.com/prettify/r298/prettify.min.js', function(){
            prettyPrint();
        });
    })();
</script>
<?php } ?>
</body>
</html>
<?php } else { ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>系统发生错误</title>
    <meta name="robots" content="noindex,nofollow" />
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/static/libs/bootstrap/css/bootstrap.min.css">
    <style>
        html,body {
            height: 100%;
        }
        body {
            margin: 0;
            font-family: "Roboto", sans-serif;
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
            color: #76838f;
            text-align: left;
            background-color: #e9ecef;
        }
        .card {
            margin-bottom: 30px;
            border: 0px;
            border-radius: 0.625rem;
            box-shadow: 6px 11px 41px -28px #90e1c9a1;
        }
        .card .card-body {
            padding: 1.88rem 1.81rem;
        }
        .error-content {
            height: 100%;
            display: flex;
            justify-content: center;
            flex-direction: column;
        }
        .error-text {
            font-size: 5rem;
            line-height: 5rem;
            color: #7571f9;
        }

        .btn-hkcms {
            background: #ff9900;
            border-color: #ff9900;
            color: #fff;
        }
        .btn-hkcms:active, .btn-hkcms:focus, .btn-hkcms:hover {
            background: #EF8200;
            color: #fff;
            border-color: #ff9900;
        }

        .btn-qq {
            background: #3b5998;
            border-color: #3b5998;
            color: #fff;
        }
        .btn-qq:active, .btn-qq:focus, .btn-qq:hover {
            background: #2d4373;
            color: #fff;
            border-color: #2d4373;
        }
        @media (max-width: 768px) {
            .h-100 {
                height: auto !important;
            }
        }
    </style>
</head>
<body>

<div class="h-100">
    <div class="container h-100 error-content">
        <div class="row justify-content-center">
            <div class="col-xl-6">
                <div class="card mb-0">
                    <div class="card-body text-center">
                        <h1 class="error-text text-primary mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-emoji-frown" viewBox="0 0 16 16" style="color: #d8646f">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M4.285 12.433a.5.5 0 0 0 .683-.183A3.498 3.498 0 0 1 8 10.5c1.295 0 2.426.703 3.032 1.75a.5.5 0 0 0 .866-.5A4.498 4.498 0 0 0 8 9.5a4.5 4.5 0 0 0-3.898 2.25.5.5 0 0 0 .183.683zM7 6.5C7 7.328 6.552 8 6 8s-1-.672-1-1.5S5.448 5 6 5s1 .672 1 1.5zm4 0c0 .828-.448 1.5-1 1.5s-1-.672-1-1.5S9.448 5 10 5s1 .672 1 1.5z"/>
                            </svg>
                        </h1>
                        <h4 class="mt-4" style="line-height:normal"><?php echo htmlentities($message); ?></h4>
                        <div class="mt-5 mb-4">
                            <div class="row justify-content-md-center">
                                <div class="col-4" style="background: #eee;height: 1px"></div>
                            </div>
                        </div>
                        <div class="text-center">
                            <p><span>HkCms V<?php echo config('ver.cms_version');?>(<?php echo config('ver.cms_build');?>)</span></p>
                            <ul class="list-inline">
                                <li class="list-inline-item">
                                    <a href="http://www.hkcms.cn/" class="btn btn-sm btn-hkcms d-flex align-items-center" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-globe" viewBox="0 0 16 16">
                                            <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855A7.97 7.97 0 0 0 5.145 4H7.5V1.077zM4.09 4a9.267 9.267 0 0 1 .64-1.539 6.7 6.7 0 0 1 .597-.933A7.025 7.025 0 0 0 2.255 4H4.09zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a6.958 6.958 0 0 0-.656 2.5h2.49zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5H4.847zM8.5 5v2.5h2.99a12.495 12.495 0 0 0-.337-2.5H8.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5H4.51zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5H8.5zM5.145 12c.138.386.295.744.468 1.068.552 1.035 1.218 1.65 1.887 1.855V12H5.145zm.182 2.472a6.696 6.696 0 0 1-.597-.933A9.268 9.268 0 0 1 4.09 12H2.255a7.024 7.024 0 0 0 3.072 2.472zM3.82 11a13.652 13.652 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5H3.82zm6.853 3.472A7.024 7.024 0 0 0 13.745 12H11.91a9.27 9.27 0 0 1-.64 1.539 6.688 6.688 0 0 1-.597.933zM8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855.173-.324.33-.682.468-1.068H8.5zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.65 13.65 0 0 1-.312 2.5zm2.802-3.5a6.959 6.959 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5h2.49zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7.024 7.024 0 0 0-3.072-2.472c.218.284.418.598.597.933zM10.855 4a7.966 7.966 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4h2.355z"/>
                                        </svg>
                                        <span class="ml-1">官方网站</span>
                                    </a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="https://www.kancloud.cn/hkcms/hkcms_tp6/2252597" class="btn btn-sm btn-success d-flex align-items-center" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                                            <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                                            <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                                        </svg>
                                        <span class="ml-1">使用手册</span>
                                    </a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="https://www.hkcms.cn/help/" class="btn btn-sm btn-info d-flex align-items-center" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-patch-question" viewBox="0 0 16 16">
                                            <path d="M8.05 9.6c.336 0 .504-.24.554-.627.04-.534.198-.815.847-1.26.673-.475 1.049-1.09 1.049-1.986 0-1.325-.92-2.227-2.262-2.227-1.02 0-1.792.492-2.1 1.29A1.71 1.71 0 0 0 6 5.48c0 .393.203.64.545.64.272 0 .455-.147.564-.51.158-.592.525-.915 1.074-.915.61 0 1.03.446 1.03 1.084 0 .563-.208.885-.822 1.325-.619.433-.926.914-.926 1.64v.111c0 .428.208.745.585.745z"/>
                                            <path d="m10.273 2.513-.921-.944.715-.698.622.637.89-.011a2.89 2.89 0 0 1 2.924 2.924l-.01.89.636.622a2.89 2.89 0 0 1 0 4.134l-.637.622.011.89a2.89 2.89 0 0 1-2.924 2.924l-.89-.01-.622.636a2.89 2.89 0 0 1-4.134 0l-.622-.637-.89.011a2.89 2.89 0 0 1-2.924-2.924l.01-.89-.636-.622a2.89 2.89 0 0 1 0-4.134l.637-.622-.011-.89a2.89 2.89 0 0 1 2.924-2.924l.89.01.622-.636a2.89 2.89 0 0 1 4.134 0l-.715.698a1.89 1.89 0 0 0-2.704 0l-.92.944-1.32-.016a1.89 1.89 0 0 0-1.911 1.912l.016 1.318-.944.921a1.89 1.89 0 0 0 0 2.704l.944.92-.016 1.32a1.89 1.89 0 0 0 1.912 1.911l1.318-.016.921.944a1.89 1.89 0 0 0 2.704 0l.92-.944 1.32.016a1.89 1.89 0 0 0 1.911-1.912l-.016-1.318.944-.921a1.89 1.89 0 0 0 0-2.704l-.944-.92.016-1.32a1.89 1.89 0 0 0-1.912-1.911l-1.318.016z"/>
                                            <path d="M7.001 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0z"/>
                                        </svg>
                                        <span class="ml-1">帮助中心</span>
                                    </a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="https://jq.qq.com/?_wv=1027&k=8YzV4elJ" class="btn btn-sm btn-qq d-flex align-items-center" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="0" height="16" fill="currentColor" class="bi bi-patch-question" viewBox="0 0 16 16"></svg>
                                        <span class="ml">Q群808251031</span></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="mt-4">
                    <?php $q = explode(':', $message)[0];?>
                    <div align="center"><b style="font-size: 16px">问题解决方案列表。</b><small>未能解决？<a href="https://www.hkcms.cn/help/posts/post.html?type=3&sh=1&q=<?php echo $q;?>" target="_blank">点我立即提问</a> 或 <a href="http://wpa.qq.com/msgrd?v=3&uin=746117169&site=qq&menu=yes" target="_blank">联系QQ客服</a></small></div>
                    <div class="mt-3" style="border-radius: 0.525rem;box-shadow: 6px 11px 41px -28px #90e1c9a1;height:350px;overflow: hidden">
                        <iframe src="//www.hkcms.cn/help/s.html?pagetype=1&q=<?php echo $q;?>" frameborder="0" style="width: 100%;height: 100%"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php } ?>

