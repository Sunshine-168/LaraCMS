/**
 * 语言包
 * @param name
 * @param arr
 * @returns {*}
 */
function lang(name, arr) {
    name = Lang[name] ? Lang[name] : name;
    if (arr) {
        for(var key in arr) {
            name = name.replace('%s', arr[key]);
        }
    }
    return name;
}

require.config({
    baseUrl: Config.cdn+'/static/',
    urlArgs: "v="+Config.version+parseInt(new Date().getTime()/1000),
    map: {
        '*': {
            'css': 'libs/require/css.min'
        }
    },
    paths: {
        "jquery": "libs/jquery/jquery.min",
        "bootstrap": "libs/bootstrap/js/bootstrap.bundle.min",
        // "bootstrap-table": 'libs/bootstrap-table-master/bootstrap-table.min',
        // "bootstrap-table-lang": 'libs/bootstrap-table-master/locale/bootstrap-table-'+Config.admin_lang+'.min',
        "validator": 'libs/nice-validator/jquery.validator.min',
        "validatorLang": 'libs/nice-validator/local/zh-cn', // 语言包
        "jquery-ui-widget": 'libs/jquery-fileupload/jquery.ui.widget',
        "jquery-fileupload": 'libs/jquery-fileupload/jquery.fileupload',
        // "jquery-ui": 'libs/jquery-ui/jquery-ui.min',
        "layerJs": 'libs/layer/layer',
        // "laydate": 'libs/laydate/laydate',
        // "selectpage": 'libs/selectpage/selectpage.min',
        // "jstree": 'libs/jstree/jstree.min'
    },
    shim: {
        'bootstrap': ['jquery'],
        // 'bootstrap-table': ['bootstrap'],
        // 'bootstrap-table-lang': ['bootstrap-table'],
        'validator': ['css!libs/nice-validator/jquery.validator.css'],
        'validatorLang': ['validator'],
        'jquery-fileupload': ['jquery-ui-widget'],
        // 'jquery-ui': ['css!libs/jquery-ui/jquery-ui.min.css'],
        // 'selectpage': ['css!libs/selectpage/selectpage.css'],
        // 'jstree': ['jquery','css!libs/jstree/themes/default/style.min.css']
    },
    waitSeconds: 30
});

/**
 * Util常用函数库
 * @returns {UtilHelpApp20190708}
 * @constructor
 */
