@php
    $fields = $fields ?? [];
    $model = $model ?? null;
@endphp
<div class="tabs">
    @foreach($locales as $code)
        <button type="button" data-locale-tab="{{ $code }}" class="{{ $loop->first ? 'active' : '' }}">
            {{ config('cms.locales.'.$code.'.label', $code) }}
        </button>
    @endforeach
</div>
@foreach($locales as $code)
    <div class="locale-pane {{ $loop->first ? 'active' : '' }}" data-locale-pane="{{ $code }}">
        @foreach($fields as $field => $meta)
            <label>{{ $meta['label'] }} ({{ $code }})</label>
            @if(($meta['type'] ?? 'text') === 'editor')
                <textarea class="cms-editor" name="locales[{{ $code }}][{{ $field }}]">{{ old("locales.$code.$field", $model?->t($field, $code, '')) }}</textarea>
            @elseif(($meta['type'] ?? 'text') === 'textarea')
                <textarea name="locales[{{ $code }}][{{ $field }}]">{{ old("locales.$code.$field", $model?->t($field, $code, '')) }}</textarea>
            @else
                <input type="text" name="locales[{{ $code }}][{{ $field }}]" value="{{ old("locales.$code.$field", $model?->t($field, $code, '')) }}">
            @endif
        @endforeach
    </div>
@endforeach
@push('scripts')
<script>
document.querySelectorAll('[data-locale-tab]').forEach(btn => {
    btn.addEventListener('click', () => {
        const code = btn.getAttribute('data-locale-tab');
        document.querySelectorAll('[data-locale-tab]').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('[data-locale-pane]').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.querySelector('[data-locale-pane="'+code+'"]').classList.add('active');
    });
});
</script>
@endpush
