@props([
    'id' => 'dituContent',
    'title' => '',
    'address' => '',
    'point' => ['lng' => 116.404, 'lat' => 39.915],
    'scale' => 15,
    'style' => 'width:100%;height:360px;',
])

@php
    $config = config('addons.address');
    $mapType = (int) ($config['base']['map_type'] ?? 1);
    $ak = $config['base']['map_ak'] ?? '';
    $pointJson = json_encode($point, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<div id="{{ $id }}" style="{{ $style }}"></div>

@if($mapType === 1)
    <script src="https://api.map.baidu.com/api?ak={{ $ak }}&v=3.0"></script>
    <script>
        (function () {
            const lnglat = {!! $pointJson !!};
            const map = new BMap.Map(@js($id));
            const point = new BMap.Point(lnglat.lng, lnglat.lat);
            map.centerAndZoom(point, {{ (int) $scale }});
            map.enableScrollWheelZoom(true);
            map.addControl(new BMap.NavigationControl({anchor: BMAP_ANCHOR_TOP_RIGHT, type: BMAP_NAVIGATION_CONTROL_SMALL}));

            const marker = new BMap.Marker(point);
            map.addOverlay(marker);

            const infoWindow = new BMap.InfoWindow(
                "<div style='margin:0 0 5px 0;padding:0.2em 0;font-size:16px'>{{ e($title) }}</div>" +
                "<p style='margin-bottom:0'>地址：{{ e($address) }}</p>"
            );
            marker.openInfoWindow(infoWindow);
        })();
    </script>
@else
    <script src="https://webapi.amap.com/maps?v=2.0&key={{ $ak }}"></script>
    <script>
        (function () {
            const lnglat = {!! $pointJson !!};
            const map = new AMap.Map(@js($id), {
                zoom: {{ (int) $scale }},
                center: [lnglat.lng, lnglat.lat]
            });

            map.on('complete', function () {
                const marker = new AMap.Marker({position: [lnglat.lng, lnglat.lat]});
                map.add(marker);

                const infoWindow = new AMap.InfoWindow({
                    anchor: 'top-left',
                    content: "<div><div style='font-size:1rem;font-weight:bold;'>{{ e($title) }}</div><p>地址：{{ e($address) }}</p></div>"
                });
                infoWindow.open(map, [lnglat.lng, lnglat.lat]);
            });
        })();
    </script>
@endif