function UtilHelpApp20190708() {
    if (this.constructor != UtilHelpApp20190708) {
        return new UtilHelpApp20190708();
    }

    /**
     * 设置URL常用参数
     * @param options object {url:'', query:[]}
     * @returns {string|*}
     */
    this.setUrlParams = function (options) {
        let {url,query} = options;
        if(query) {
            let queryArr = [];
            for (const key in query) {
                if (query.hasOwnProperty(key)) {
                    queryArr.push(`${key}=${query[key]}`)
                }
            }
            if(url.indexOf('?') !== -1) {
                url =`${url}&${queryArr.join('&')}`
            } else {
                url = url ? `${url}?${queryArr.join('&')}` : queryArr.join('&');
            }
        }
        return url;
    };

    /**
     * 获取URL地址参数
     * @param url
     * @returns {{}}
     */
    this.getUrlParams = function getRequest(url) {
        url = url || window.location.search; //获取url中"?"符后的字串
        var params = {};
        if (url.indexOf("?") != -1) {
            var str = url.substr(1);
            str = str.split("&");
            for(var i = 0; i < str.length; i ++) {
                params[str[i].split("=")[0]]=decodeURI(str[i].split("=")[1]);
            }
        }
        return params;
    }

    /**
     * ajax请求
     * @param options ajax选项，覆盖ajax
     * @param before    请求前的回调
     * @param success   请求后的回调
     * @param error 响应成功，但状态码错误的回调
     * @param btnSumit 提交按钮对象
     * @returns {*}
     */
    this.ajax = function (options, before, success, error, btnSumit) {
        options = typeof options === 'string' ? {url: options} : options;

        if (options.type==='post') {
            var token = $('meta[name="csrf-token"]').attr('content');
            if (token) {
                if (Object.prototype.toString.call(options.data) === '[object Array]') {
                    options.data.push({name:'__token__',value:token});
                } else if(typeof options.data == "object") {
                    options.data['__token__'] = token;
                }
            }
        }

        var index = 0;
        var defaultConfig = {
            type: 'get',
            dataType:'json',
            beforeSend:function(XMLHttpRequest,self) {
                if (typeof before === "function") {
                    var data = before(options.data);
                    if (false===data) {
                        if (btnSumit){btnSumit.removeClass('disabled')}
                        return false;
                    }
                    self.data = $.param(data);
                }
                index = layer.load(1);
            },
            cache: false,
            complete: function (xhr) {
                var token = xhr.getResponseHeader('__token__');
                if (token) {
                    $('meta[name="csrf-token"]').attr('content',token);
                }
            },
            success: function(response) {
                layer.close(index);

                if (response.code === 200) {
                    if (typeof success === "function") {
                        if (false===success(response.data,response)) {
                            return false;
                        }
                    }
                } else {
                    if (typeof error === "function") {
                        if (false===error(response)) {
                            return false;
                        }
                    }
                    layer.msg(response.msg, {time:4000,icon:2});
                }
            },
            error: function(e) {
                layer.close(index);

                if (btnSumit){
                    btnSumit.removeClass('disabled')
                }

                if (e.status>0) {
                    let txt = e.statusText;
                    if (e.responseJSON && e.responseJSON.message) {
                        txt = e.responseJSON.message;
                    } else if (e.responseJSON && e.responseJSON.msg) {
                        txt = e.responseJSON.msg;
                    }
                    layer.alert('['+e.status+'] '+txt);
                } else {
                    layer.alert(lang('Request exception'));
                }
            }
        };
        return $.ajax($.extend(defaultConfig,options));
    }

    /**
     * 获取唯一值
     * @returns {string}
     */
    this.guid = function () {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
            return v.toString(16);
        });
    }
}

var UtilHelpApp = UtilHelpApp20190708;
window.Util = new UtilHelpApp();

define('layer', ['jquery', 'layerJs'], function ($, layer) {
    layer.config({
        extend: 'default/layer.css', //加载新皮肤
    });

    // 全局
    window.Layer = layer;
    return layer;
})

