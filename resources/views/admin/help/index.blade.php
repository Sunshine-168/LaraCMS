@extends('admin.layout')
@section('title', '说明')
@section('icon', 'book')
@php
    $topic = $topic ?? 'use';
    $siteId = $siteId ?? 1;
    $urls = $urls ?? [];
    $hl = function (string $key, string $label) use ($urls): string {
        $url = $urls[$key] ?? null;

        return $url ? '<a href="'.e($url).'">'.e($label).'</a>' : e($label);
    };
    $groups = [
        '使用' => [
            'use' => '怎么用',
            'admin' => '后台',
            'themes' => '主题',
            'theme' => '模板',
            'tags' => '标签',
        ],
        '安装' => [
            'env' => '环境',
            'schedule' => '定时',
            'laravel' => '命令行',
            'docker' => 'Docker',
        ],
    ];
@endphp
@section('content')
    <div class="help-page">
        <p class="muted recycle-lead">怎么用、后台每一项、主题和模板标签。装系统、nginx 和定时任务在「安装」里。</p>

        <div class="help-nav">
            @foreach($groups as $group => $items)
                <div class="help-nav-group">
                    <span>{{ $group }}</span>
                    <div class="tabs">
                        @foreach($items as $key => $label)
                            <a class="{{ $topic === $key ? 'active' : '' }}" href="{{ route('admin.help', ['topic' => $key]) }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @if($topic === 'use')
            <div class="help-body">
                @include('admin.help._use')
            </div>
        @elseif($topic === 'admin')
            <div class="help-body">
                @include('admin.help._admin')
            </div>
        @elseif($topic === 'themes')
            <div class="help-body">
                @include('admin.help._themes')
            </div>
        @elseif($topic === 'theme')
            <div class="help-body">
                @include('admin.help._theme')
            </div>
        @elseif($topic === 'tags')
            <div class="help-body">
                @include('admin.help._tags')
            </div>
        @elseif($topic === 'env')
            <div class="help-body deploy-guide">
                @include('install._deploy', [
                    'siteId' => $siteId,
                    'compact' => false,
                    'scheduleUrl' => route('admin.help', ['topic' => 'schedule']),
                ])
            </div>
        @elseif($topic === 'schedule')
            <div class="help-body">
                @include('admin.help._schedule')
            </div>
        @elseif($topic === 'laravel')
            <div class="help-body">
                @include('install._laravel', [
                    'deployUrl' => route('admin.help', ['topic' => 'env']),
                    'webInstallUrl' => url('/install?way=web'),
                ])
            </div>
        @else
            <div class="help-body">
                @include('install._docker')
            </div>
        @endif
    </div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var code = btn.parentElement && btn.parentElement.querySelector('code');
        if (!code) return;
        var text = code.innerText;
        function done() {
            btn.textContent = '已复制';
            setTimeout(function () { btn.textContent = '复制'; }, 1500);
        }
        function fallback() {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); done(); } catch (e) {}
            document.body.removeChild(ta);
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(fallback);
        } else {
            fallback();
        }
    });
});
</script>
@endpush
