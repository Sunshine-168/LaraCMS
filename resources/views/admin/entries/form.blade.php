@extends('admin.layout')
@section('title', $entry->exists ? '编辑内容' : '新建内容')
@section('content')
    <form method="post" action="{{ $entry->exists ? route('admin.entries.update', $entry) : route('admin.entries.store') }}">
        @csrf
        @if($entry->exists) @method('PUT') @endif
        <input type="hidden" name="model_id" value="{{ $model->id }}">

        @include('admin.partials.locale-tabs', [
            'locales' => $locales,
            'model' => $entry,
            'fields' => [
                'title' => ['label' => '标题'],
                'slug' => ['label' => 'Slug'],
                'excerpt' => ['label' => '摘要', 'type' => 'textarea'],
                'content' => ['label' => '正文', 'type' => 'editor'],
                'meta_title' => ['label' => 'SEO 标题'],
                'meta_description' => ['label' => 'SEO 描述', 'type' => 'textarea'],
            ],
        ])

        <label>主栏目（决定 URL；扩展字段按栏目绑定显示）</label>
        <select name="category_id" id="entryPrimaryCategory">
            <option value="">—</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $entry->category_id) == $category->id)>{{ $category->t('title') }}</option>
            @endforeach
        </select>
        @php
            $extraCats = old('category_ids', $entry->relationLoaded('categories')
                ? $entry->categories->pluck('id')->all()
                : ($entry->exists ? $entry->categories()->pluck('cms_categories.id')->all() : []));
            $extraCats = array_map('intval', (array) $extraCats);
        @endphp
        <label>附加栏目（可多选，列表页也会出现）</label>
        <div>
            @foreach($categories as $category)
                <label style="display:inline-block;min-width:160px;margin:4px 8px 4px 0">
                    <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                        @checked(in_array((int)$category->id, $extraCats, true))>
                    {{ $category->t('title') }}
                </label>
            @endforeach
        </div>

        <label>封面 URL</label>
        <div class="cover-row">
            <input type="text" id="entryCover" name="cover" value="{{ old('cover', $entry->cover) }}">
            <button type="button" class="btn btn-muted" data-media-pick="#entryCover">选择媒体</button>
        </div>
        @php
            $flagOpts = \App\Models\Cms\CmsFlag::options();
            $currentFlags = old('flag_list', array_filter(array_map('trim', explode(',', (string) old('flags', $entry->flags)))));
            $pubAt = old('published_at', optional($entry->published_at)->format('Y-m-d\TH:i'));
        @endphp
        <label>Flags 属性</label>
        <div>
            @forelse($flagOpts as $key => $label)
                <label style="display:inline;margin-right:14px">
                    <input type="checkbox" name="flag_list[]" value="{{ $key }}" @checked(in_array($key, $currentFlags, true))>
                    {{ $label }}
                </label>
            @empty
                <span class="muted">请先在 Flags 字典中配置</span>
            @endforelse
        </div>
        <label>自定义 Flags（逗号分隔，可与上方叠加）</label>
        <input type="text" name="flags" value="{{ old('flags', collect($currentFlags)->diff(array_keys($flagOpts))->implode(',')) }}" placeholder="about,custom">
        <label>排序</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $entry->sort_order ?? 0) }}">
        <label>状态</label>
        @php
            $canPublish = $canPublish ?? auth('admin')->user()?->canPerm('publish');
            $statusVal = old('status', $entry->status);
        @endphp
        <select name="status">
            @if($canPublish)
                <option value="draft" @selected($statusVal === 'draft')>草稿</option>
                <option value="pending" @selected($statusVal === 'pending')>待审</option>
                <option value="published" @selected($statusVal === 'published')>已发布</option>
            @elseif($entry->status === 'published')
                <option value="published" selected>已发布</option>
            @else
                <option value="draft" @selected($statusVal === 'draft')>草稿</option>
                <option value="pending" @selected($statusVal === 'pending')>待审</option>
            @endif
        </select>
        @if(! $canPublish)
            <p class="muted">无发布权限：草稿/新建不能直接发布，会进入待审。已发布内容不能改为草稿或待审。</p>
        @endif
        <label>定时发布时间（可空；未来时间则到点才前台可见）</label>
        <input type="datetime-local" name="published_at" value="{{ $pubAt }}">
        <label><input type="checkbox" name="is_homepage" value="1" @checked(old('is_homepage', $entry->is_homepage))> 设为首页文档</label>

        <h3 style="margin-top:20px;">扩展字段（{{ $model->name }}）</h3>
        <p class="muted" id="fieldBindHint">未绑定栏目的字段全栏目显示；已绑定字段仅在所选主栏目下出现。</p>
        @foreach($model->fields as $field)
            <div class="cms-field-bind" data-bound-cats="{{ $field->boundCatsAttr() }}">
            <label>{{ $field->label }} ({{ $field->name }})</label>
            @php $val = old('attrs.'.$field->name, $entry->attr($field->name)); @endphp
            @if($field->type === 'editor')
                <textarea class="cms-editor" name="attrs[{{ $field->name }}]">{{ $val }}</textarea>
            @elseif($field->type === 'textarea')
                <textarea name="attrs[{{ $field->name }}]">{{ $val }}</textarea>
            @elseif($field->type === 'images')
                <div class="images-gallery" data-images-input="#attr_{{ $field->name }}">
                    <div class="cover-row">
                        <input type="text" id="attr_{{ $field->name }}" name="attrs[{{ $field->name }}]" value="{{ is_array($val) ? implode(',', $val) : $val }}" placeholder="多图 URL，逗号分隔">
                        <button type="button" class="btn btn-muted" data-media-pick-append="#attr_{{ $field->name }}">添加图片</button>
                    </div>
                    <div class="images-thumbs"></div>
                </div>
            @elseif(in_array($field->type, ['image', 'file'], true))
                <div class="cover-row">
                    <input type="text" id="attr_{{ $field->name }}" name="attrs[{{ $field->name }}]" value="{{ is_array($val) ? implode(',', $val) : $val }}" placeholder="{{ $field->type === 'images' ? '多图 URL，逗号分隔' : '' }}">
                    <button type="button" class="btn btn-muted" data-media-pick="#attr_{{ $field->name }}">选择媒体</button>
                </div>
            @elseif($field->relatedModelSlug())
                @php
                    $relatedSlug = $field->relatedModelSlug();
                    $relatedModelId = \App\Models\Cms\ContentModel::query()->where('slug', $relatedSlug)->value('id');
                    $relatedEntries = $relatedModelId
                        ? \App\Models\Cms\Entry::query()->published()->where('model_id', $relatedModelId)->orderByDesc('id')->limit(300)->get()
                        : collect();
                @endphp
                <select name="attrs[{{ $field->name }}]">
                    <option value="">— 选择{{ $field->label }} —</option>
                    @foreach($relatedEntries as $rel)
                        <option value="{{ $rel->id }}" @selected((string)$val === (string)$rel->id)>{{ $rel->t('title') }} (#{{ $rel->id }})</option>
                    @endforeach
                </select>
            @elseif($field->type === 'number')
                <input type="number" step="any" name="attrs[{{ $field->name }}]" value="{{ $val }}">
            @elseif($field->type === 'url')
                <input type="url" name="attrs[{{ $field->name }}]" value="{{ $val }}" placeholder="https://">
            @elseif($field->type === 'date')
                <input type="date" name="attrs[{{ $field->name }}]" value="{{ $val }}">
            @elseif($field->type === 'datetime')
                <input type="datetime-local" name="attrs[{{ $field->name }}]" value="{{ $val }}">
            @elseif($field->type === 'checkbox' || $field->type === 'switch')
                <label><input type="checkbox" name="attrs[{{ $field->name }}]" value="1" @checked((string)$val === '1' || $val === true || $val === 'true')> 是</label>
            @elseif($field->type === 'radio')
                @foreach(($field->options['choices'] ?? []) as $k => $label)
                    @php $opt = is_int($k) ? $label : $k; @endphp
                    <label style="display:inline;margin-right:12px">
                        <input type="radio" name="attrs[{{ $field->name }}]" value="{{ $opt }}" @checked((string)$val === (string)$opt)> {{ $label }}
                    </label>
                @endforeach
            @elseif($field->type === 'select')
                <select name="attrs[{{ $field->name }}]">
                    <option value="">—</option>
                    @foreach(($field->options['choices'] ?? []) as $k => $label)
                        @php $opt = is_int($k) ? $label : $k; @endphp
                        <option value="{{ $opt }}" @selected($val == $opt)>{{ $label }}</option>
                    @endforeach
                </select>
            @elseif($field->type === 'map')
                @include('admin.partials.map-picker', [
                    'name' => 'attrs['.$field->name.']',
                    'value' => $val,
                    'defaults' => $mapDefaults ?? ['lng' => 116.397, 'lat' => 39.908],
                ])
            @else
                <input type="text" name="attrs[{{ $field->name }}]" value="{{ $val }}">
            @endif
            </div>
        @endforeach

        @if($tags->isNotEmpty())
            <label>标签</label>
            <div>
                @foreach($tags as $tag)
                    <label style="display:inline;margin-right:12px">
                        <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                            @checked(collect(old('tag_ids', $entry->tags?->pluck('id')->all() ?? []))->contains($tag->id))>
                        {{ $tag->t('title') }}
                    </label>
                @endforeach
            </div>
        @endif

        <p style="margin-top:16px;">
            <button class="btn" type="submit">保存</button>
            <a class="btn btn-muted" href="{{ route('admin.entries.index', ['model_id' => $model->id]) }}">返回</a>
        </p>
    </form>

    @if($entry->exists)
        <div class="card-soft" style="margin-top:24px;max-width:720px">
            <h3>草稿预览</h3>
            <p class="muted">未发布内容可通过此链接查看；过期后点刷新。HTML 缓存与访问统计会跳过预览页。</p>
            @if($entry->preview_token)
                <p>
                    <a href="{{ $entry->previewUrl() }}" target="_blank" rel="noopener">{{ url($entry->previewUrl()) }}</a>
                    @if($entry->preview_expires_at)
                        <span class="muted">（有效至 {{ $entry->preview_expires_at->format('Y-m-d H:i') }}）</span>
                    @endif
                </p>
            @else
                <p class="muted">保存后会自动生成预览链接。</p>
            @endif
            <form method="post" action="{{ route('admin.entries.preview', $entry) }}" style="margin-top:8px">
                @csrf
                <button class="btn btn-muted" type="submit">刷新预览链接</button>
            </form>
        </div>

        @php $revisions = $revisions ?? $entry->revisions ?? collect(); @endphp
        <div class="card-soft" style="margin-top:16px;max-width:720px">
            <h3>历史版本</h3>
            <p class="muted">每次保存会快照上一稿，最多保留 20 条。还原会把当前稿再存一版。</p>
            @forelse($revisions as $rev)
                <div style="display:flex;gap:12px;align-items:center;padding:8px 0;border-top:1px solid #e5e6eb">
                    <div style="flex:1">
                        <strong>{{ $rev->title_hint ?: '（无标题）' }}</strong>
                        <div class="muted">{{ $rev->created_at?->format('Y-m-d H:i') }} · {{ $rev->admin?->name ?: '系统' }}</div>
                    </div>
                    <form method="post" action="{{ route('admin.entries.revisions.restore', [$entry, $rev]) }}" onsubmit="return confirm('还原到该版本？当前内容会另存为新版本。')">
                        @csrf
                        <button class="btn btn-muted" type="submit">还原</button>
                    </form>
                </div>
            @empty
                <p class="muted">尚无历史版本，再次保存后出现。</p>
            @endforelse
        </div>
    @endif
    @include('admin.partials.editor-assets')
    <script>
    (function () {
        const sel = document.getElementById('entryPrimaryCategory');
        const blocks = document.querySelectorAll('.cms-field-bind');
        if (!sel || !blocks.length) return;
        const apply = () => {
            const cat = String(sel.value || '');
            blocks.forEach((el) => {
                const bound = el.getAttribute('data-bound-cats') || '*';
                const show = bound === '*' || (cat !== '' && bound.split(',').includes(cat));
                el.style.display = show ? '' : 'none';
                el.querySelectorAll('input,select,textarea,button').forEach((inp) => {
                    if (inp.type === 'hidden' && inp.name && inp.name.indexOf('attrs[') === 0) {
                        // map hidden 仍随显示控制 disabled，避免提交隐藏栏目字段
                    }
                    if (show) inp.removeAttribute('disabled');
                    else inp.setAttribute('disabled', 'disabled');
                });
            });
        };
        sel.addEventListener('change', apply);
        apply();
    })();
    </script>
@endsection
