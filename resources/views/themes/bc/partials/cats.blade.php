@php
    $apiUrl = $apiUrl ?? (app(\App\Services\Cms\SiteFrontService::class)->categoryBySlug('gw-api')?->frontUrl() ?: cms_front_url('gw-api'));
    $tiles = [
        ['stock/roulette.jpg', cms_t('真人', 'Live')],
        ['stock/stadium.jpg', cms_t('体育', 'Sports')],
        ['stock/slots.jpg', cms_t('电子', 'Slots')],
        ['stock/cards.jpg', cms_t('棋牌', 'Cards')],
        ['stock/dice.jpg', cms_t('彩票', 'Lottery')],
    ];
@endphp
<div class="ig-mosaic">
    @foreach($tiles as [$file, $label])
        <a class="ig-cat" href="{{ $apiUrl }}">
            <img src="{{ asset('themes/bc/'.$file) }}" alt="{{ $label }}">
            <span>{{ $label }}</span>
        </a>
    @endforeach
</div>
