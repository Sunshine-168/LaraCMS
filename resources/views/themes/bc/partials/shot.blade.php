@php
    $file = [
        'standard' => 'stock/floor.jpg',
        'custom' => 'stock/neon.jpg',
        'api' => 'stock/desk.jpg',
    ][$slug ?? ''] ?? '';
@endphp
@if($file !== '')
    <img src="{{ asset('themes/bc/'.$file) }}" alt="{{ $alt ?? '' }}">
@endif
