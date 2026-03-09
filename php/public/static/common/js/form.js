define('form', ['jquery','layer','validator','validatorLang'], function ($, undefined) {
    var Form = {
        config: {
            frmOperate: '.frm-operate', // 表单标签class
            btnSubmit: '.btn-submit', // 表单提交按钮class
            isValidate: true, // 是否开启验证
        },
        api: {
            init: function (config) {
                config = config ? config : {};
                config = $.extend(Form.config, config);
                var form = typeof config.frmOperate == 'object' ? config.frmOperate : $(config.frmOperate);

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
                Form.event.keyvalue(form);
                Form.event.selectpicker(form);
                Form.event.more(form);
            },
            submit: function(form, before, success, error) {
                var btnSumit = $('[type=submit]', form);
                if (btnSumit.hasClass('disabled')) {
                    return false;
                }
                btnSumit.addClass('disabled');

                // 数据提交
                var url = form.attr('action');
                url = url ? url : location.href;
                var method = form.attr('method');
                method = method ? method : 'post';

                var obj = hkcms.api.ajax({
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

                    if (self != top) { // iframe
                        if ($('.operatePage').length) {
                            layer.msg(response.msg, {time:2000, icon:1}, function () {
                                var index = parent.layer.getFrameIndex(window.name);

                                // 获取页面关闭后的处理
                                var page_callback = parent.$("#layui-layer" + index).data("page");
                                parent.layer.close(index);
                                if (page_callback) {
                                    page_callback();
                                }
                            });
                        } else {
                            // 返回上一页
                            layer.msg(response.msg, {time:2000, icon:1}, function () {
                                self.location = document.referrer;
                            });
                        }
                    } else {
                        // 刷新当前页
                        layer.msg(response.msg, {time:2000, icon:1}, function () {
                            window.location.reload();
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
                var options = {
                    formClass: "n-default n-bootstrap",
                    msgClass: "n-bottom",
                    theme: 'bootstrap',
                    invalidClass: 'is-invalid',
                    target: function(elem){ // 自定义消息位置
                        var formitem = $(elem).closest('.form-group>div'),
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
                        var option = {
                            showField: 'name',
                            keyField: 'id',
                            // searchField: 'name',    // ajax查询时，需要提交的查询字段，多个英文逗号分隔(废弃默认，默认与showField一致，可外面指定)
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
                            var id = $(this).attr('id'),data = $(this).data();
                            data = $.extend(false, option, data); // 通过input data属性覆盖默认属性，更多属性查看文档

                            // 自动加上域名
                            if (data.data.indexOf(Config.root_file)<0) {
                                data.data = Config.root_domain+(data.data.substr(0,1)=='/'?data.data:'/'+data.data);
                            }

                            // 判断是否自定义条件json类型
                            var jsonStr = data.params;
                            if (typeof jsonStr!=='function' && typeof jsonStr!=='undefined') {
                                data.params = function(){return jsonStr;}
                            }
                            $('#'+id).selectPage(data);
                        })
                    })
                }
            },
            selectpicker: function (form) {
                if (form.find('.selectpicker').length>0) {
                    // 加载selectpicker下拉插件
                    require(['selectpicker'], function (undefined) {
                        var option = {
                            style: "form-control",
                            noneSelectedText: lang('Please choose'), // 多重选择没有选定选项时显示的文本。
                        }

                        $.fn.selectpicker.defaults = {
                            noneSelectedText: '没有选中任何项',
                            noneResultsText: '没有找到匹配项',
                            countSelectedText: '选中{1}中的{0}项',
                            maxOptionsText: ['超出限制 (最多选择{n}项)', '组选择超出限制(最多选择{n}组)'],
                            multipleSeparator: ', ',
                            selectAllText: '全选',
                            deselectAllText: '取消全选'
                        };

                        form.find('.selectpicker').each(function (key,item) {
                            var id = $(this).attr('id'),data = $(this).data();
                            data = $.extend(false, option, data);

                            $('#'+id).selectpicker(data);
                        })
                    });
                }
            },
            laydate: function (form) {
                if (form.find('.laydate').length>0) {
                    // 加载laydate插件
                    require(['laydate'], function (Laydate) {
                        form.find('.laydate').each(function (idx,vo) {
                            var obj = {
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
                    var option = {
                        field: 'image',
                        mimetype: '',
                        multiple: false,
                        fileNum: 10
                    };
                    option = $.extend(option, $(this).data());
                    option.mimetype = option.mimetype.replace("*", "");
                    var url = hkcms.api.setUrlParams({url:Config.root_domain+'/routine.attachment/select', query:option});

                    var _this = this;
                    hkcms.api.open(url,lang('Select image'),{},function (arr) {
                        var field = $(_this).data('field');
                        if (field) {
                            var multiple = $(_this).data('multiple');
                            if (multiple == 'multiple' || multiple == 1) {
                                var oldfiles = $('#' + field).val();
                                if ($('#' + field).parent().parent().hasClass('file-json')) { // json
                                    oldfiles = oldfiles.length == 0 ? [] : JSON.parse(oldfiles);
                                    for (const idx in arr) {
                                        oldfiles.push({file: arr[idx], info: ""})
                                    }
                                } else { // 逗号分隔模式
                                    oldfiles = oldfiles.length == 0 ? [] : oldfiles.split(',');
                                    for (const idx in arr) {
                                        oldfiles.push(arr[idx])
                                    }
                                }
                                // 判断是否超过最大可上传图片数
                                if (oldfiles.length > option.fileNum) {
                                    layer.msg(lang('Only %s file can be uploaded at a time!', [option.fileNum]), {
                                        time: 4000,
                                        icon: 2
                                    })
                                    return false;
                                }
                                $('#' + field).val($('#' + field).parent().parent().hasClass('file-json') ? JSON.stringify(oldfiles) : oldfiles.join(','));
                            } else {
                                $('#' + field).val(arr.join(','))
                            }

                            // 触发change事件
                            $('#' + field).trigger('change');
                        }
                    });
                })
            },
            fileUpload: function (form) { // 文件上传插件
                if (form.find('.btn-imgUpload').length>0) {
                    require(['jquery-fileupload'], function (undefined) { // 加载文件上传插件
                        form.find('.btn-imgUpload').each(function (index) {
                            if ($(this).parent().find('input[type=file]').length!=0) {
                                return true;
                            }
                            var option = {
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
                                    var arr = option.mimetype.split(',');
                                    if (data.originalFiles.length > option.fileNum) {
                                        layer.msg(lang('Only %s file can be uploaded at a time!',[option.fileNum]),{time: 4000,icon:2});
                                        return false;
                                    }
                                    for (var idx in data.originalFiles) {
                                        // 文件格式限制
                                        if (option.mimetype.indexOf("/")===-1) { // .jpg,.png格式
                                            var index1 = data.originalFiles[idx]['name'].lastIndexOf(".");
                                            var index2 = data.originalFiles[idx]['name'].length;
                                            var ext = data.originalFiles[idx]['name'].substring(index1,index2);
                                            if (option.mimetype!='*' && 0>$.inArray(ext, arr)) {
                                                layer.msg(lang('Unsupported file suffix'), {time: 4000,icon:2});
                                                return false;
                                            }
                                        } else {
                                            // image/png 格式判断
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
                                        }

                                        //文件大小判断
                                        if(data.originalFiles[idx].size > option.size) {
                                            layer.msg(lang('Please upload a leaflet that does not exceed %s',[(option.size/1024).toFixed(2)+'KB']),{time: 4000,icon:2});
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

                                    if (obj.is('input')) {
                                        obj.val(obj.data('value'));
                                    } else {
                                        obj.html(obj.data('value'));
                                    }

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
                                        var newfilesnum = paths.length;

                                        // 多文件模式
                                        var multiple = $(e.target).attr('multiple');
                                        if (multiple=='multiple' || multiple==1) {

                                            var oldfiles = $('#'+option.field).val();
                                            if ($('#'+option.field).parent().parent().hasClass('file-json')) { // json
                                                oldfiles = oldfiles.length == 0 ? [] : JSON.parse(oldfiles);
                                                for (const idx in paths) {
                                                    oldfiles.push({file: paths[idx], info: ""})
                                                }
                                            } else { // 逗号分隔模式
                                                oldfiles = oldfiles.length == 0 ? [] : oldfiles.split(',');
                                                for (const idx in paths) {
                                                    oldfiles.push(paths[idx])
                                                }
                                            }
                                            // 判断是否超过最大可上传图片数
                                            if (oldfiles.length > option.fileNum) {
                                                layer.msg(lang('Only %s file can be uploaded at a time!', [option.fileNum]), {
                                                    time: 4000,
                                                    icon: 2
                                                })
                                                return false;
                                            }
                                            $('#'+option.field).val($('#'+option.field).parent().parent().hasClass('file-json') ? JSON.stringify(oldfiles) : oldfiles.join(','));
                                        } else {
                                            $('#'+option.field).val(paths.join(','));
                                        }
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

                                    layer.alert(data._response.jqXHR.responseJSON.message);
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
                    var imgStr = $(this).val();
                    if (imgStr.length==0) {
                        return false;
                    }

                    // 判断是否是：base图片
                    var html = '';
                    if (/^\s*data:([a-z]+\/[a-z0-9-+.]+(;[a-z-]+=[a-z0-9-]+)?)?(;base64)?,([a-z0-9!$&',()*+;=\-._~:@\/?%\s]*?)\s*$/i.test(imgStr)) {
                        html += '<div class="col-md-3">\n' +
                            '<a href="'+imgStr+'" target="_blank"><img src="'+imgStr+'" class="img-thumbnail"></a>\n' +
                            '<a href="#" class="btn btn-danger btn-xs preview-del mt-2" data-index="0"><i class="fas fa-trash-alt"></i></a>\n' +
                            '</div>';
                        $(this).parent().parent().find('.file-preview').html(html);
                        return true;
                    }

                    // json格式。多图、多文件组件
                    if ($(this).parent().parent().hasClass('file-json')) {
                        var arr = JSON.parse(imgStr);
                    } else {
                        var arr = imgStr.split(',');
                        var jsonObj = []
                        for (var idx in arr) {
                            jsonObj.push({
                                file: arr[idx],
                                info: ""
                            })
                        }
                        arr = jsonObj
                    }

                    var reg = /^(http|https):\/\//;
                    var file = '';
                    var ext = '';

                    for (var idx in arr) {
                        file = reg.test(arr[idx].file) ? arr[idx].file : Config.cdn_url+arr[idx].file;
                        if (file.length==0) {
                            continue;
                        }
                        file = file.split('?');
                        file = file[0];
                        ext = file.split('.').pop().toLowerCase();
                        html += '<div class="col-md-3 file-item">\n';
                        if (ext=='mp4') {
                            html += '<a href="'+arr[idx].file+'" target="_blank"><img src="'+Config.static_path+'/img/video.png" class="img-thumbnail"></a>\n';
                        } else if ($.inArray(ext, ['png','jpg','jpeg','gif','bmp','ico','webp'])!=-1) {
                            html += '<a href="'+arr[idx].file+'" target="_blank"><img src="'+(file)+'" class="img-thumbnail"></a>\n';
                        } else {
                            html +='<a href="'+arr[idx].file+'" target="_blank"><img src="'+Config.static_path+'/img/zip.png" class="img-thumbnail"></a>\n';
                        }
                        html += '<a href="#" class="preview-del" data-index="'+idx+'"><i class="fas fa-times"></i></a>\n';
                        // '<textarea cols="1" rows="5" data-path="'+arr[idx]+'" class="form-control form-control-sm edit-image-remark" style="height: 28px;" placeholder="'+lang('Remark')+'"></textarea>\n' +
                        // 是否json对象
                        if ($(this).parent().parent().hasClass('file-json')) {
                            html += '<textarea cols="1" rows="5" data-index="'+idx+'" class="form-control form-control-sm edit-image-remark" style="height: 28px;" placeholder="'+lang('Remark')+'">'+arr[idx].info+'</textarea>\n';
                        }
                        // 是否开启移动排序
                        if ($(this).parent().parent().hasClass('file-sortable')) {
                            html += '<a href="#" class="preview-arrows-alt" data-index="'+idx+'"><i class="fas fa-arrows-alt"></i></a>\n'
                        }
                        html += '</div>';
                    }
                    $(this).parent().parent().find('.file-preview').html(html);
                });
                
                // 格式化多文件、多图片组件
                form.find('.file-json').each(function (idx, vo) {
                    var txtVal = $(this).find('.txt-files').val();
                    if (txtVal.length==0) {
                        return false;
                    }
                    try {
                        JSON.parse(txtVal);
                    } catch (error) {
                        var arr = txtVal.split(',');
                        var jsonObj = []
                        for (idx in arr) {
                            jsonObj.push({
                                file: arr[idx],
                                info: ""
                            })
                        }
                        $(this).find('.txt-files').val(JSON.stringify(jsonObj))
                    }
                })
                
                form.find('.txt-files').trigger('change');
                // 删除事件
                form.on('click', '.preview-del', function (e) {
                    var obj = $(this).parents('.fileGroup').find('.txt-files');
                    // json格式。多图、多文件组件
                    var arr = obj.val();
                    if ($(this).parents('.fileGroup').hasClass('file-json')) {
                        arr = JSON.parse(arr);
                    } else {
                        arr = arr.split(',');
                    }
                    var index = $(this).parents('.fileGroup').find('.preview-del').index(this)
                    arr.splice(index,1);
                    obj.val($(this).parents('.fileGroup').hasClass('file-json') ? (arr.length==0 ? "" : JSON.stringify(arr)) : arr.join(','));
                    $(this).parent().remove();
                });
                // 开启拖动排序
                if (form.find('.file-sortable').length>0) {
                    require(['jquery-ui'], function (undefined) {
                        form.find('.file-sortable').each(function (idx, vo) {
                            $(this).sortable({cursor:"move",items:".file-item",opacity:0.6,stop:function (e, ui) {
                                var curVal = $(e.target).find('.txt-files').val();
                                if ($(e.target).hasClass('file-json')) {
                                    curVal = JSON.parse(curVal);
                                } else {
                                    curVal = curVal.split(',');
                                }
                                var newVal = []
                                $(e.target).find(".preview-del").each(function (idx, vo){
                                    newVal.push(curVal[$(this).data('index')])
                                })
                                $(e.target).find('.txt-files').val($(e.target).hasClass('file-json') ? JSON.stringify(newVal) : newVal.join(','))
                                $(e.target).find('.txt-files').trigger('change');
                            }});
                        })
                    })
                }
                // 备注更新事件
                form.on('change', '.edit-image-remark', function (e) {
                    var obj = $(this).parent().parent().parent().find('.txt-files');
                    var curVal = JSON.parse(obj.val());
                    curVal[$(this).data('index')].info = $(this).val()
                    obj.val(JSON.stringify(curVal))
                })
            },
            tips: function (form) {
                var tips_index = 0;
                form.on('mouseover', '.form-tips span', function (e) {
                    if ($(this).data('tips')) {
                        tips_index = layer.tips($(this).data('tips'), this, {
                            tips: 2,
                            time: 0
                        });
                    } else if (typeof($(this).attr('title'))!= "undefined") {
                        tips_index = layer.tips($(this).attr('title'), this, {
                            tips: 2,
                            time: 0
                        });
                    }
                }).on('mouseleave', '.form-tips span', function(){
                    layer.close(tips_index);
                });
            },
            keyvalue: function (form) {
                if (form.find('.keyvalue').length>0) {
                    require(['jquery-ui'], function (undefined) {
                        // 更新
                        var updateJson = function (e) {
                            var arr = {};
                            e.children('.row').each(function (idx, item) {
                                var tmp = $(this).find('[data-name="keyvalue-key"]').val();
                                if (tmp) {
                                    arr[tmp] = $(this).find('[data-name="keyvalue-value"]').val();
                                }
                            })
                            e.parent().find('.key-value-textarea').val($.isEmptyObject(arr)?'':JSON.stringify(arr));
                        };

                        form.find('.keyvalue').each(function (idx, item) {
                            updateJson($(this).find('.keyvalue-item'));
                            $(this).find('.keyvalue-item').sortable({update: function (e) {
                                    updateJson($(this));
                                }});
                        });

                        // 删除行
                        $(document).on('click', '.btn-keyvalue-row-del', function (e) {
                            var obj = $(this).closest('.keyvalue-item');
                            $(this).parent().parent().remove();
                            updateJson(obj);
                            e.preventDefault();
                        })
                        // 添加行
                        $(document).on('click', '.btn-keyvalue-row-add', function (e) {
                            var obj = $(this).closest('.keyvalue');
                            var html = obj.find('.keyvalue-template').html();
                            obj.find('.keyvalue-item').append(html);
                            e.preventDefault();
                        })

                        // 值更改
                        $(document).on('change keyup', ".keyvalue .keyvalue-item input", function () {
                            updateJson($(this).closest('.keyvalue-item'))
                        });

                        // 参数配置
                        $(document).on('click','.btn-key-config',function () {
                            var obj = $(this).closest('.keyvalue');
                            var val = obj.find('.key-value-textarea').val();
                            if (val) {
                                $.post($(this).data('url'),{val:val,field:obj.find('.key-value-textarea').data('field'),model_id:$('#model_id').val()},function (e) {
                                    layer.msg(e.msg);
                                });
                            } else {
                                layer.msg("请点追加按钮追加数据后再试~");
                            }
                        })
                    });
                }
            },
            more: function (form) {
            }
        }
    }
    return Form;
});