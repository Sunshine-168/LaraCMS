<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>位置选取</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; font-family: Arial, sans-serif; font-size: 14px; }
        .page { padding: 12px; }
        .row { display: flex; gap: 12px; margin-bottom: 12px; align-items: center; flex-wrap: wrap; }
        .label { min-width: 48px; }
        .input { flex: 1; min-width: 180px; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; }
        .btn { padding: 8px 16px; border: 0; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer; }
        .alert { padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; }
        .alert-warning { background: #fff7ed; color: #9a3412; }
        .alert-danger { background: #fef2f2; color: #b91c1c; }
        #map { width: 100%; height: calc(100vh - 120px); min-height: 420px; border-radius: 8px; overflow: hidden; }
        #searchResultPanel { display: none; }
    </style>
</head>
<body>
    <div class="page">
        @if(($config['base']['map_ak'] ?? '') === 'uUvLWdeGhSwc2OLw2NPkkGrBWYf0lgG8')
            <div class="alert alert-warning">当前使用的是默认密钥，若无法正常访问，请替换为你自己的地图 AK。</div>
        @endif

        @if(empty($config['base']['map_ak']))
            <div class="alert alert-danger">你还没有配置地图密钥，请先在 Laravel 环境变量里设置 `ADDONS_ADDRESS_MAP_AK`。</div>
        @endif

        <div class="row">
            <label class="label" for="gps-search">搜索</label>
            <input id="gps-search" class="input" type="search" placeholder="输入关键字，或点击地图选取坐标">
            <label class="label" for="gps-lng">经度</label>
            <input id="gps-lng" class="input" type="text">
            <label class="label" for="gps-lat">纬度</label>
            <input id="gps-lat" class="input" type="text">
            <button type="button" class="btn" id="confirm-address">确定</button>
        </div>

        <div id="map"></div>
    </div>

    <div id="searchResultPanel"></div>

    @if(($config['base']['map_type'] ?? 1) === 1)
        <script src="https://api.map.baidu.com/api?type=webgl&v=1.0&ak={{ $config['base']['map_ak'] ?? '' }}"></script>
        <script>
            let map;
            let geocoder;
            let title = '';
            let address = '';
            let lnglat = '';

            function updateFields(point) {
                document.getElementById('gps-lng').value = point.lng;
                document.getElementById('gps-lat').value = point.lat;
            }

            function addOverlay(point) {
                map.clearOverlays();
                const marker = new BMapGL.Marker(point);
                lnglat = {lng: point.lng, lat: point.lat};
                map.addOverlay(marker);

                geocoder.getLocation(point, function (result) {
                    if (result?.surroundingPois?.length > 0) {
                        title = result.surroundingPois[0].title || '';
                    }
                    address = result?.address || '';
                    updateFields(point);
                    marker.addEventListener('click', function () {
                        const infoWindow = new BMapGL.InfoWindow('地址：' + address, {title: title, width: 220, height: 80});
                        map.openInfoWindow(infoWindow, point);
                    });
                    marker.dispatchEvent('click');
                });
            }

            map = new BMapGL.Map('map');
            const initialPoint = new BMapGL.Point(116.404, 39.915);
            map.centerAndZoom(initialPoint, 12);
            map.enableScrollWheelZoom(true);
            geocoder = new BMapGL.Geocoder();
            addOverlay(initialPoint);

            map.addEventListener('click', function (e) {
                addOverlay(e.latlng);
            });

            const autoComplete = new BMapGL.Autocomplete({input: 'gps-search', location: map});
            let searchValue = '';

            autoComplete.addEventListener('onconfirm', function (e) {
                const value = e.item.value;
                searchValue = (value.province || '') + (value.city || '') + (value.district || '') + (value.street || '') + (value.business || '');
                const local = new BMapGL.LocalSearch(map, {
                    onSearchComplete: function () {
                        const point = local.getResults()?.getPoi(0)?.point;
                        if (!point) return;
                        map.centerAndZoom(point, 18);
                        addOverlay(point);
                    }
                });
                local.search(searchValue);
            });

            document.getElementById('confirm-address').addEventListener('click', function () {
                const payload = {title, address, lnglat};
                if (window.opener && typeof window.opener.onAddressSelected === 'function') {
                    window.opener.onAddressSelected(payload);
                } else if (window.parent && window.parent !== window && typeof window.parent.onAddressSelected === 'function') {
                    window.parent.onAddressSelected(payload);
                }
                window.close();
            });
        </script>
    @else
        <script src="https://webapi.amap.com/maps?v=2.0&key={{ $config['base']['map_ak'] ?? '' }}&plugin=AMap.Geocoder,AMap.AutoComplete"></script>
        <script src="https://webapi.amap.com/ui/1.1/main.js?v=1.1.1"></script>
        <script>
            let map;
            let geocoder;
            let title = '';
            let address = '';
            let lnglat = '';

            function updateFields(point) {
                document.getElementById('gps-lng').value = point.lng;
                document.getElementById('gps-lat').value = point.lat;
            }

            function addOverlay(point) {
                map.clearMap();
                const marker = new AMap.Marker({position: point});
                map.add(marker);
                lnglat = {lng: point.lng, lat: point.lat};

                geocoder.getAddress(point, function (status, result) {
                    if (status === 'complete' && result.regeocode) {
                        title = result.regeocode.addressComponent.building || '';
                        address = (result.regeocode.formattedAddress || '');
                        updateFields(point);
                        const infoWindow = new AMap.InfoWindow({
                            anchor: 'top-left',
                            content: "<div><strong>" + title + "</strong><p>地址：" + address + "</p></div>"
                        });
                        infoWindow.open(map, point);
                    }
                });
            }

            map = new AMap.Map('map', {resizeEnable: true, zoom: 12, center: [116.397428, 39.90923]});
            geocoder = new AMap.Geocoder();
            addOverlay({lng: 116.397428, lat: 39.90923});

            map.on('click', function (e) {
                addOverlay(e.lnglat);
            });

            AMapUI.loadUI(['misc/PoiPicker'], function (PoiPicker) {
                const poiPicker = new PoiPicker({input: 'gps-search'});
                poiPicker.on('poiPicked', function (poiResult) {
                    addOverlay(poiResult.item.location);
                });
            });

            document.getElementById('confirm-address').addEventListener('click', function () {
                const payload = {title, address, lnglat};
                if (window.opener && typeof window.opener.onAddressSelected === 'function') {
                    window.opener.onAddressSelected(payload);
                } else if (window.parent && window.parent !== window && typeof window.parent.onAddressSelected === 'function') {
                    window.parent.onAddressSelected(payload);
                }
                window.close();
            });
        </script>
    @endif
</body>
</html>
