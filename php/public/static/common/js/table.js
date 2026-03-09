define('table', ['jquery','bootstrap-table','bootstrap-table-lang','moment'], function ($, undefined, undefined, Moment) {
    var Table = {
        defaults: { // 对应bootstraptable属性，表格选项,(doc: https://www.bootstrap-table.com.cn/doc/api/table-options/)
            classes: 'table table-bordered table-hover table-striped',
            theadClasses: '',
            sortName: 'weigh', // 排序字段
            sortOrder: 'asc',
            url:'',
            method: 'get',
            pagination: true, //为在表格底部显示分页工具栏
            sidePagination: 'server', // 服务端分页
            pageSize: Tpl && Tpl.page && Tpl.page>10 ? parseInt(Tpl.page) : 10, // 初始分页大小
            pageList: [10, 15, 25, 50, 100, 'all'], // all 显示所有
            paginationLoop: true, // 分页连续循环模式。
            search: false, // 快速搜索，formatSearch:function(){return"Search"},更改提示
            toolbar:'#toolbar', //工具栏
            showColumns: true, // 显示行下拉选项
            showRefresh: true, // 显示表格刷新
            showToggle: true,   // 卡片/表格切换
            showFullscreen: false, // 全屏按钮
            escape: true, // 转义用于插入HTML的字符串，并替换 &, <, >, “, `, and ‘ 字符
            clickToSelect: true, // 行点击选中
            singleSelect: false, // 是否开启单选
            multipleSelectRow: false, // 设置true以启用多选行。可以使用ctrl键单击以选择一行，或使用shift键单击以选择一系列行
            pk: 'id',
            onDblClickRow: function(row,obj,name) {
                $(obj).find('.btn-row-edit').trigger('click');
            },
            responseHandler: function(res) {
                if (res && res.code && res.code===-1000) {
                    layer.msg(res.msg, {time: 4000,icon:2});
                    return [];
                }
                if (res.rows.length<=0 && res.total) {
                    return [];
                }
                return res;
            },
            queryParams: function(params) {
                var exp = {};
                $('.filter-panel').find('[data-op]').each(function (id, vo) {
                    var name = $(this).attr('name');
                    if (typeof name == 'undefined'){
                        return true;
                    }
                    if ($.inArray($(this).data('op').toUpperCase(), ['BETWEEN','NOT BETWEEN','BETWEEN TIME','NOT BETWEEN TIME'])>=0 && !$(this).hasClass('laydate')) {
                        var start = $('#'+$(this).attr('name')+'_start').val() ? $('#'+$(this).attr('name')+'_start').val() : '';
                        var end = $('#'+$(this).attr('name')+'_end').val() ? $('#'+$(this).attr('name')+'_end').val() : '';
                        if (!start && !end) {
                            $(this).val('');
                        } else {
                            $(this).val(start+' - '+end);
                        }
                    }
                    if (($(this).data('op').toUpperCase()=='IN' || $(this).data('op').toUpperCase()=='NOT IN') && $(this).hasClass('selectpage')) {
                        var str = $(this).attr('id');
                        if (str.indexOf('_text')>=0) {
                            str = str.substr(0, str.length-5);
                        }
                        name = $('#'+str).attr('name');
                    }
                    exp[name] = $(this).data('op');
                });

                params.filter = $('.frm-filter').serialize();
                params.op = JSON.stringify(exp);

                var lan = $('.J-hk-contentLang').val();
                if (!lan) {
                    lan = Config.content_lang_mode
                }
                params.clang = lan;
                return params;
            }
        },
        columnDefaults: {   // 列表选项
            align: 'center',
            valign: 'middle'
        },
        config: {
            table: '#table',
            disabledbtn: '.btn-disabled', // 禁用按钮
            btnAdd: '.btn-add',  // 添加按钮标识
            btnEdit: '.btn-edit',  // 添加按钮标识
            btnDel: '.btn-del',  // 删除按钮
            btnRecycle: '.btn-recycle',  // 回收站按钮
            btnRestoreAll: '.btn-restoreAll',  // 还原全部
            btnRestore: '.btn-restore',  // 还原
            btnDestroyAll: '.btn-destroyAll',  // 销毁全部
            btnDestroy: '.btn-destroy',  // 销毁全部
        },
        init: function(customDefault,customColumnDefaults,config){

            customDefault = customDefault ? customDefault : {};
            customColumnDefaults = customColumnDefaults ? customColumnDefaults : {};
            config = config ? config : {};

            // 合并覆盖
            $.extend($.fn.bootstrapTable.defaults, this.defaults, customDefault);
            $.extend(true, $.fn.bootstrapTable.columnDefaults, this.columnDefaults, customColumnDefaults);
            $.extend(this.config, config);
            var table = $(this.config.table);
            $.extend(true,$.fn.bootstrapTable.columnDefaults, {table:table});
            table.bootstrapTable();
            return this.run(table);
        },
        api: {
            getSelectionsId: function(table, isStr) {
                var ids = table.bootstrapTable('getSelections');
                if (ids.length===0) {
                    layer.msg(lang('Please select a record line'),{time: 4000,icon:2});
                    return false;
                }
                var idsArr = [];
                $.each(ids, function (key, value) {
                    idsArr.push(value.id);
                });
                if (isStr) {
                    ids = idsArr.join(',');
                } else {
                    ids = idsArr;
                }
                return ids;
            }
        },
        run: function (table) {
            var that = this;
            // 获取选项
            var options = table.bootstrapTable('getOptions');
            // 内容多语言切换.
            if (Config.content_lang_on==1 && options.contentLangSw) {
                var html = '';
                $.each(Config.content_lang_list, function (idx, vo) {
                    html += '<option value="'+vo['mark']+'" '+(Config.content_lang_mode==vo['mark']?'selected':'')+'>'+(Config.content_lang_mode==vo['mark']?"("+lang('Current')+")"+vo['title']:vo['title'])+'</option>';
                })
                $('.J-hk-contentLang').html(html);
                $('.J-hk-contentLang').attr('data-toggle','tooltip');
                $('.J-hk-contentLang').attr('data-original-title',lang('Only for query, will not affect edit mode'));
                $('.J-hk-contentLang').removeClass('d-none');
                $(document).on('change', '.J-hk-contentLang', function (e) {
                    table.bootstrapTable('refresh');
                });
            }

            // checkbox 选中事件、取消、全选事件
            table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function (e) {
                var ids = table.bootstrapTable('getSelections');
                $(that.config.disabledbtn, options.toolbar).toggleClass('disabled', !ids.length);
            });
            //当刷新表格时
            table.on('refresh.bs.table', function (e, settings, data) {
                $(that.config.disabledbtn, options.toolbar).addClass("disabled");
            });
            // 重载页面
            $(options.toolbar).on('click','.btn-refresh',function (e) {
                //window.location.reload();
                table.bootstrapTable('refresh');
            });

            // 渲染完成后
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                $('[data-toggle="tooltip"]').tooltip();
                $(that.config.disabledbtn, options.toolbar).addClass("disabled");
            });

            // 工具栏【增、改、删、导入、导出、状态更改、回收站】
            $(options.toolbar).on('click',this.config.btnAdd,function (e) {
                var data = $(this).data();

                if (options && options.addCallback && typeof options.addCallback === "function") { // 回调
                    var res = options.addCallback.call(e, data);
                    if (res===false) {
                        return false;
                    }
                }
                if (data.popup===false) {
                    // 新页面
                    window.location.href = Util.setUrlParams({url:data.url, query:{popup:0}});
                } else {
                    cmsOpen(Util.setUrlParams({url:data.url, query:{popup:1}}),lang('Add'))
                }
            });

            // 修改
            $(options.toolbar).on('click',this.config.btnEdit,function (e) {
                if ($(this).hasClass('disabled')) {
                    return false;
                }

                var ids = Table.api.getSelectionsId(table);
                var data = $(this).data();

                if (options && options.editCallback && typeof options.editCallback === "function") { // 回调
                    var res = options.editCallback.call(e, data, ids);
                    if (res===false) {
                        return false;
                    }
                }

                if (data.popup===false) {
                    // 新页面
                    if (ids.length>1) {
                        layer.msg(lang('Only one line of record can be operated~'),{time: 4000,icon:2});
                        return false;
                    }
                    window.location.href = Util.setUrlParams({url:data.url, query:{popup:0,id:ids[0]}});
                } else {
                    $.each(ids, function (key, value) {
                        cmsOpen(Util.setUrlParams({url:data.url, query:{popup:1,id:value}}), lang('Edit'));
                    });
                }
            });

            // 删除
            $(options.toolbar).on('click',this.config.btnDel,function (e) {
                if ($(this).hasClass('disabled')) {
                    return false;
                }

                var ids = Table.api.getSelectionsId(table,true);
                var url = Util.setUrlParams({url:$(this).data('url'), query:{ids:ids}});

                //询问框
                layer.confirm(lang('Confirm operation?'), {
                    title: lang('Delete'),
                    btn: [lang('Confirm'),lang('Cancel')] //按钮
                }, function(){
                    Util.ajax({url:url,type:"post"},'',function (data,res) {
                        layer.msg(res.msg,{time:1000, icon:1},function (e) {
                            table.bootstrapTable('refresh');
                        });
                    });
                });
            });

            // 回收站
            $(options.toolbar).on('click', this.config.btnRecycle, function (e) {
                var data = $(this).data();

                if (options && options.recycleCallback && typeof options.recycleCallback === "function") { // 回调
                    var res = options.recycleCallback.call(e, data);
                    if (res===false) {
                        return false;
                    }
                }
                if (data.popup===false) {
                    // 新页面
                    window.location.href = Util.setUrlParams({url:data.url, query:{popup:0}});
                } else {
                    cmsOpen(Util.setUrlParams({url:data.url, query:{popup:1}}),lang('Recycle'))
                }
            });

            // 还原全部
            $(options.toolbar).on('click', this.config.btnRestoreAll, function (e) {
                var that = this;
                //询问框
                layer.confirm(lang('Are you sure to restore everything?'), {
                    btn: [lang('Confirm'),lang('Cancel')] //按钮
                }, function(){
                    Util.ajax({url:$(that).data('url')},'',function (data,res) {
                        layer.msg(res.msg,{time:1000,icon:1},function (e) {
                            table.bootstrapTable('refresh');
                        });
                    });
                });
            });
            // 还原选中项
            $(options.toolbar).on('click', this.config.btnRestore, function (e) {
                if ($(this).hasClass('disabled')) {
                    return false;
                }
                var ids = Table.api.getSelectionsId(table,true);
                var url = Util.setUrlParams({url:$(this).data('url'), query:{ids:ids}});
                layer.confirm(lang('Are you sure to restore the selected items?'), {
                    btn: [lang('Confirm'),lang('Cancel')] //按钮
                }, function(){
                    Util.ajax({url:url},'',function (data,res) {
                        layer.msg(res.msg,{time:1000,icon:1},function (e) {
                            table.bootstrapTable('refresh');
                        });
                    });
                });
            });

            // 销毁全部
            $(options.toolbar).on('click', this.config.btnDestroyAll, function (e) {
                var that = this;
                layer.confirm(lang('Are you sure to destroy the selected item? Document data will be included'), {
                    btn: [lang('Confirm'),lang('Cancel')] //按钮
                }, function(){
                    Util.ajax({url:$(that).data('url')},'',function (data,res) {
                        layer.msg(res.msg,{time:1000,icon:1},function (e) {
                            table.bootstrapTable('refresh');
                        });
                    });
                });
            });
            // 销毁选中项
            $(options.toolbar).on('click', this.config.btnDestroy, function (e) {
                if ($(this).hasClass('disabled')) {
                    return false;
                }
                var ids = Table.api.getSelectionsId(table,true);
                var url = Util.setUrlParams({url:$(this).data('url'), query:{ids:ids}});
                layer.confirm(lang('Are you sure to destroy the selected item? Document data will be included'), {
                    btn: [lang('Confirm'),lang('Cancel')] //按钮
                }, function(){
                    Util.ajax({url:url},'',function (data,res) {
                        layer.msg(res.msg,{time:1000,icon:1},function (e) {
                            table.bootstrapTable('refresh');
                        });
                    });
                });
            });

            // 状态更改
            $(options.toolbar).find('.btn-toggle').on('click','.dropdown-item.status',function (e) {
                var ids = Table.api.getSelectionsId(table,true);
                if (table.data('batches')) {
                    Util.ajax({url: table.data('batches'),data:{ids: ids,params:$(this).data('params')}},'',function (data,res) {
                        layer.msg(res.msg,{time:1000,icon:1},function (e) {
                            table.bootstrapTable('refresh');
                        });
                    })
                } else {
                    layer.msg(lang('Request address is empty'),{time:4000,icon:2});
                }
            })

            // 数据筛选
            if (options.customFilter) {
                $('.filter-panel .frm-filter').submit(function (e) {
                    table.bootstrapTable('refresh', {pageNumber: 1});
                    return false;
                });
                $(document).on('click','.btn-filter',function (e) {
                    $('.filter-panel').toggleClass('d-none');
                });
                if ($('.frm-filter .selectpage').length>0) {
                    require(['Form'], function (Form) {
                        Form.event.selectpage($('.frm-filter'));
                    })
                }
                if ($('.frm-filter .laydate').length>0) {
                    require(['Form'], function (Form) {
                        Form.event.laydate($('.frm-filter'));
                    })
                }
                // 重置表单
                $('.frm-filter').find("input[type=reset]").click(function (e) {
                    setTimeout(function () {
                        table.bootstrapTable('refresh');
                    }, 600);
                })
            }

            // 绑定文件上传事件
            if ($(options.toolbar).find('.btn-uploads').length>0) {
                require(['jquery-fileupload'], function (undefined) {
                    $(options.toolbar).on('click', '.btn-uploads', function (e) {
                        if ($(this).is('.disabled')) {
                            return false;
                        }

                        var chunkSize = 0; // 每次上次的分块字节
                        var error = 0; // 1-错误，0-继续上传分块
                        var count = 1;
                        var op = {
                            url: Config.upload_url,
                            type: 'POST',
                            dataType: 'json', // 服务器返回的数据类型
                            autoUpload: true, // 选择文件后自动上传
                            mimetype: '*',  // 文件类型
                            size: Config.file_size || (2*1024*1024),
                            singleFileUploads: false,
                            multiple: false,
                            fileNum: 10,
                            filesguid: Util.guid(),
                            maxChunkSize: Config && Config.chunk && Config.chunk==1 ? Config.chunk_size:0,  // 2MB
                            add: function (e, data) {
                                count = 1;
                                var arr = op.mimetype.split(',');
                                if (data.originalFiles.length > op.fileNum) {
                                    layer.msg(lang('Only %s file can be uploaded at a time!',[op.fileNum]),{time:4000,icon:2});
                                    return false;
                                }
                                for (var idx in data.originalFiles) {
                                    // 文件格式限制
                                    if (op.mimetype.indexOf("/")===-1) { // .jpg,.png格式
                                        var index1 = data.originalFiles[idx]['name'].lastIndexOf(".");
                                        var index2 = data.originalFiles[idx]['name'].length;
                                        var ext = data.originalFiles[idx]['name'].substring(index1,index2);
                                        if (op.mimetype!='*' && 0>$.inArray(ext, arr)) {
                                            layer.msg(lang('Unsupported file suffix'), {time: 4000,icon:2});
                                            return false;
                                        }
                                    } else {
                                        // image/png 格式判断
                                        var type = data.originalFiles[idx]['type'];
                                        if (op.mimetype.indexOf("/*") !== -1) {
                                            type = type.split('/');
                                            type[1] = '*';
                                            type = type.join('/');
                                        }

                                        if (op.mimetype!='*' && 0>$.inArray(type, arr)) {
                                            layer.msg(lang('Unsupported file suffix'), {time: 4000,icon:2});
                                            return false;
                                        }
                                    }

                                    //文件大小判断
                                    if(data.originalFiles[idx].size > op.size) {
                                        layer.msg(lang('Please upload a leaflet that does not exceed %s',[(op.size/1024).toFixed(2)+'kb']),{time:4000,icon:2});
                                        return false;
                                    }
                                }
                                data.submit();
                            },
                            progressall: function (e, data) {
                                var progress = parseInt(data.loaded / data.total * 100, 10);
                                var obj = $(e.target).parent().find('.btn-uploads');

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
                            done: function (e, data) {
                                count = 1;
                                var obj = $(e.target).parent().find('.btn-uploads');
                                if (obj.is('input')) {
                                    obj.val(obj.data('value'));
                                } else {
                                    obj.html(obj.data('value'));
                                }
                                obj.removeClass('disabled');
                                if (data.result.code==200) {
                                    if (!data.result.data || data.result.data.length==0) {
                                        layer.msg(lang('Not uploaded successfully'),{time:4000,icon:2});
                                        return false;
                                    }
                                    layer.msg(data.result.msg,{time:1000,icon:1},function (e) {
                                        table.bootstrapTable('refresh');
                                    });
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
                        op = $.extend(op, $(this).data());
                        $(this).after('<input type="file" class="toolbar-input-file" style="display: none" name="files[]" '+(op.multiple==true?'multiple':'')+' accept="'+op.mimetype+'">');
                        $(this).next().click();

                        $('.toolbar-input-file').fileupload(op);
                    });
                });
            }
            return table;
        },

        // 通用格式 输出
        formatter: {
            SN: function(value, row, index) {
                return index+1;
            },
            operate: function (value,row,index) {
                var table = this.table;
                var html = '';
                if (table.data('edit')) {
                    html += '<button type="button" class="btn btn-primary btn-xs btn-row-edit mr-2" title="'+lang('Edit')+'" data-popup="'+(typeof(this.popup) !== "undefined"?this.popup:'true')+'"><i class="fas fa-pen"></i></button>';
                }
                if (table.data('del')) {
                    html += '<button type="button" class="btn btn-danger btn-xs btn-row-del mr-2" title="'+lang('Delete')+'" data-tips="'+(this.delTips || '')+'"><i class="fas fa-trash-alt"></i></button>';
                }
                return html;
            },
            switchBtn: function (value, row, index, field) { // 切换按钮。1-打开 0-关闭
                var table = this.table;
                var checked = 1;
                if (!value || value===0 || value === '0' || value=='hidden') {
                    checked = 0;
                }

                if (table.data('batches')) {
                    return '<div class="custom-control custom-switch custom-control-sm">\n' +
                        '<input type="checkbox" class="custom-control-input" id="switch'+field+'_'+index+'" data-field="'+field+'" '+(checked===1?'checked':'')+' >\n' +
                        '<label class="custom-control-label" for="switch'+field+'_'+index+'"><div class="custom-control-label-dot"></div></label>\n' +
                        '</div>'
                } else {
                    return '<div class="custom-control custom-switch custom-control-sm">\n' +
                        '<input type="checkbox" class="custom-control-input" disabled id="switch'+field+'_'+index+'" data-field="'+field+'" '+(checked===1?'checked':'')+' >\n' +
                        '<label class="custom-control-label" for="switch'+field+'_'+index+'"><div class="custom-control-label-dot"></div></label>\n' +
                        '</div>'
                }
            },
            txtEditBtn: function (value, row, index, field) {
                var table = this.table;
                if (table.data('batches')) {
                    value = '<input value="'+value+'" data-field="'+field+'" data-id="'+row['id']+'" class="form-control form-control-sm btn-txtEditBtn" style="width: 50px;text-align: center;margin: 0 auto;padding: 2px 8px;" />';
                    return value;
                } else {
                    return value;
                }
            },
            textBox: function (value, row, index, field) { // 设置超过多少隐藏文本
                var textLength = typeof this.textLength === 'undefined' || this.textLength=='' ? 0 : this.textLength;
                var html = '';
                if (value.length>textLength) {
                    html += '<a class="btn btn-xs btn-default text-box-btn" data-field="'+this.field+'"><i class="fas fa-eye"></i></a>';
                } else {
                    html += value;
                }
                return html;
            },
            editor: function (value, row, index, field) { // 编辑器效果
                return '<a class="btn btn-xs btn-default editor-box-btn" data-field="'+this.field+'"><i class="fas fa-eye"></i></a>';
            },
            image: function (value, row, index, field) {
                if (!value) {
                    return '';
                }
                var html = '';
                var checkValue = value.toLowerCase()
                if(!/\.(gif|jpg|jpeg|png|ico|webp)$/.test(checkValue)) {
                    if (/\.mp4$/.test(checkValue)) {
                        html += '<a href="'+value+'" target="_blank"><img src="'+Config.static_path+'/img/video.png" data-toggle="tooltip" title="'+lang('Click to open')+'" data-url="'+value+'" style="width: auto;height:60px;object-fit: cover;cursor: pointer" class="img-thumbnail"></a>';
                    } else if (/^\s*data:([a-z]+\/[a-z0-9-+.]+(;[a-z-]+=[a-z0-9-]+)?)?(;base64)?,([a-z0-9!$&',()*+;=\-._~:@\/?%\s]*?)\s*$/i.test(value)) {
                        html += '<img src="'+value+'" style="width: 100px;height:60px;object-fit: cover;cursor: pointer" data-toggle="tooltip" title="'+lang('Click to open')+'" class="img-thumbnail">';
                    } else {
                        html += '<a href="'+value+'" target="_blank"><img src="'+Config.static_path+'/img/zip.png" data-toggle="tooltip" title="'+lang('Click to open')+'" data-url="'+value+'" style="width: auto;height:60px;object-fit: cover;cursor: pointer" class="img-thumbnail"></a>';
                    }
                } else {
                    html += '<a href="'+value+'" target="_blank"><img src="'+value+'" style="width: 100px;height:60px;object-fit: cover;cursor: pointer" data-toggle="tooltip" title="'+lang('Click to open')+'" data-url="'+value+'" class="img-thumbnail" style=""></a>';
                }
                return html;
            },
            radio: function (value, row, index, field) {
                if (typeof this.radioOption === 'undefined' || this.radioOption=='') {
                    return value
                }
                return this.radioOption[value];
            },
            datetime: function (value, row, index, field) { // 日期处理
                var format = typeof this.datetimeFormat==='undefined' ? 'YYYY-MM-DD HH:mm:ss' : this.datetimeFormat;
                if (isNaN(value)) {
                    return value ? Moment(value).format(format) : '';
                } else {
                    return value ? Moment(parseInt(value)* 1000).format(format) : '';
                }
            },
            images: function (value, row, index, field) {
                if (!value) {
                    return '';
                }
                var html = '';
                var tmpArr = [];
                try {
                    var JsonObj = JSON.parse(row[field]);
                    for (const key in JsonObj) {
                        tmpArr.push(JsonObj[key].file);
                    }
                } catch (error) {
                    console.log(error)
                    tmpArr = value.split(',');
                }
                for (var itemKey in tmpArr) {
                    html = html + Table.formatter.image(tmpArr[itemKey])
                }
                return html;
            },
        },
        //单元格元素事件
        events: {
            operate: {
                'click .btn-row-edit': function (e, value, row, index) {
                    e.stopPropagation();
                    var table = $(e.currentTarget).closest('table');
                    var options = table.bootstrapTable('getOptions');
                    var id = row[options.pk];
                    var data = $(e.currentTarget).data();
                    var url = table.data('edit');
                    if (!url) {
                        layer.msg(lang('Request address is empty'),{time:4000,icon:2});
                        return false;
                    }

                    if (options && options.editCallback && typeof options.editCallback === "function") { // 回调
                        var res = options.editCallback.call(e, {url:url}, id, row);
                        if (res===false) {
                            return false;
                        }
                    }

                    if (data.popup===false) {
                        // 新页面
                        window.location.href = Util.setUrlParams({url:url, query:{popup:0,id:id}});
                    } else {
                        cmsOpen(Util.setUrlParams({url:url,query:{popup:1,id:id}}), lang('Edit'));
                    }
                },
                'click .btn-row-del': function (e, value, row, index) {
                    e.stopPropagation();

                    var table = $(e.currentTarget).closest('table');
                    var options = table.bootstrapTable('getOptions');
                    var id = row[options.pk];
                    var url = table.data('del');
                    if (!url) {
                        layer.msg(lang('Request address is empty'),{time:4000,icon:2});
                        return false;
                    }

                    var data = $(e.currentTarget).data();
                    layer.confirm(data && data.tips || lang('Confirm operation?'), {
                        btn: [lang('Confirm'),lang('Cancel')] //按钮
                    }, function(){
                        Util.ajax({url:Util.setUrlParams({url:url, query:{ids:id}}),type:"post"},'',function (data,res) {
                            layer.msg(res.msg,{time:1000,icon:1},function (e) {
                                table.bootstrapTable('refresh');
                            });
                        });
                    });
                }
            },
            switchBtn: {
                'change .custom-control-input': function (e, value, row, index) {
                    var table = $(e.currentTarget).closest('table');
                    var options = table.bootstrapTable('getOptions');
                    var r=/^\d+$/;
                    var val;
                    if (r.test(value)) {
                        val = value===1?0:1;
                    } else {
                        val = value==='normal'?'hidden':'normal';
                    }
                    if (table.data('batches')) {
                        Util.ajax({url: table.data('batches'),data:{ids: row[options.pk],params:$(e.currentTarget).data('field')+'='+val}},'',function (data,res) {
                            layer.msg(res.msg,{time:1000,icon:1},function (e) {
                                table.bootstrapTable('refresh');
                            });
                        })
                    } else {
                        layer.msg(lang('Permission denied'),{time:4000,icon:2});
                    }
                }
            },
            txtEditBtn: {   // 快速修改字段值
                'change .btn-txtEditBtn': function (e, value, row, index) {
                    e.stopPropagation();

                    var table = $(e.currentTarget).closest('table');
                    var data = $(e.currentTarget).data();
                    if (data) {
                        hkcms.api.ajax({url: table.data('batches'),data:{ids:data.id,params:data.field+'='+$(e.currentTarget).val()},type: 'post'},'',function (data, res) {
                            layer.msg(res.msg,{time:1000, icon:1},function (e) {
                                table.bootstrapTable('refresh');
                            });
                        })
                    }
                },
                'dblclick .btn-txtEditBtn': function (e) {
                    e.stopPropagation()
                }
            },
            textBoxBtn: { // 弹出式显示文本内容
                'click .text-box-btn': function (e, value, row, index) {
                    e.stopPropagation();
                    hkcms.api.open('',lang('Info'),{
                        type: 1,
                        area: [$(top.window).width() > 800 ? '400px' : '90%', $(top.window).height() > 600 ? '300px' : '90%'],
                        id:'frm-'+$(e.currentTarget).data('field')+index,
                        content:`<div style="word-wrap:break-word;padding: 10px;"><textarea class="form-control" rows="8">`+value+'</textarea></div>'
                    })
                }
            },
            image: {
                'click .img-thumbnail': function (e, value, row, index) {
                    e.stopPropagation();
                    e.preventDefault();
                    var img = $(e.currentTarget).data('url');
                    window.open(img)
                }
            },
            // 编辑器
            editor: {
                'click .editor-box-btn': function (e, value, row, index) {
                    e.stopPropagation();
                    console.log($(top.window).width())
                    hkcms.api.open('',lang('Info'),{
                        type: 1,
                        area: [$(top.window).width() > 800 ? '800px' : '90%', $(top.window).height() > 600 ? '600px' : '90%'],
                        id:'frm-'+$(e.currentTarget).data('field')+index,
                        content:`<div style="padding: 20px 25px 25px 25px">`+value+'</div>'
                    })
                }
            },
        }
    };
    return Table;
});