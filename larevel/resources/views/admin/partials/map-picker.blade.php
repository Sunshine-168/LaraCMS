{{-- map 字段选点：值格式 lng,lat 或 lng,lat|地址 --}}
@php
    $raw = (string) ($value ?? '');
    $addr = '';
    $lng = $defaults['lng'] ?? 116.397;
    $lat = $defaults['lat'] ?? 39.908;
    if ($raw !== '') {
        $parts = explode('|', $raw, 2);
        $coord = trim($parts[0]);
        $addr = trim($parts[1] ?? '');
        if (str_contains($coord, ',')) {
            [$a, $b] = array_map('trim', explode(',', $coord, 2));
            if (is_numeric($a) && is_numeric($b)) {
                $lng = (float) $a;
                $lat = (float) $b;
            } elseif ($addr === '') {
                $addr = $coord;
            }
        } elseif ($addr === '') {
            $addr = $coord;
        }
    }
    $uid = 'map_'.preg_replace('/\W+/', '_', $name);
@endphp
<div class="cms-map-picker" data-uid="{{ $uid }}" style="margin-bottom:12px">
    <input type="hidden" name="{{ $name }}" id="{{ $uid }}_val" value="{{ $raw }}">
    <div class="cover-row" style="margin-bottom:8px">
        <input type="text" id="{{ $uid }}_addr" value="{{ $addr }}" placeholder="地址备注（可选）" style="flex:1">
        <input type="text" id="{{ $uid }}_lng" value="{{ $lng }}" placeholder="经度" style="width:110px">
        <input type="text" id="{{ $uid }}_lat" value="{{ $lat }}" placeholder="纬度" style="width:110px">
        <button type="button" class="btn btn-muted" id="{{ $uid }}_apply">定位</button>
    </div>
    <div id="{{ $uid }}_map" style="height:280px;border:1px solid #e2e8f0;border-radius:8px"></div>
    <p class="muted" style="margin:6px 0 0">点击地图选点；存盘格式：经度,纬度|地址</p>
</div>
@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/ciZLRCeR3PxQ2xUpc=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    window.CmsMapPicker = window.CmsMapPicker || {
        boot(uid, lng, lat) {
            const mapEl = document.getElementById(uid + '_map');
            const valEl = document.getElementById(uid + '_val');
            const addrEl = document.getElementById(uid + '_addr');
            const lngEl = document.getElementById(uid + '_lng');
            const latEl = document.getElementById(uid + '_lat');
            const applyBtn = document.getElementById(uid + '_apply');
            if (!mapEl || !window.L) return;
            const map = L.map(mapEl).setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            let marker = L.marker([lat, lng], {draggable: true}).addTo(map);
            const sync = (la, ln) => {
                lngEl.value = Number(ln).toFixed(6);
                latEl.value = Number(la).toFixed(6);
                const a = (addrEl.value || '').trim();
                valEl.value = Number(ln).toFixed(6) + ',' + Number(la).toFixed(6) + (a ? '|' + a : '');
            };
            sync(lat, lng);
            marker.on('dragend', () => {
                const p = marker.getLatLng();
                sync(p.lat, p.lng);
            });
            map.on('click', (e) => {
                marker.setLatLng(e.latlng);
                sync(e.latlng.lat, e.latlng.lng);
            });
            addrEl.addEventListener('input', () => sync(parseFloat(latEl.value) || lat, parseFloat(lngEl.value) || lng));
            applyBtn.addEventListener('click', () => {
                const la = parseFloat(latEl.value);
                const ln = parseFloat(lngEl.value);
                if (Number.isNaN(la) || Number.isNaN(ln)) return;
                marker.setLatLng([la, ln]);
                map.setView([la, ln], Math.max(map.getZoom(), 14));
                sync(la, ln);
            });
            setTimeout(() => map.invalidateSize(), 80);
        }
    };
    </script>
@endonce
<script>document.addEventListener('DOMContentLoaded', function () { CmsMapPicker.boot(@json($uid), {{ (float)$lat }}, {{ (float)$lng }}); });</script>
