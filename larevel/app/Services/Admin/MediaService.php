<?php

namespace App\Services\Admin;

use App\Models\Cms\Media;
use App\Models\Cms\Setting;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    /** 媒体分页 */
    public function paginate(int $perPage = 24): LengthAwarePaginator
    {
        return Media::query()->orderByDesc('id')->paginate($perPage);
    }

    /** 供选择器使用的最近图片 */
    public function recentImages(int $limit = 60): array
    {
        return Media::query()
            ->where('mime', 'like', 'image/%')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Media $m) => [
                'id' => $m->id,
                'url' => Storage::disk($m->disk)->url($m->path),
                'filename' => $m->filename,
                'alt' => $m->alt,
            ])
            ->all();
    }

    /** 上传文件（图片可缩略 / 水印） */
    public function upload(UploadedFile $file, ?string $alt = null): array
    {
        $path = $file->store('cms/'.date('Y/m'), 'public');
        $full = Storage::disk('public')->path($path);
        if (str_starts_with((string) $file->getMimeType(), 'image/')) {
            $this->processImage($full);
        }

        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => filesize($full) ?: ($file->getSize() ?: 0),
            'alt' => $alt ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ]);

        return Result::ok($media, '上传成功');
    }

    /** 删除媒体 */
    public function delete(Media $media): array
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return Result::ok(null, '已删除');
    }

    /** 缩略（最大边）+ 文字水印 */
    protected function processImage(string $fullPath): void
    {
        if (! function_exists('imagecreatetruecolor') || ! is_file($fullPath)) {
            return;
        }

        $info = @getimagesize($fullPath);
        if (! $info) {
            return;
        }
        [$w, $h, $type] = $info;
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : false,
            IMAGETYPE_GIF => @imagecreatefromgif($fullPath),
            default => false,
        };
        if (! $src) {
            return;
        }

        $maxW = (int) Setting::getValue('upload_thumb_width', 0);
        $nw = $w;
        $nh = $h;
        if ($maxW > 0 && $w > $maxW) {
            $nw = $maxW;
            $nh = (int) max(1, round($h * ($maxW / $w)));
        }

        $dst = imagecreatetruecolor($nw, $nh);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $mark = trim((string) Setting::getValue('upload_watermark_text', ''));
        if ($mark !== '') {
            $color = imagecolorallocatealpha($dst, 255, 255, 255, 60);
            $shadow = imagecolorallocatealpha($dst, 0, 0, 0, 80);
            $x = max(8, $nw - (strlen($mark) * 7) - 12);
            $y = max(16, $nh - 14);
            imagestring($dst, 3, $x + 1, $y + 1, $mark, $shadow);
            imagestring($dst, 3, $x, $y, $mark, $color);
        }

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($dst, $fullPath, 85),
            IMAGETYPE_PNG => imagepng($dst, $fullPath, 6),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($dst, $fullPath, 85) : imagejpeg($dst, $fullPath, 85),
            IMAGETYPE_GIF => imagegif($dst, $fullPath),
            default => null,
        };

        imagedestroy($src);
        imagedestroy($dst);
    }
}

