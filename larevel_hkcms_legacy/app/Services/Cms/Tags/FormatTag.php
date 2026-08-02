<?php

namespace App\Services\Cms\Tags;

use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class FormatTag
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function date(array $options = []): HtmlString
    {
        $value = $options['value'] ?? $options['name'] ?? null;
        $format = (string) ($options['format'] ?? 'Y-m-d H:i');
        $api = (string) ($options['api'] ?? '');

        if ($value === null || $value === '') {
            return new HtmlString('');
        }

        try {
            $time = is_numeric($value)
                ? Carbon::createFromTimestamp((int) $value)
                : Carbon::parse((string) $value);
        } catch (\Throwable) {
            return new HtmlString(e((string) $value));
        }

        if ($api === 'human') {
            return new HtmlString(e($time->diffForHumans()));
        }

        return new HtmlString(e($time->format($format)));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function substr(array $options = []): HtmlString
    {
        $value = (string) ($options['value'] ?? $options['name'] ?? '');
        $len = max(0, (int) ($options['len'] ?? 0));
        $dot = (string) ($options['dot'] ?? '...');

        if ($len <= 0 || mb_strlen($value) <= $len) {
            return new HtmlString(e($value));
        }

        return new HtmlString(e(mb_substr($value, 0, $len).$dot));
    }
}
