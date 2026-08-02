<form method="get" action="{{ route('search') }}" class="cms-search-form" style="margin:16px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:center">
    <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="关键词" style="padding:8px;min-width:200px;flex:1;max-width:420px">
    @if(($searchModels ?? collect())->isNotEmpty())
        <select name="model_id" style="padding:8px">
            <option value="">全部模型</option>
            @foreach($searchModels as $m)
                <option value="{{ $m->id }}" @selected(($modelId ?? null) == $m->id)>{{ $m->name }}</option>
            @endforeach
        </select>
    @endif
    @if(($searchCategories ?? collect())->isNotEmpty())
        <select name="category_id" style="padding:8px">
            <option value="">全部栏目</option>
            @foreach($searchCategories as $c)
                <option value="{{ $c->id }}" @selected(($categoryId ?? null) == $c->id)>{{ $c->t('title') }}</option>
            @endforeach
        </select>
    @endif
    <button class="btn" type="submit">搜索</button>
</form>
