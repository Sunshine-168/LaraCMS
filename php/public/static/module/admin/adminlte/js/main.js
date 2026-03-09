require.config({
    baseUrl: Config.cdn+'/static/',
    urlArgs: "v="+(Config.app_debug==1?new Date().getTime():Config.version),
    map: {
        '*': {
            'css': 'libs/require/css.min'
        }
    },
    paths: {
        "jquery": "libs/jquery/jquery.min",
        "bootstrap": "libs/bootstrap/js/bootstrap.bundle.min",
        "adminlte": Config.static_path+'/js/adminlte',
        "hkcms": "common/js/hkcms",
        "table": "common/js/table",
        "form": "common/js/form",
        "bootstrap-table": 'libs/bootstrap-table-master/bootstrap-table.min',
        "bootstrap-table-lang": 'libs/bootstrap-table-master/locale/bootstrap-table-'+Config.admin_lang+'.min',
        "validator": 'libs/nice-validator/jquery.validator.min',
        "validatorLang": 'libs/nice-validator/local/'+Config.admin_lang, // 语言包
        "jquery-ui-widget": 'libs/jquery-fileupload/jquery.ui.widget',
        "jquery-fileupload": 'libs/jquery-fileupload/jquery.fileupload',
        "jquery-ui": 'libs/jquery-ui/jquery-ui.min',
        "layerJs": 'libs/layer/layer',
        "laydate": 'libs/laydate/laydate',
        "selectpage": 'libs/selectpage/selectpage.min',
        "jstree": 'libs/jstree/jstree.min',
        "selectpicker": 'libs/bootstrap-select/js/bootstrap-select.min',
        "overlayScrollbars": 'libs/OverlayScrollbars/jquery.overlayScrollbars.min',
        "moment": 'libs/momentjs/moment',
    },
    shim: {
        'bootstrap': ['jquery'],
        'adminlte': ['jquery','bootstrap'],
        'admin': ['jquery','bootstrap','layer'],
        'bootstrap-table': ['bootstrap'],
        'bootstrap-table-lang': ['bootstrap-table'],
        'validator': ['css!libs/nice-validator/jquery.validator.css'],
        'validatorLang': ['validator'],
        'jquery-fileupload': ['jquery-ui-widget'],
        'jquery-ui': ['css!libs/jquery-ui/jquery-ui.min.css'],
        'selectpage': ['css!libs/selectpage/selectpage.css'],
        'jstree': ['jquery','css!libs/jstree/themes/default/style.min.css'],
        'selectpicker': ['jquery','css!libs/bootstrap-select/css/bootstrap-select.min.css'],
        'overlayScrollbars': ['jquery','css!libs/OverlayScrollbars/OverlayScrollbars.css'],
    },
    waitSeconds: 30
});

// 兼容旧版
define('cmsTable', ['table'], function (Table) {
    return Table;
});
define('Form', ['form'], function (Form) {
    return Form;
});
define('layer', ['layerJs'], function () {
    // 初始化弹出框
    layer.config({
        extend: 'admin/layer.css', //加载新皮肤
    });
    window.Layer = layer;
});

/**
 * admin初始化，所有页面加载时需要加载admin初始化
 */
define('admin', ['jquery', 'bootstrap','layer','overlayScrollbars', 'adminlte', 'hkcms'], function ($) {
    // 增加class，自动添加标签页
    $(document).on('click','.btn-newMenu', function (e) {
        e.preventDefault();

        var obj = window.top.$;
        var link = $(this).attr('href');
        link = link ? link : $(this).data('url');
        if (!link) {
            console.error('缺少data-url或href必要属性')
            return;
        }

        var unq = link.replace('./', '').replace(/["&'./:=?[\]]/gi, '-').replace(/(--)/gi, '');

        var len = obj('.iframe-mode .navbar-nav').find('#tab-'+unq).length;
        if (len>0) {
            obj('.content-wrapper.iframe-mode').IFrame('switchTab', '#tab-'+unq)
        } else {
            obj('.content-wrapper.iframe-mode').IFrame('createTab', $(this).data('title'), link, unq, true)
        }
    });

    $(function () {

        // 子页高度计算
        if ($('.operatePage').find('.card-body').length==1) {
            function resizeFrom() {
                var heights = {
                    body: $('body').outerHeight(),
                    header: $('.frm-operate').find('.card-header').outerHeight() || 0,
                    footer: $('.frm-operate').find('.card-footer').outerHeight() || 0
                };
                $('.frm-operate').find('.card-body').css('overflow-y','auto');
                $('.frm-operate').find('.card-body').css('height', heights.body - heights.header - heights.footer);
            }

            $(window).resize(function() {
                resizeFrom();
            });
            resizeFrom();

            $('body').overlayScrollbars({
                scrollbars:{autoHide:"leave"}
            });
        }

        // 增加滚动条
        if ($('.operatePage').find('.card-body').length==1) {
            $('.frm-operate').find('.card-body').overlayScrollbars({
                scrollbars:{autoHide:"leave"}
            });
        } else {
            $('.overlayScrollbars').overlayScrollbars({
                scrollbars:{autoHide:"leave"}
            });
        }
    })
    var admin = {};
    return admin;
})