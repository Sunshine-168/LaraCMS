@extends('themes.official.partials.layout')

@section('title', cms_t('说明 — LeafCMS', 'Docs — LeafCMS'))
@section('description', cms_t('后台「说明」里的怎么用、后台、主题、标签和安装，官网上是同一套。', 'The in-app guide — usage, admin, themes, tags, and install — is published here.'))

@section('content')
@php
    $topics = [
        'use' => cms_t('怎么用', 'Usage'),
        'admin' => cms_t('后台', 'Admin'),
        'themes' => cms_t('主题', 'Themes'),
        'theme' => cms_t('模板', 'Templates'),
        'tags' => cms_t('标签', 'Tags'),
        'env' => cms_t('环境', 'Environment'),
        'schedule' => cms_t('定时', 'Cron'),
        'laravel' => cms_t('命令行', 'CLI'),
        'docker' => 'Docker',
    ];
    $groups = [
        cms_t('说明', 'Guide') => ['use', 'admin', 'themes', 'theme', 'tags'],
        cms_t('安装', 'Install') => ['env', 'schedule', 'laravel', 'docker'],
    ];
    $topic = (string) request('topic', 'use');
    if (! array_key_exists($topic, $topics)) {
        $topic = 'use';
    }
    $doc = fn (string $key): string => cms_route('site.docs', ['topic' => $key]);
    $urls = [
        'helpUse' => $doc('use'),
        'helpAdmin' => $doc('admin'),
        'helpThemes' => $doc('themes'),
        'theme' => $doc('theme'),
        'tags' => $doc('tags'),
        'env' => $doc('env'),
        'schedule' => $doc('schedule'),
        'front' => cms_route('home'),
    ];
    $hl = static fn (string $key, string $label): string => e($label);
    $zhView = match ($topic) {
        'env' => 'install._deploy',
        'laravel' => 'install._laravel',
        'docker' => 'install._docker',
        default => 'admin.help._'.$topic,
    };
    $enView = 'admin.help.en._'.$topic;
    $helpView = \App\Support\Locale::isEnglish() && view()->exists($enView) ? $enView : $zhView;
@endphp
    <div class="page-hero">
        <div class="wrap">
            <div class="kicker"><i></i> Docs</div>
            <h1>{{ cms_t('系统里的说明', 'The in-app guide') }}</h1>
            <p class="lead">{{ cms_t('下面和后台「说明」是同一套：怎么用、后台每一栏、主题、模板标签，以及环境和安装。', 'This is the same guide as Admin → Help: usage, each admin screen, themes, tags, environment, and install.') }}</p>
        </div>
    </div>
    <section>
        <div class="wrap docs">
            <aside class="docs-nav" aria-label="{{ cms_t('说明目录', 'Docs menu') }}">
                @foreach($groups as $group => $keys)
                    <strong>{{ $group }}</strong>
                    @foreach($keys as $key)
                        <a href="{{ $doc($key) }}" @if($topic === $key) aria-current="page" @endif>{{ $topics[$key] }}</a>
                    @endforeach
                @endforeach
            </aside>
            <div class="docs-body help-page" data-copy-label="{{ admin_t('guide.copy') }}" data-copied-label="{{ admin_t('guide.copied') }}">
                @include($helpView, [
                    'urls' => $urls,
                    'hl' => $hl,
                    'siteId' => 1,
                    'compact' => false,
                    'scheduleUrl' => $doc('schedule'),
                    'deployUrl' => $doc('env'),
                    'webInstallUrl' => url('/install?way=web'),
                ])
            </div>
        </div>
    </section>
@endsection
