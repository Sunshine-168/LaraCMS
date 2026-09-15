@props([
    'href' => '#',
    'icon' => 'circle',
    'label' => '',
    'active' => false,
    'count' => 0,
])
<a href="{{ $href }}" @class(['active' => $active])>
    <i class="fas fa-{{ $icon }}" aria-hidden="true"></i>
    <span>{{ $label }}</span>
    @if((int) $count > 0)
        <span class="nav-count">{{ (int) $count > 99 ? '99+' : (int) $count }}</span>
    @endif
</a>
