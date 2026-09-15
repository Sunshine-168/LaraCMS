@extends('admin.layout')
@section('title', admin_t('nav.sitemap'))
@section('icon', 'map')
@php
    $options = $options ?? [];
    $counts = $counts ?? [];
    $preview = $preview ?? collect();
    $typeLabel = [
        'home' => '首页',
        'category' => '栏目',
        'entry' => '文章',
        'tag' => '标签',
        'extra' => '补充',
    ];
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ $xmlUrl }}" target="_blank" rel="noopener">打开 XML</a>
    @if(! empty($options['html']))
        <a class="btn btn-muted btn-sm" href="{{ $htmlUrl }}" target="_blank" rel="noopener">打开网页版</a>
    @endif
@endsection
@section('content')
    <div class="sitemap-page">
        <p class="muted recycle-lead">给搜索引擎看的地址清单。XML 在 <a href="{{ $xmlUrl }}" target="_blank" rel="noopener"><code>/sitemap.xml</code></a>，英文站是 <code>/en/sitemap.xml</code>。<a href="{{ cms_front_url('robots.txt') }}" target="_blank" rel="noopener"><code>robots.txt</code></a> 已经列出各语言。会员才能看的文章、没有文章的标签不会列入。</p>
        <p class="sitemap-counts">
            <strong>现在 {{ (int) ($counts['total'] ?? 0) }} 条</strong>
            <span>· 首页 {{ (int) ($counts['home'] ?? 0) }}</span>
            <span>· 栏目 {{ (int) ($counts['category'] ?? 0) }}</span>
            <span>· 文章 {{ (int) ($counts['entry'] ?? 0) }}</span>
            <span>· 标签 {{ (int) ($counts['tag'] ?? 0) }}</span>
            <span>· 补充 {{ (int) ($counts['extra'] ?? 0) }}</span>
        </p>

        <div class="sitemap-split">
            <div class="html-cache-card">
                <div class="sitemap-card-head">
                    <h3>收录范围</h3>
                </div>
                <form method="post" action="{{ route('admin.sitemap.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="sitemap-checks">
                        <label class="inline">
                            <input type="hidden" name="sitemap_categories" value="0">
                            <input type="checkbox" name="sitemap_categories" value="1" @checked(! empty($options['categories']))>
                            栏目（不含外链栏目）
                        </label>
                        <label class="inline">
                            <input type="hidden" name="sitemap_entries" value="0">
                            <input type="checkbox" name="sitemap_entries" value="1" @checked(! empty($options['entries']))>
                            已发布的公开文章
                        </label>
                        <label class="inline">
                            <input type="hidden" name="sitemap_tags" value="0">
                            <input type="checkbox" name="sitemap_tags" value="1" @checked(! empty($options['tags']))>
                            标签页
                        </label>
                        <label class="inline">
                            <input type="hidden" name="sitemap_html" value="0">
                            <input type="checkbox" name="sitemap_html" value="1" @checked(! empty($options['html']))>
                            提供网页版给访客（/sitemap）
                        </label>
                    </div>

                    <label for="sitemap_entry_limit">文章最多收录</label>
                    <input id="sitemap_entry_limit" type="number" name="sitemap_entry_limit" min="50" max="10000" value="{{ (int) ($options['entry_limit'] ?? 2000) }}">
                    <p class="muted field-hint">按最近更新取。太大时生成会变慢。首页始终收录。</p>

                    <label for="sitemap_extra">补充地址</label>
                    <textarea id="sitemap_extra" name="sitemap_extra" rows="4" placeholder="/about&#10;https://www.example.com/special">{{ $options['extra'] ?? '' }}</textarea>
                    <p class="muted field-hint">一行一条。本站路径按你写的收录（英文站请写 /en/...），也可以写完整 https://。不要写 HTML 标签。</p>

                    <div class="form-actions">
                        <button class="btn" type="submit">保存</button>
                    </div>
                </form>
            </div>

            <div class="html-cache-card">
                <div class="sitemap-card-head">
                    <h3>预览</h3>
                    <p class="muted field-hint">前 20 条@if(($total ?? 0) > 20)，一共 {{ (int) $total }} 条@endif。完整列表打开 XML。</p>
                </div>
                @if($preview->isEmpty())
                    <p class="muted">还没有地址。打开栏目和文章收录后再保存。</p>
                @else
                    <div class="table-scroll sitemap-preview-scroll">
                    <table class="data">
                        <thead>
                        <tr>
                            <th class="sitemap-col-type">类型</th>
                            <th>地址</th>
                            <th class="sitemap-col-date">更新</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($preview as $row)
                            @php
                                $kind = $row['type'] ?? '';
                                $title = $kind === 'home' ? '首页' : (string) ($row['title'] ?? '');
                                if ($title === '' || $title === ($row['loc'] ?? '')) {
                                    $title = '';
                                }
                            @endphp
                            <tr>
                                <td class="sitemap-col-type">{{ $typeLabel[$kind] ?? '其他' }}</td>
                                <td>
                                    @if($title !== '')
                                        <div class="sitemap-title">{{ $title }}</div>
                                    @endif
                                    <a class="sitemap-loc" href="{{ $row['loc'] }}" target="_blank" rel="noopener" title="{{ $row['loc'] }}">{{ $row['loc'] }}</a>
                                </td>
                                <td class="sitemap-col-date">
                                    @if(! empty($row['lastmod']))
                                        {{ \Illuminate\Support\Carbon::parse($row['lastmod'])->format('Y-m-d') }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
