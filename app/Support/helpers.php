<?php

use App\Support\Cdn;

if (! function_exists('cms_media')) {
    function cms_media(?string $path): string
    {
        return Cdn::url($path);
    }
}
