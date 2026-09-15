@extends($layout ?? 'themes.enterprise.partials.layout')
@section('content')
    @cmsBreadcrumb(['last' => cms_ui('sitemap')])
    <h1>{{ cms_ui('sitemap') }}</h1>
    <p class="muted">{{ cms_ui('sitemap_hint') }}</p>

    @foreach($groups ?? [] as $key => $items)
        @if($items !== [])
            <section class="card-soft sitemap-page-block">
                <h2>
                    @if($key === 'home') {{ cms_ui('home') }}
                    @elseif($key === 'category') {{ cms_ui('sitemap_cats') }}
                    @elseif($key === 'entry') {{ cms_ui('sitemap_posts') }}
                    @elseif($key === 'tag') {{ cms_ui('sitemap_tags') }}
                    @else {{ cms_ui('sitemap_extra') }}
                    @endif
                </h2>
                <ul>
                    @foreach($items as $item)
                        @php
                            $loc = (string) ($item['loc'] ?? '');
                            $host = rtrim((string) url('/'), '/');
                            $offsite = (str_starts_with($loc, 'http://') || str_starts_with($loc, 'https://'))
                                && ! str_starts_with($loc, $host);
                        @endphp
                        <li><a href="{{ $loc }}" rel="{{ $offsite ? 'noopener nofollow' : 'noopener' }}">{{ $key === 'home' ? cms_ui('home') : $item['title'] }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach
@endsection