define('Form', ['jquery','layer','validator','validatorLang'], function ($, layer) {
    var Form = {
        config: {
            frmOperate: '.frm-operate', // 表单标签class
            isValidate: true, // 是否开启验证
        },
        api: {
            init: function (config) {
                config = config ? config : {};
                config = $.extend(Form.config, config);
                let form = typeof config.frmOperate == 'object' ? config.frmOperate : $(config.frmOperate);

                if (config.isValidate) {
                    Form.event.validator(form, config.validator, config.before, config.success, config.error);
                } else {
                    form.submit(function (e) {
                        e.preventDefault();
                        Form.api.submit(form, config.before, config.success, config.error);
                    });
                }
                Form.event.selectpage(form);
                Form.event.laydate(form);
                Form.event.fileSelect(form);
                Form.event.fileUpload(form);
                Form.event.filePreview(form);
                Form.event.tips(form);
                Form.event.more(form);
            },
            submit: function(form, before, success, error) {
                let btnSumit = $('[type=submit]', form);
                if (btnSumit.hasClass('disabled')) {
                    return false;
                }
                btnSumit.addClass('disabled');

                // 数据提交
                let url = form.attr('action');
                url = url ? url : location.href;
                let method = form.attr('method');
                method = method ? method : 'post';

                let obj = Util.ajax({
                    url: url,
                    type: method,
                    data: form.serializeArray(),
                    enableToken: true
                }, before,function (data,response) {
                    btnSumit.removeClass('disabled');

                    // 是否有设置响应成功后的回调
                    if (typeof success === "function") {
                        if (false===success(data,response)) {
                            return false;
                        }
                    }

                    if ($('.operatePage').length) {
                        layer.msg(response.msg || lang('Successful operation'), {time:2000, icon:1}, function () {
                            parent.layer.close(parent.layer.getFrameIndex(window.name));
                            parent.$('button[name="refresh"]').trigger('click');
                        });
                    } else {
                        layer.msg(response.msg || lang('Successful operation'), {time:2000, icon:1}, function () {
                            self.location=document.referrer;
                        });
                    }
                }, function (data) {
                    // 错误回调
                    btnSumit.removeClass('disabled');

                    // 是否有设置回调
                    if (typeof error === "function") {
                        if (false===error(data)) {
                            return false;
                        }
                    }
                    layer.msg(data.msg,{time:4000, icon:2});
                }, btnSumit);
            },
        },
        event: {
            validator: function (form, option, before, success, error) {
                option = option || {};
                let options = {
                    formClass: "n-default n-bootstrap",
                    msgClass: "n-bottom",
                    theme: 'bootstrap',
                    invalidClass: 'is-invalid',
                    target: function(elem){ // 自定义消息位置
                        let formitem = $(elem).closest('.form-group>div'),
                            msgbox = formitem.find('span.msg-box');
                        if (!msgbox.length) {
                            msgbox = $('<span class="msg-box"></span>').appendTo(formitem);
                        }
                        return msgbox;
                    },
                    valid: function(result) {
                        Form.api.submit(form, before, success, error);
                    },
                    invalid: function (result) {
                        if ($(result).find('.card.card-tabs').length==1) {
                            // is-invalid
                            var href = $('.nav-tabs').find('.nav-link.active').attr('href');
                            if ($(href).find('.is-invalid').length<=0) {
                                $('.nav-tabs').find('.nav-link:not(.active)').each(function (e) {
                                    href = $(this).attr('href');
                                    if ($(href).find('.is-invalid').length) {
                                        $(this).trigger('click');
                                        return false;
                                    }
                                });
                            }
                        }
                    }
                };
                form.validator($.extend(options, option));
            },
            selectpage: function (form) {
                if (form.find('.selectpage').length>0) {
                    // 加载selectpage插件
                    require(['selectpage'], function (undefined) {
                        let option = {
                            showField: 'name',
                            keyField: 'id',
                            searchField: 'name',    // ajax查询时，需要提交的查询字段，多个英文逗号分隔
                            data: [], // 格式：[{id:1,name:'张三',sex:'男'},{id:2,name:'李四',sex:'男'}]
                            selectOnly: false,
                            pagination: true,
                            listSize: 10,   // 列表显示的项目个数，其它的项目以滚动条滚动方式展现
                            multiple: false,
                            lang: 'cn',
                            maxSelectLimit: 10,
                            eAjaxSuccess: function (data) {
                                if (data.code && data.code!=200) {
                                    layer.alert(data.msg);
                                    return {list:[],totalRow:[]};
                                }
                                // 动态下拉返回值的格式化
                                data.list = typeof data.rows !== 'undefined' ? data.rows : [];
                                data.totalRow = typeof data.total !== 'undefined' ? data.total : data.list.length;
                                return data;
                            }
                        };
                        form.find('.selectpage').each(function (key,item) {
                            let id = $(this).attr('id'),data = $(this).data();
                            data = $.extend(false, option, data); // 通过input data属性覆盖默认属性，更多属性查看文档

                            // 自动加上域名
                            if (data.data.indexOf(Config.root_file)<0) {
                                data.data = Config.root_domain+(data.data.substr(0,1)=='/'?data.data:'/'+data.data);
                            }

                            // 判断是否自定义条件json类型
                            let jsonStr = data.params;
                            if (typeof jsonStr!=='function' && typeof jsonStr!=='undefined') {
                                data.params = function(){return jsonStr;}
                            }
                            $('#'+id).selectPage(data);
                        })
                    })
                }
            },
            laydate: function (form) {
                if (form.find('.laydate').length>0) {
                    // 加载laydate插件
                    require(['laydate'], function (Laydate) {
                        form.find('.laydate').each(function (idx,vo) {
                            let obj = {
                                elem: vo,
                                type: 'datetime',
                                trigger: 'click',
                                value: $(this).val()
                            };
                            obj = $.extend(obj, $(this).data());
                            Laydate.render(obj);
                        });
                    })
                }
            },
            fileSelect: function (form) { // 文件选择
                form.on('click', '.btn-imgSelect', function (e) {
                    let option = {
                        field: 'image',
                        mimetype: '*',
                        multiple: false,
                        fileNum: 10
                    };
                    option = $.extend(option, $(this).data());
                    let url = Util.setUrlParams({url:Config.root_domain+'/routine.attachment/select', query:option});
                    cmsOpen(url,lang('Select image'));
                })
            },
            fileUpload: function (form) { // 文件上传插件
                if (form.find('.btn-imgUpload').length>0) {
                    require(['jquery-fileupload'], function (undefined) { // 加载文件上传插件
                        form.find('.btn-imgUpload').each(function (index) {
                            if ($(this).parent().find('input[type=file]').length!=0) {
                                return true;
                            }
                            let option = {
                                url: Config.upload_url,
                                field: 'image',
                                mimetype: '*',
                                multiple: false
                            };
                            option = $.extend(option, $(this).data());
                            $(this).after('<input type="file" class="input-file'+index+'" style="display: none" name="files[]" '+(option.multiple==true?'multiple':'')+' accept="'+option.mimetype+'">');
                            $(this).bind('click', {}, function (e) {
                                if (!$(this).is('.disabled')) {
                                    $(this).next().click();
                                }
                            });

                            var chunkSize = 0; // 每次上次的分块字节
                            var error = 0; // 1-错误，0-继续上传分块
                            var count = 1;
                            option = {
                                url: Config.upload_url,
                                type: 'POST',
                                dataType: 'json', // 服务器返回的数据类型
                                autoUpload: true, // 选择文件后自动上传
                                mimetype: '*',  // 文件类型
                                size: Config.file_size || (2*1024*1024),
                                singleFileUploads: false,
                                fileNum: 10,
                                filesguid: Util.guid(),
                                maxChunkSize: Config && Config.chunk && Config.chunk==1 ? Config.chunk_size:0,  // 2MB
                                formData: function (form) { // 额外表单
                                    var allow = [];
                                    if (option && option.fields) {
                                        var arr = option.fields.split(',');
                                        if (arr.length>0) {
                                            $.each(form.serializeArray(), function (idx, item) {
                                                if ($.inArray(item.name,arr)!=-1) {
                                                    allow.push(item);
                                                }
                                            })
                                        }
                                    }
                                    return allow;
                                },
                                add: function (e, data) { // 文件添加验证
                                    let arr = option.mimetype.split(',');
                                    if (data.originalFiles.length > option.fileNum) {
                                        layer.msg(lang('Only %s file can be uploaded at a time!',[option.fileNum]),{time: 4000,icon:2});
                                        return false;
                                    }
                                    for (let idx in data.originalFiles) {
                                        var type = data.originalFiles[idx]['type'];
                                        if (option.mimetype.indexOf("/*") !== -1) {
                                            type = type.split('/');
                                            type[1] = '*';
                                            type = type.join('/');
                                        }
                                        if (option.mimetype!='*' && 0>$.inArray(type, arr)) {
                                            layer.msg(lang('Unsupported file suffix'), {time: 4000,icon:2});
                                            return false;
                                        }

                                        //文件大小判断
                                        if(data.originalFiles[idx].size > option.size) {
                                            layer.msg(lang('Please upload a leaflet that does not exceed %s',[(option.size/1024/1024).toFixed(2)+'M']),{time: 4000,icon:2});
                                            return false;
                                        }
                                    }
                                    count = 1;
                                    chunkSize = 0;
                                    data.submit();
                                },
                                progressall: function (e, data) { // 进度
                                    var progress = parseInt(data.loaded / data.total * 100, 10);
                                    var obj = $(e.target).parent().find('.btn-imgUpload');

                                    obj.addClass('disabled');

                                    var value = '';
                                    if (obj.is('input')) {
                                        value = obj.val();
                                        obj.val(progress+'%');
                                    } else {
                                        value = obj.html();
                                        obj.html(progress+'%');
                                    }
                                    if (!obj.data('value')) {
                                        obj.attr('data-value', value);
                                    }
                                },
                                done: function (e, data) { // 成功回调
                                    count = 1;
                                    var obj = $(e.target).parent().find('.btn-imgUpload');
                                    obj.val(obj.data('value'));
                                    obj.removeClass('disabled');
                                    if (data.result.code==200) {
                                        if (!data.result.data || data.result.data.length==0) {
                                            layer.msg(lang('Not uploaded successfully'),{time: 4000,icon:2});
                                            return false;
                                        }
                                        var paths = [];
                                        $.each(data.result.data, function (idx, item) {
                                            paths.push(item.path);
                                        })
                                        paths = paths.join(',');
                                        $('#'+option.field).val(paths);
                                        $('#'+option.field).trigger('change');
                                    } else {
                                        layer.alert(data.result.msg);
                                    }
                                },
                                chunksend: function (e, data) { // 分块上传前的回调，返回false，结束上传
                                    if (error) {
                                        return false;
                                    }
                                    data.data.append('chunksize', chunkSize += data.chunkSize);
                                    data.data.append('filesize', data.files[0].size);
                                    data.data.append('fileid', data.filesguid);
                                    data.data.append('fileindex', count);

                                    count++;
                                },
                                chunkdone: function (e, data) { // 每个分块上传完成的回调
                                    if (data.result.code!=200) {
                                        layer.alert(data.result.msg);
                                        error = 1;
                                        return false;
                                    }
                                },
                                fail: function (e, data) {
                                    if (data.maxChunkSize>0) {
                                        // 切片上传处理
                                        var url = Util.setUrlParams({url:data.url, query:{action:'clear',fileid:data.filesguid}})
                                        $.get({url: url});
                                    }
                                    count = 1;
                                }
                            };

                            option = $.extend(option, $(this).data());
                            $('.input-file'+index).fileupload(option);
                        })
                    })
                }
            },
            filePreview: function (form) { // 文件input框更改事件,预览
                form.on('change','.txt-files', function (e) {
                    if (!$(this).parent().parent().find('.file-preview')) {
                        return false;
                    }
                    let imgStr = $(this).val();
                    if (imgStr.length==0) {
                        return false;
                    }
                    let arr = imgStr.split(',');
                    let html = '';
                    for (var idx in arr) {
                        html += '<div class="col-md-3">\n' +
                            '<a href="'+arr[idx]+'" target="_blank"><img src="'+Config.cdn_url+arr[idx]+'" class="img-thumbnail"></a>\n' +
                            '<a href="#" class="btn btn-danger btn-xs preview-del mt-2" data-index="'+idx+'"><i class="fas fa-trash-alt"></i></a>\n' +
                            '</div>';
                    }
                    $(this).parent().parent().find('.file-preview').html(html);
                });
                form.find('.txt-files').trigger('change');
                form.on('click', '.preview-del', function (e) {
                    let obj = $(this).parents('.fileGroup').find('.txt-files');
                    let arr = obj.val().split(',');
                    let index = $(this).parents('.fileGroup').find('.preview-del').index(this)
                    arr.splice(index,1);
                    obj.val(arr.join(','));
                    $(this).parent().remove();
                });
            },
            tips: function (form) {
                var tips_index = 0;
                form.on('mouseover', '.form-tips span', function (e) {
                    tips_index = layer.tips($(this).attr('title'), this, {
                        tips: 2,
                        time: 0
                    });
                }).on('mouseleave', '.form-tips span', function(){
                    layer.close(tips_index);
                });
            },
            more: function (form) {
            }
        }
    }
    return Form;
});

require(['jquery','bootstrap'], function ($) {
    // 初始化
    if ($(window).width()>767) {
        $('#navbarSupportedContent').find('.dropdown>.nav-link').removeAttr('data-toggle');
    }
    $('.userMenuBtn').click(function (e) {
        if ($(this).find('.fas').is('.fa-bars')) {
            $('#userMenu').addClass('show');
            $(this).find('.fas').removeClass('fa-bars').addClass('fa-times');
        } else {
            $('#userMenu').removeClass('show');
            $(this).find('.fas').removeClass('fa-times').addClass('fa-bars');
        }
    });
})