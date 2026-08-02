@extends('admin.layout')

@section('content')
    <section class="card" style="margin-bottom:16px;">
        <form method="get" action="{{ route('admin.contents.index') }}" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <div style="min-width:280px;">
                <label>选择栏目</label>
                <select name="category_id" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    <option value="">请选择栏目</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" data-model="{{ $category->model_id }}" @selected((int) request('category_id') === (int) $category->id)>
                            {{ $category->title }} @if($category->modelDef) ({{ $category->modelDef->name }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>带上子栏目</label>
                <select name="issub" style="padding:10px;border:1px solid #d1d5db;border-radius:8px;">
                    <option value="0" @selected((int) request('issub', 0) === 0)>否</option>
                    <option value="1" @selected((int) request('issub', 0) === 1)>是</option>
                </select>
            </div>
            <input type="hidden" name="model_id" value="{{ request('model_id', $selectedModel?->id) }}">
            <button type="submit" class="btn">查看内容</button>
        </form>
    </section>

    <script>
        (function () {
            const categorySelect = document.querySelector('select[name="category_id"]');
            const modelInput = document.querySelector('input[name="model_id"]');
            if (!categorySelect || !modelInput) {
                return;
            }
            categorySelect.addEventListener('change', function () {
                const option = this.options[this.selectedIndex];
                modelInput.value = option ? (option.dataset.model || '') : '';
            });
        })();
    </script>

    @if(!$selectedCategory)
        <section class="card">
            <h3 style="margin-top:0;">可访问栏目</h3>
            <div class="grid grid-3">
                @forelse($categories as $category)
                    <a class="card" href="{{ route('admin.contents.index', ['category_id' => $category->id, 'model_id' => $category->model_id]) }}">
                        <div>{{ $category->title }}</div>
                        <div class="meta" style="margin-top:8px;">模型 ID：{{ $category->model_id }}</div>
                    </a>
                @empty
                    <div class="meta">当前没有可访问的内容栏目。</div>
                @endforelse
            </div>
        </section>
    @else
        <section class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <div>
                    <div><strong>{{ $selectedCategory->title }}</strong></div>
                    <div class="meta">模型：{{ $selectedModel?->name ?? '-' }}</div>
                </div>
                <div style="display:flex;gap:12px;align-items:center;">
                    <div class="meta">共 {{ count($rows) }} 条</div>
                    <a class="btn" href="{{ route('admin.contents.create', ['category_id' => $selectedCategory->id, 'model_id' => $selectedModel?->id]) }}">新增内容</a>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>标题</th>
                        <th>栏目</th>
                        <th>管理员</th>
                        <th>状态</th>
                        <th>发布时间</th>
                        <th>浏览量</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['id'] }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['category_title'] ?? '-' }}</td>
                            <td>{{ $row['username'] ?? '-' }}</td>
                            <td>{{ $row['status'] }}</td>
                            <td>{{ $row['publish_time_text'] ?: '-' }}</td>
                            <td>{{ $row['views'] ?? 0 }}</td>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <a class="badge" href="{{ route('admin.contents.edit', ['content' => $row['id'], 'category_id' => $selectedCategory->id, 'model_id' => $selectedModel?->id]) }}">编辑</a>
                                    <form method="post" action="{{ route('admin.contents.destroy', $row['id']) }}" onsubmit="return confirm('确认删除该内容吗？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="badge" style="border:0;cursor:pointer;background:#fef2f2;color:#b91c1c;">删除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="meta">当前栏目暂无内容。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endif
@endsection
