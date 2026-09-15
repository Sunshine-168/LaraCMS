<section class="wrap" style="max-width:720px;margin:40px auto">
    <h1>{{ $entry->exists ? cms_ui('edit_post') : cms_ui('write_post') }}</h1>
    @if(session('status'))<p style="color:green">{{ session('status') }}</p>@endif
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif
    <p><a href="{{ cms_route('member.entries.index') }}">{{ cms_ui('back_list') }}</a>
        @if($entry->exists)
            <span class="muted"> · {{ cms_ui('status_'.$entry->status) }}</span>
        @endif
    </p>

    <form method="post" action="{{ $entry->exists ? cms_route('member.entries.update', $entry) : cms_route('member.entries.store') }}">
        @csrf
        @if($entry->exists) @method('PUT') @endif

        @if(!$entry->exists && ($models ?? collect())->count() > 1)
            <p><label>{{ cms_ui('model') }}<br>
                <select name="model_id" onchange="location='{{ cms_route('member.entries.create') }}?model_id='+this.value" style="width:100%;padding:8px">
                    @foreach($models as $m)
                        <option value="{{ $m->id }}" @selected((int)$model->id === (int)$m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </label></p>
        @else
            <input type="hidden" name="model_id" value="{{ $model->id }}">
        @endif

        <p><label>{{ cms_ui('channel') }}<br>
            <select id="memberPrimaryCategory" name="category_id" required style="width:100%;padding:8px">
                <option value="">{{ cms_ui('please_select') }}</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $entry->category_id) == $c->id)>{{ $c->t('title') }}</option>
                @endforeach
            </select>
        </label></p>
        @if($categories->isEmpty())
            <p class="muted">{{ cms_ui('no_contribute_cats') }}</p>
        @endif

        <p><label>{{ cms_ui('title_field') }}<br><input type="text" name="title" value="{{ old('title', $entry->t('title')) }}" required maxlength="200" style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('excerpt_field') }}<br><textarea name="excerpt" rows="3" maxlength="500" style="width:100%;padding:8px">{{ old('excerpt', $entry->t('excerpt')) }}</textarea></label></p>
        <p><label>{{ cms_ui('body_field') }}<br><textarea name="content" rows="12" maxlength="50000" style="width:100%;padding:8px">{{ old('content', $entry->t('content')) }}</textarea></label></p>
        <p><label>{{ cms_ui('cover_url') }}<br><input type="text" id="memberCover" name="cover" value="{{ old('cover', $entry->cover) }}" maxlength="500" style="width:100%;padding:8px"></label></p>
        @if($member->canMember('upload'))
            @include('cms.partials.member-chunk-upload', ['purpose' => 'file', 'targetInput' => 'memberCover', 'previewId' => 'memberCoverPreview'])
            <p><img id="memberCoverPreview" src="{{ cms_media($entry->cover) }}" alt="" style="max-width:180px;{{ $entry->cover ? '' : 'display:none' }}"></p>
        @endif

        @include('cms.partials.member-attrs')

        <p>
            <button type="submit" name="action" value="draft">{{ cms_ui('save_draft') }}</button>
            <button type="submit" name="action" value="submit">{{ cms_ui('submit_review') }}</button>
        </p>
    </form>
</section>
