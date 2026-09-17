@php
    $tplAsset = static function (string $rel): string {
        $path = public_path('vendor/tpl-editor/'.$rel);
        $t = is_file($path) ? (int) filemtime($path) : 1;

        return asset('vendor/tpl-editor/'.$rel).'?v='.$t;
    };
@endphp
<script src="{{ $tplAsset('vendor/codemirror.min.js') }}"></script>
<script src="{{ $tplAsset('vendor/xml.js') }}"></script>
<script src="{{ $tplAsset('vendor/javascript.js') }}"></script>
<script src="{{ $tplAsset('vendor/css.js') }}"></script>
<script src="{{ $tplAsset('vendor/htmlmixed.js') }}"></script>
<script src="{{ $tplAsset('vendor/xml-fold.js') }}"></script>
<script src="{{ $tplAsset('vendor/matchbrackets.js') }}"></script>
<script src="{{ $tplAsset('vendor/closetag.js') }}"></script>
<script src="{{ $tplAsset('vendor/active-line.js') }}"></script>
<script src="{{ $tplAsset('vendor/dialog.js') }}"></script>
<script src="{{ $tplAsset('vendor/searchcursor.js') }}"></script>
<script src="{{ $tplAsset('vendor/search.js') }}"></script>
<script src="{{ $tplAsset('blade.js') }}"></script>
<script src="{{ $tplAsset('boot.js') }}"></script>
