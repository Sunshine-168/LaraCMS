@if(($model->fields ?? collect())->isNotEmpty())
    <h2 style="margin-top:24px">扩展字段</h2>
    @foreach($model->fields as $field)
        @php
            $val = old('attrs.'.$field->name, $entry->attr($field->name));
            $inputId = 'member_attr_'.$field->name;
        @endphp
        <div class="cms-field-bind" data-bound-cats="{{ $field->boundCatsAttr() }}" style="margin:12px 0">
            <label>{{ $field->label }}@if($field->is_required) * @endif</label>
            @if(in_array($field->type, ['editor', 'textarea', 'images'], true))
                <textarea id="{{ $inputId }}" name="attrs[{{ $field->name }}]" rows="{{ $field->type === 'images' ? 3 : 6 }}" style="width:100%;padding:8px">{{ is_array($val) ? implode("\n", $val) : $val }}</textarea>
            @elseif($field->relatedModelSlug())
                @php
                    $relatedSlug = $field->relatedModelSlug();
                    $relatedModelId = \App\Models\Cms\ContentModel::query()->where('slug', $relatedSlug)->value('id');
                    $relatedEntries = $relatedModelId
                        ? \App\Models\Cms\Entry::query()->published()->where('model_id', $relatedModelId)->orderByDesc('id')->limit(200)->get()
                        : collect();
                @endphp
                <select name="attrs[{{ $field->name }}]" style="width:100%;padding:8px">
                    <option value="">请选择</option>
                    @foreach($relatedEntries as $rel)
                        <option value="{{ $rel->id }}" @selected((string)$val === (string)$rel->id)>{{ $rel->t('title') }}</option>
                    @endforeach
                </select>
            @elseif($field->type === 'number')
                <input type="number" step="any" name="attrs[{{ $field->name }}]" value="{{ $val }}" style="width:100%;padding:8px">
            @elseif($field->type === 'url')
                <input type="url" name="attrs[{{ $field->name }}]" value="{{ $val }}" placeholder="https://" style="width:100%;padding:8px">
            @elseif($field->type === 'date')
                <input type="date" name="attrs[{{ $field->name }}]" value="{{ $val }}" style="width:100%;padding:8px">
            @elseif($field->type === 'datetime')
                <input type="datetime-local" name="attrs[{{ $field->name }}]" value="{{ $val }}" style="width:100%;padding:8px">
            @elseif($field->type === 'checkbox' || $field->type === 'switch')
                <label><input type="checkbox" name="attrs[{{ $field->name }}]" value="1" @checked((string)$val === '1' || $val === true || $val === 'true')> 是</label>
            @elseif($field->type === 'radio')
                @foreach(($field->options['choices'] ?? []) as $k => $label)
                    @php $opt = is_int($k) ? $label : $k; @endphp
                    <label style="margin-right:12px">
                        <input type="radio" name="attrs[{{ $field->name }}]" value="{{ $opt }}" @checked((string)$val === (string)$opt)> {{ $label }}
                    </label>
                @endforeach
            @elseif($field->type === 'select')
                <select name="attrs[{{ $field->name }}]" style="width:100%;padding:8px">
                    <option value="">请选择</option>
                    @foreach(($field->options['choices'] ?? []) as $k => $label)
                        @php $opt = is_int($k) ? $label : $k; @endphp
                        <option value="{{ $opt }}" @selected((string)$val === (string)$opt)>{{ $label }}</option>
                    @endforeach
                </select>
            @else
                <input type="text" id="{{ $inputId }}" name="attrs[{{ $field->name }}]" value="{{ is_array($val) ? implode(',', $val) : $val }}" style="width:100%;padding:8px">
            @endif
            @if(in_array($field->type, ['image', 'file'], true) && $member->canMember('upload'))
                @include('cms.partials.member-chunk-upload', [
                    'purpose' => $field->type === 'image' ? 'file' : 'file',
                    'inputId' => $inputId.'_file',
                    'targetInput' => $inputId,
                    'previewId' => $inputId.'_preview',
                    'progressId' => $inputId.'_progress',
                ])
            @endif
        </div>
    @endforeach
    <script>
    (function () {
        const sel = document.getElementById('memberPrimaryCategory');
        const blocks = document.querySelectorAll('.cms-field-bind');
        if (!sel || !blocks.length) return;
        const apply = () => {
            const cat = String(sel.value || '');
            blocks.forEach((el) => {
                const bound = el.getAttribute('data-bound-cats') || '*';
                const show = bound === '*' || (cat !== '' && bound.split(',').includes(cat));
                el.style.display = show ? '' : 'none';
                el.querySelectorAll('input,select,textarea').forEach((inp) => {
                    if (show) inp.removeAttribute('disabled');
                    else inp.setAttribute('disabled', 'disabled');
                });
            });
        };
        sel.addEventListener('change', apply);
        apply();
    })();
    </script>
@endif
