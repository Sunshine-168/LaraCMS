@props([
    'pointName' => 'point',
    'addressName' => 'address',
    'pointValue' => '{"lng":116.404,"lat":39.915}',
    'addressValue' => '',
    'label' => '位置选取',
    'id' => 'address_picker_'.\Illuminate\Support\Str::random(8),
])

@php
    $pointId = $id.'_point';
    $addressId = $id.'_address';
@endphp

<div id="{{ $id }}" style="display:flex;gap:8px;align-items:center;">
    <input id="{{ $pointId }}" type="hidden" name="{{ $pointName }}" value="{{ $pointValue }}">
    <input id="{{ $addressId }}" type="text" name="{{ $addressName }}" value="{{ $addressValue }}" style="flex:1;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;">
    <button type="button" style="padding:8px 14px;border:0;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer;" onclick="open{{ \Illuminate\Support\Str::studly($id) }}()">{{ $label }}</button>
</div>

<script>
    function open{{ \Illuminate\Support\Str::studly($id) }}() {
        const popup = window.open('{{ route('addons.address.index') }}', '_blank', 'width=980,height=720');
        window.onAddressSelected = function (payload) {
            document.getElementById(@js($pointId)).value = JSON.stringify(payload.lnglat || {});
            document.getElementById(@js($addressId)).value = payload.address || '';
            if (popup) {
                popup.close();
            }
        };
    }
</script>
