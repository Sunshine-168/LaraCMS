@php
    $fields = $fields ?? [];
    $advanced = $advanced ?? [];
    $model = $model ?? null;
    $hideCode = (bool) ($hideCode ?? false);
@endphp
<div class="tabs">
    @foreach($locales as $code)
        <button type="button" data-locale-tab="{{ $code }}" class="{{ $loop->first ? 'active' : '' }}">
            {{ \App\Support\Locale::configured()[$code]['label'] ?? $code }}
        </button>
    @endforeach
</div>
@foreach($locales as $code)
    <div class="locale-pane {{ $loop->first ? 'active' : '' }}" data-locale-pane="{{ $code }}">
        @foreach($fields as $field => $meta)
            <div class="{{ $meta['wrap'] ?? '' }}">
                <label>{{ $meta['label'] }}@unless($hideCode) ({{ $code }})@endunless</label>
                @if(! empty($meta['hint']))
                    <p class="muted field-hint">{{ $meta['hint'] }}</p>
                @endif
                @if(($meta['type'] ?? 'text') === 'editor')
                    <textarea class="cms-editor" name="locales[{{ $code }}][{{ $field }}]">{{ old("locales.$code.$field", $model?->translation($field, $code)) }}</textarea>
                @elseif(($meta['type'] ?? 'text') === 'textarea')
                    <textarea name="locales[{{ $code }}][{{ $field }}]" rows="{{ $meta['rows'] ?? 4 }}">{{ old("locales.$code.$field", $model?->translation($field, $code)) }}</textarea>
                @else
                    <input type="text" class="{{ $meta['class'] ?? '' }}" name="locales[{{ $code }}][{{ $field }}]" value="{{ old("locales.$code.$field", $model?->translation($field, $code)) }}" placeholder="{{ $meta['placeholder'] ?? '' }}">
                @endif
            </div>
        @endforeach
        @if($advanced !== [])
            <details class="entry-seo">
                <summary>搜索引擎（可选）</summary>
                @foreach($advanced as $field => $meta)
                    <label>{{ $meta['label'] }}</label>
                    @if(($meta['type'] ?? 'text') === 'textarea')
                        <textarea name="locales[{{ $code }}][{{ $field }}]" rows="3">{{ old("locales.$code.$field", $model?->translation($field, $code)) }}</textarea>
                    @else
                        <input type="text" name="locales[{{ $code }}][{{ $field }}]" value="{{ old("locales.$code.$field", $model?->translation($field, $code)) }}">
                    @endif
                @endforeach
            </details>
        @endif
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
        btn.closest('.tabs')?.parentElement?.querySelector('[data-locale-pane="'+code+'"]')?.classList.add('active')
            || document.querySelector('[data-locale-pane="'+code+'"]').classList.add('active');
        if (window.tinymce) {
            window.tinymce.editors.forEach((ed) => { try { ed.dispatch('ResizeEditor'); } catch (e) {} });
        }
    });
});
</script>
@endpush
