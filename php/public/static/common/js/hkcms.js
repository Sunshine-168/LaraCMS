define('hkcms', ['jquery','bootstrap','layer'], function ($, undefined,Layer) {
    var hkcms = {
        init: function () { // 初始化
            // 增加弹出框
            $(document).on('click', '[data-toggle="open"]', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var url = $(this).data('url');
                var title = $(this).data('title');
                title = title ? title : '信息';
                if (url) {
                    hkcms.api.open(url, title);
                }
            });
        },
        api: {
            // JS内使用语言包，PHP需要提前将语言包赋值给JS Lang 变量
            lang: function (name, arr) {
                var langKey = name.toLowerCase();
                name = Lang[langKey] ? Lang[langKey] : name;
                if (arr) {
                    for(var key in arr) {
                        name = name.replace('%s', arr[key]);
                    }
                }
                return name;
            },
            // 获取唯一值
            guid: function () {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
                    return v.toString(16);
                });
            },
            // 通用ajax请求,options 选项，before 提交前的处理，success 状态码200的回调，error 状态码非200的回调，btnSumit 提交按钮jq对象用于实现提交中不可在点击
            ajax: function (options, before, success, error, btnSumit) {
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
                            var txt = e.statusText;
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
            },
            // 获取地址栏参数
            getUrlParams: function (url) {
                url = url || window.location.search; //获取url中"?"符后的字串
                var params = {};
                var urlidx = url.indexOf("?");
                if (urlidx != -1) {
                    var str = url.substr(urlidx+1);
                    str = str.split("&");
                    for(var i = 0; i < str.length; i ++) {
                        params[str[i].split("=")[0]]=decodeURI(str[i].split("=")[1]);
                    }
                }
                return params;
            },
            // 追加url参数，options object {url:'', query:[]}
            setUrlParams: function (options) {
                var {url,query} = options;
                if(query) {
                    var queryArr = [];
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
            },
            open: function (url, title, options,callback) {
                options = options ? options : {};
                // 弹出框统一增加popup参数
                var urlPar = hkcms.api.getUrlParams(url);
                if (!(urlPar && urlPar.popup)) {
                    url = hkcms.api.setUrlParams({url:url,query:{popup:1}});
                }

                // 是否默认全屏,2=全屏
                var area = Tpl.popup==1 ? [$(top.window).width() > 800 ? '800px' : '90%', $(top.window).height() > 600 ? '600px' : '90%'] : ['100%', '100%'];

                var defaultOptions = {
                    type: 2,
                    title: title,
                    shadeClose: false,
                    shade: false,
                    area: area,
                    maxmin: true,
                    resize: true,
                    moveOut: true,
                    content: url,
                    zIndex: top.layer.zIndex, //重点1
                    success: (layero) => {
                        top.layer.setTop(layero);

                        // 解决全屏下无法正常缩小问题
                        if (typeof options.area =="object" && options.area[0]=='100%' && options.area[1]=='100%') {
                            var width = $(top.window).width();
                            var height = $(top.window).height();
                            var area = [
                                width > 800 ? 800 : width*0.9,
                                height > 600 ? 600 : height*0.9,
                                (height - (height > 600 ? 600 : height*0.9))/2,
                                (width - (width > 800 ? 800 : width*0.9))/2
                            ];
                            layero.find('.layui-layer-max').addClass('layui-layer-maxmin');
                            layero.attr({area: area});
                        }

                        // 页面关闭后的处理
                        top.$(layero).data('page', function () {
                            if ($('button[name="refresh"]').length>0) {
                                $('button[name="refresh"]').trigger('click');
                            } else if ($('.btn-refresh').length>0) {
                                $('.btn-refresh').trigger('click');
                            } else {
                                window.location.reload();
                            }
                        });
                        top.$(layero).data('callback', callback);
                    }
                };

                options = $.extend(defaultOptions, options);
                return top.layer.open(options);
            }
        }
    };

    // 暴露到全局
    window.lang = hkcms.api.lang;
    window.hkcms = hkcms;
    // 兼容旧版
    window.Util = hkcms.api;
    window.cmsOpen = hkcms.api.open;

    // 初始化
    hkcms.init();

    return hkcms;
})