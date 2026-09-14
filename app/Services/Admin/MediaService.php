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
    public function paginate(int $perPage = 24, ?string $keyword = null, ?string $type = null): LengthAwarePaginator
    {
        $query = Media::query()->orderByDesc('id');
        $keyword = trim((string) $keyword);
        if ($keyword !== '') {
            $like = '%'.addcslashes($keyword, '%_\\').'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('filename', 'like', $like)
                    ->orWhere('alt', 'like', $like);
            });
        }
        if ($type === 'image') {
            $query->where('mime', 'like', 'image/%');
        } elseif ($type === 'file') {
            $query->where(function ($inner) {
                $inner->whereNull('mime')->orWhere('mime', 'not like', 'image/%');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /** 供选择器使用的最近图片 */
    public function recentImages(int $limit = 60): array
    {
        return Media::query()
            ->where('mime', 'like', 'image/%')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Media $m) => $this->toPickerItem($m))
            ->all();
    }

    /** @return array{id:int,url:string,filename:string,alt:?string} */
    public function toPickerItem(Media $media): array
    {
        return [
            'id' => $media->id,
            'url' => $media->url(),
            'filename' => $media->filename,
            'alt' => $media->alt,
        ];
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

    /** 缩略（最大边）+ 图/文字水印 */
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
        imagealphablending($dst, true);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagesavealpha($dst, true);
        }

        $pos = $this->watermarkPosition();
        $this->applyImageWatermark($dst, $nw, $nh, $pos);
        $this->applyTextWatermark($dst, $nw, $nh, $pos);

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

    /** @return 'br'|'bl'|'tr'|'tl'|'center' */
    protected function watermarkPosition(): string
    {
        $pos = strtolower(trim((string) Setting::getValue('upload_watermark_position', 'br')));

        return in_array($pos, ['br', 'bl', 'tr', 'tl', 'center'], true) ? $pos : 'br';
    }

    /** @param  \GdImage  $dst */
    protected function applyImageWatermark($dst, int $nw, int $nh, string $pos): void
    {
        $path = $this->resolveWatermarkImagePath((string) Setting::getValue('upload_watermark_image', ''));
        if (! $path) {
            return;
        }
        $info = @getimagesize($path);
        if (! $info) {
            return;
        }
        $wm = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };
        if (! $wm) {
            return;
        }
        $ww = imagesx($wm);
        $wh = imagesy($wm);
        $max = (int) max(24, min($nw, $nh) * 0.28);
        if ($ww > $max || $wh > $max) {
            $scale = min($max / $ww, $max / $wh);
            $tw = (int) max(1, round($ww * $scale));
            $th = (int) max(1, round($wh * $scale));
            $resized = imagecreatetruecolor($tw, $th);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $wm, 0, 0, 0, 0, $tw, $th, $ww, $wh);
            imagedestroy($wm);
            $wm = $resized;
            $ww = $tw;
            $wh = $th;
        }
        [$x, $y] = $this->watermarkXY($nw, $nh, $ww, $wh, $pos, 12);
        imagecopy($dst, $wm, $x, $y, 0, 0, $ww, $wh);
        imagedestroy($wm);
    }

    /** @param  \GdImage  $dst */
    protected function applyTextWatermark($dst, int $nw, int $nh, string $pos): void
    {
        $mark = trim((string) Setting::getValue('upload_watermark_text', ''));
        if ($mark === '') {
            return;
        }
        $color = imagecolorallocatealpha($dst, 255, 255, 255, 50);
        $shadow = imagecolorallocatealpha($dst, 0, 0, 0, 70);
        $font = $this->resolveTtfFont();
        if ($font && function_exists('imagettfbbox') && function_exists('imagettftext')) {
            $size = (int) max(12, min(22, (int) round($nw / 36)));
            $bbox = @imagettfbbox($size, 0, $font, $mark);
            if ($bbox) {
                $tw = (int) abs($bbox[2] - $bbox[0]);
                $th = (int) abs($bbox[7] - $bbox[1]);
                [$x, $y] = $this->watermarkXY($nw, $nh, $tw, $th, $pos, 12);
                $baseline = $y + $th;
                @imagettftext($dst, $size, 0, $x + 1, $baseline + 1, $shadow, $font, $mark);
                @imagettftext($dst, $size, 0, $x, $baseline, $color, $font, $mark);

                return;
            }
        }
        $fontId = 5;
        $tw = imagefontwidth($fontId) * strlen($mark);
        $th = imagefontheight($fontId);
        [$x, $y] = $this->watermarkXY($nw, $nh, $tw, $th, $pos, 10);
        imagestring($dst, $fontId, $x + 1, $y + 1, $mark, $shadow);
        imagestring($dst, $fontId, $x, $y, $mark, $color);
    }

    /** @return array{0:int,1:int} */
    protected function watermarkXY(int $nw, int $nh, int $tw, int $th, string $pos, int $pad): array
    {
        $x = $pad;
        $y = $pad;
        if ($pos === 'tr' || $pos === 'br') {
            $x = max($pad, $nw - $tw - $pad);
        }
        if ($pos === 'bl' || $pos === 'br') {
            $y = max($pad, $nh - $th - $pad);
        }
        if ($pos === 'center') {
            $x = max($pad, (int) (($nw - $tw) / 2));
            $y = max($pad, (int) (($nh - $th) / 2));
        }

        return [$x, $y];
    }

    protected function resolveWatermarkImagePath(string $stored): ?string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return null;
        }
        if (is_file($stored)) {
            return $stored;
        }
        $rel = $stored;
        if (preg_match('#/storage/(.+)$#', $stored, $m)) {
            $rel = $m[1];
        } elseif (str_starts_with($stored, 'storage/')) {
            $rel = substr($stored, 8);
        }
        $full = Storage::disk('public')->path($rel);

        return is_file($full) ? $full : null;
    }

    protected function resolveTtfFont(): ?string
    {
        $candidates = [
            'C:\\Windows\\Fonts\\msyh.ttf',
            'C:\\Windows\\Fonts\\simhei.ttf',
            'C:\\Windows\\Fonts\\simsun.ttc',
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-microhei.ttc',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
