@extends('admin.layout')
@section('title', $locale->exists ? admin_t('locale.form_edit') : admin_t('locale.form_new'))
@section('icon', 'language')
@php
    $isDefaultNow = (bool) old('is_default', $locale->is_default);
@endphp
@section('header_actions')
    <a class="btn btn-muted btn-sm" href="{{ route('admin.locales.index', ['tab' => 'content']) }}">{{ admin_back('locales') }}</a>
@endsection
@section('content')
    @if($errors->any())
        <div class="flash error">{{ $errors->first() }}</div>
    @endif
    <form method="post" class="locale-form" action="{{ $locale->exists ? route('admin.locales.update', $locale) : route('admin.locales.store') }}">
        @csrf
        @if($locale->exists) @method('PUT') @endif
        <input type="hidden" name="from_form" value="1">

        <label for="locale-label">{{ admin_t('locale.name') }}</label>
        <input id="locale-label" type="text" name="label" value="{{ old('label', $locale->label) }}" placeholder="{{ admin_t('locale.name_ph_form') }}" required autofocus>
        <p class="muted field-hint">{{ admin_t('locale.name_hint') }}</p>

        <label for="locale-native">{{ admin_t('locale.native') }}</label>
        <input id="locale-native" type="text" name="native" value="{{ old('native', $locale->native) }}" placeholder="{{ admin_t('locale.native_ph') }}">
        <p class="muted field-hint">{{ admin_t('locale.native_hint') }}</p>

        <label for="locale-code">{{ admin_t('locale.code') }}</label>
        <input id="locale-code" type="text" name="code" value="{{ old('code', $locale->code) }}" placeholder="{{ admin_t('locale.code_ph_form') }}" required {{ $locale->exists ? 'readonly' : '' }}>
        <p class="muted field-hint">{{ $locale->exists ? admin_t('locale.code_locked') : admin_t('locale.code_hint') }}</p>

        <label for="locale-sort">{{ admin_t('locale.sort') }}</label>
        <input id="locale-sort" type="number" name="sort" value="{{ old('sort', $locale->sort ?? 0) }}" min="0" max="9999">
        <p class="muted field-hint">{{ admin_t('locale.sort_hint') }}</p>

        @if($locale->exists && $locale->is_default)
            <input type="hidden" name="is_default" value="1">
            <p class="muted field-hint">{{ admin_t('locale.is_default_locked') }}</p>
        @else
            <label class="inline">
                <input type="hidden" name="is_default" value="0">
                <input id="locale-is-default" type="checkbox" name="is_default" value="1" @checked($isDefaultNow)>
                {{ admin_t('locale.set_default') }}
            </label>
            <p class="muted field-hint">{{ admin_t('locale.set_default_hint') }}</p>
        @endif

        <div id="locale-hide-default" @style(['display:none' => ! $isDefaultNow])>
            <label class="inline">
                <input type="hidden" name="hide_default" value="0">
                <input type="checkbox" name="hide_default" value="1" @checked(old('hide_default', $locale->hide_default ?? true))>
                {{ admin_t('locale.hide_prefix') }}
            </label>
            <p class="muted field-hint">{{ admin_t('locale.hide_prefix_hint') }}</p>
        </div>

        @if($locale->exists && $locale->is_default)
            <input type="hidden" name="is_active" value="1">
        @else
            <label class="inline">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $locale->is_active ?? true))>
                {{ admin_t('locale.enable_front') }}
            </label>
        @endif

        <div class="form-actions">
            <button class="btn" type="submit">{{ $locale->exists ? admin_t('locale.save') : admin_t('locale.submit_add_form') }}</button>
            <a class="btn btn-muted" href="{{ route('admin.locales.index', ['tab' => 'content']) }}">{{ admin_t('locale.cancel') }}</a>
        </div>
    </form>
@endsection
@push('scripts')
<script>
document.getElementById('locale-is-default')?.addEventListener('change', function () {
    var box = document.getElementById('locale-hide-default');
    if (box) box.style.display = this.checked ? '' : 'none';
});
</script>
@endpush
