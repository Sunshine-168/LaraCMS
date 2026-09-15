@extends('admin.layout')
@section('title', admin_t('locale.title'))
@section('icon', 'language')
@php
    $tab = $tab ?? 'content';
    $composeFocus = session('locale_quick_added') || $locales->isEmpty();
    $coverage = $coverage ?? [];
@endphp
@section('content')
    <div class="entry-index locale-index">
        <div class="tabs">
            <a href="{{ route('admin.locales.index', ['tab' => 'content']) }}" class="{{ $tab === 'content' ? 'active' : '' }}">{{ admin_t('locale.tab_content') }}</a>
            <a href="{{ route('admin.locales.index', ['tab' => 'admin']) }}" class="{{ $tab === 'admin' ? 'active' : '' }}">{{ admin_t('locale.tab_admin') }}</a>
            <a href="{{ route('admin.locales.index', ['tab' => 'theme']) }}" class="{{ $tab === 'theme' ? 'active' : '' }}">{{ admin_t('locale.tab_theme') }}</a>
        </div>

        @if($tab === 'content')
            <div class="locale-how">
                <h3>{{ admin_t('locale.how_title') }}</h3>
                <ol class="help-steps">
                    <li>{{ admin_t('locale.how_1') }}</li>
                    <li>{{ admin_t('locale.how_2') }}</li>
                    <li>{{ admin_t('locale.how_3') }}</li>
                </ol>
            </div>

            @if($errors->any())
                <div class="flash error">{{ $errors->first() }}</div>
            @endif

            <div class="locale-compose">
                <form method="post" action="{{ route('admin.locales.store') }}">
                    @csrf
                    <input type="hidden" name="is_active" value="1">
                    <label class="locale-compose-label" for="locale-quick-label">{{ admin_t('locale.add') }}</label>
                    <div class="locale-compose-row">
                        <input id="locale-quick-label" type="text" name="label" value="{{ old('label') }}" placeholder="{{ admin_t('locale.name_ph') }}" aria-label="{{ admin_t('locale.add') }}" required @if($composeFocus || $errors->has('label')) autofocus @endif>
                        <input class="locale-code" type="text" name="code" value="{{ old('code') }}" placeholder="{{ admin_t('locale.code_ph') }}" required @if($errors->has('code')) autofocus @endif>
                        <button class="btn" type="submit">{{ admin_t('locale.submit_add') }}</button>
                    </div>
                    <p class="muted field-hint">{!! admin_t('locale.add_hint', ['link' => '<a href="'.e(route('admin.locales.create')).'">'.e(admin_t('locale.open_form')).'</a>']) !!}</p>
                </form>
            </div>

            @if($locales->isEmpty())
                <div class="list-empty">
                    <p>{{ admin_t('locale.empty') }}</p>
                    <p class="muted">{{ admin_t('locale.empty_hint') }}</p>
                </div>
            @else
                <p class="muted recycle-lead">{{ admin_t('locale.lead') }}</p>
                <table class="data">
                    <thead>
                    <tr>
                        <th>{{ admin_t('locale.col_lang') }}</th>
                        <th>{{ admin_t('locale.col_fill') }}</th>
                        <th class="col-actions">{{ admin_t('locale.col_actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($locales as $locale)
                        @php $fill = $coverage[$locale->code] ?? ['filled' => 0, 'total' => 0]; @endphp
                        <tr @class(['is-off' => ! $locale->is_active])>
                            <td>
                                <div class="entry-row-title-line">
                                    <a class="entry-row-title" href="{{ route('admin.locales.edit', $locale) }}">{{ $locale->label ?: $locale->code }}</a>
                                    @if($locale->is_default)
                                        <span class="badge">{{ admin_t('locale.badge_default') }}</span>
                                    @endif
                                    @unless($locale->is_active)
                                        <span class="badge badge-off">{{ admin_t('locale.badge_off') }}</span>
                                    @endunless
                                </div>
                                <div class="entry-row-meta">
                                    {{ $locale->code }}
                                    @if($locale->native && $locale->native !== $locale->label)
                                        · {{ admin_t('locale.front_name', ['name' => $locale->native]) }}
                                    @endif
                                    @if($locale->is_default)
                                        · {{ $locale->hide_default ? admin_t('locale.no_prefix') : admin_t('locale.with_prefix') }}
                                    @endif
                                </div>
                            </td>
                            <td class="locale-fill">
                                {{ admin_t('locale.filled', ['filled' => $fill['filled'], 'total' => $fill['total']]) }}
                            </td>
                            <td class="actions">
                                @if($locale->is_default)
                                    <span class="muted">{{ admin_t('locale.current_default') }}</span>
                                @else
                                    <form action="{{ route('admin.locales.default', $locale) }}" method="post">
                                        @csrf
                                        <button class="btn btn-sm btn-muted" type="submit" @disabled(! $locale->is_active)>{{ admin_t('locale.make_default') }}</button>
                                    </form>
                                    <form action="{{ route('admin.locales.toggle', $locale) }}" method="post">
                                        @csrf
                                        <button class="btn btn-sm btn-muted" type="submit">{{ $locale->is_active ? admin_t('locale.disable') : admin_t('locale.enable') }}</button>
                                    </form>
                                    <form action="{{ route('admin.locales.destroy', $locale) }}" method="post" onsubmit="return confirm(@json(admin_t('locale.confirm_delete', ['name' => $locale->label ?: $locale->code])))">
                                        @csrf @method('DELETE')
                                        <button class="btn-quiet" type="submit">{{ admin_t('locale.delete') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <p class="muted entry-index-count">{{ admin_t('locale.count', ['n' => $locales->count()]) }}</p>
            @endif
        @elseif($tab === 'admin')
            <p class="muted recycle-lead">{{ admin_t('locale.admin_lead') }}</p>
            <form method="post" action="{{ route('admin.locales.ui') }}" class="locale-ui-form">
                @csrf
                @method('PUT')
                <label>{{ admin_t('locale.admin_mine') }}</label>
                <div class="locale-ui-choices">
                    @foreach(\App\Support\AdminUi::options() as $code => $label)
                        <label class="inline">
                            <input type="radio" name="ui_locale" value="{{ $code }}" @checked($adminUi === $code)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <label>{{ admin_t('locale.admin_site') }}</label>
                <div class="locale-ui-choices">
                    @foreach(\App\Support\AdminUi::options() as $code => $label)
                        <label class="inline">
                            <input type="radio" name="site_ui_locale" value="{{ $code }}" @checked($siteAdminUi === $code)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="muted field-hint">{{ admin_t('locale.admin_note') }}</p>
                <div class="form-actions">
                    <button class="btn" type="submit">{{ admin_t('locale.admin_save') }}</button>
                </div>
            </form>
        @else
            <p class="muted recycle-lead">{{ admin_t('locale.theme_lead') }}</p>
            <form method="post" action="{{ route('admin.locales.theme') }}" class="locale-theme-form">
                @csrf
                @method('PUT')
                <table class="data">
                    <thead>
                    <tr>
                        <th>{{ admin_t('locale.theme_key') }}</th>
                        @foreach($locales as $loc)
                            <th>
                                {{ $loc->label ?: $loc->code }}
                                @unless($loc->is_active)
                                    <span class="badge badge-off">{{ admin_t('locale.badge_off') }}</span>
                                @endunless
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($themeCatalog as $key => $meta)
                        <tr>
                            <td>{{ $meta['label'] }}<div class="entry-row-meta"><code>cms_ui('{{ $key }}')</code></div></td>
                            @foreach($locales as $loc)
                                <td>
                                    <input type="text" name="theme_ui[{{ $key }}][{{ $loc->code }}]" value="{{ old('theme_ui.'.$key.'.'.$loc->code, $themeStored[$key][$loc->code] ?? $meta[$loc->code] ?? $meta['zh_cn']) }}">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="form-actions">
                    <button class="btn" type="submit">{{ admin_t('locale.theme_save') }}</button>
                </div>
            </form>
        @endif
    </div>
@endsection
