<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\Entry;
use App\Models\Cms\Media;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class FileinfoTag
{
    public function __construct(private readonly CmsViewContext $context) {}

    /**
     * 附件信息列表
     *
     * @param  array<string, mixed>  $options  field|entry|aid|name
     */
    public function get(array $options = []): Collection
    {
        $entry = $options['entry'] ?? $this->context->article();
        if (! $entry && ! empty($options['aid'])) {
            $entry = Entry::query()->find((int) $options['aid']);
        }
        if (! $entry instanceof Entry) {
            return collect();
        }

        $fields = $options['field'] ?? $options['name'] ?? 'file';
        $fields = is_array($fields) ? $fields : [$fields];
        $out = collect();

        foreach ($fields as $field) {
            $raw = $entry->attr((string) $field);
            if (! $raw) {
                continue;
            }
            $paths = is_array($raw) ? $raw : [$raw];
            foreach ($paths as $path) {
                $out->push($this->describe((string) $path, (string) $field, $entry));
            }
        }

        return $out->filter()->values();
    }

    protected function describe(string $path, string $field, Entry $entry): object
    {
        $isUrl = str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
        $media = ! $isUrl
            ? Media::query()->where('path', $path)->first()
            : null;

        $size = $media?->size ?? 0;
        if (! $isUrl && ! $size && Storage::disk('public')->exists($path)) {
            $size = Storage::disk('public')->size($path);
        }

        $filename = $media?->filename ?: basename(parse_url($path, PHP_URL_PATH) ?: $path);
        $downloadUrl = route('download', $entry).'?field='.urlencode($field);

        return (object) [
            'field' => $field,
            'path' => $path,
            'filename' => $filename,
            'mime' => $media?->mime,
            'size' => $size,
            'size_text' => $size > 0 ? number_format($size / 1024, 1).' KB' : ($entry->attr('size') ?: ''),
            'url' => cms_media($isUrl ? $path : ($media ? $media->url() : Storage::disk('public')->url($path))),
            'download_url' => $downloadUrl,
            'entry' => $entry,
        ];
    }
}
