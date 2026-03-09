require(['admin'], function (admin) {
    if ($('[data-toggle="address"]', document)) {
        $('[data-toggle="address"]', document).each(function (idx,vo) {
            if ($(this).attr('type')!='text') {
                return true;
            }
            var tmp1 = $(this).data('point');
            var tmp2 = $(this).data('address');

            $(this).removeAttr('data-toggle');
            $(this).removeAttr('data-point');
            $(this).removeAttr('data-address');
            $(this).after('<div class="input-group">'+$(this).prop('outerHTML')+'<div class="input-group-append"><button type="button" class="btn btn-primary" data-point="'+tmp1+'" data-address="'+tmp2+'" data-toggle="address">位置选取</button></div></div>');
            $(this).remove();
        });

        $(document).on('click','[data-toggle="address"]',function (e) {
            var that = this;
            var index = hkcms.api.open((Config && Config.url_mode==1?'':'/index.php') + '/addons/address/index/index','位置选取',{},function (obj) {
                $('#'+$(that).data('point')).val(JSON.stringify(obj.lnglat));
                $('#'+$(that).data('address')).val(obj.address)
            });
        })
    }
})