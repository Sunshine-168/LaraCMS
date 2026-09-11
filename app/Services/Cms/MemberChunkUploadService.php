<?php

namespace App\Services\Cms;

use App\Models\Cms\Media;
use App\Models\Cms\Member;
use App\Support\Result;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberChunkUploadService
{
    private const CHUNK_TTL = 7200;

    private const MAX_CHUNKS = 2000;

    private const MAX_CHUNK_BYTES = 2_097_152; // 2MB

    private const MAX_TOTAL_BYTES = 52_428_800; // 50MB

    /** 初始化分片会话 */
    public function init(Member $member, string $filename, int $totalChunks, int $totalSize): array
    {
        if (! $member->canMember('upload')) {
            return Result::fail('无上传权限');
        }
        if ($totalChunks < 1 || $totalChunks > self::MAX_CHUNKS) {
            return Result::fail('分片数量无效');
        }
        if ($totalSize < 1 || $totalSize > self::MAX_TOTAL_BYTES) {
            return Result::fail('文件过大（上限 50MB）');
        }

        $safeName = $this->safeFilename($filename);
        $uploadId = (string) Str::uuid();
        $meta = [
            'member_id' => $member->id,
            'filename' => $safeName,
            'total_chunks' => $totalChunks,
            'total_size' => $totalSize,
            'received' => [],
        ];
        Cache::put($this->cacheKey($uploadId), $meta, self::CHUNK_TTL);
        Storage::disk('local')->makeDirectory('chunks/'.$uploadId);

        return Result::ok(['upload_id' => $uploadId], 'ok');
    }

    public function storeChunk(Member $member, string $uploadId, int $index, UploadedFile $chunk): array
    {
        if (! $member->canMember('upload')) {
            return Result::fail('无上传权限');
        }
        $meta = Cache::get($this->cacheKey($uploadId));
        if (! is_array($meta) || (int) ($meta['member_id'] ?? 0) !== (int) $member->id) {
            return Result::fail('上传会话无效或已过期');
        }
        if ($index < 0 || $index >= (int) $meta['total_chunks']) {
            return Result::fail('分片序号无效');
        }
        if ($chunk->getSize() > self::MAX_CHUNK_BYTES) {
            return Result::fail('分片过大');
        }

        $path = 'chunks/'.$uploadId.'/'.$index.'.part';
        Storage::disk('local')->put($path, file_get_contents($chunk->getRealPath() ?: '') ?: '');
        $received = array_map('intval', $meta['received'] ?? []);
        $received[] = $index;
        $meta['received'] = array_values(array_unique($received));
        Cache::put($this->cacheKey($uploadId), $meta, self::CHUNK_TTL);

        return Result::ok(['index' => $index, 'received' => count($meta['received'])], 'ok');
    }

    /** 合并并入库；purpose=avatar 时写入会员头像 */
    public function complete(Member $member, string $uploadId, string $purpose = 'file'): array
    {
        if (! $member->canMember('upload')) {
            return Result::fail('无上传权限');
        }
        $meta = Cache::get($this->cacheKey($uploadId));
        if (! is_array($meta) || (int) ($meta['member_id'] ?? 0) !== (int) $member->id) {
            return Result::fail('上传会话无效或已过期');
        }

        $total = (int) $meta['total_chunks'];
        $received = array_map('intval', $meta['received'] ?? []);
        for ($i = 0; $i < $total; $i++) {
            if (! in_array($i, $received, true) || ! Storage::disk('local')->exists('chunks/'.$uploadId.'/'.$i.'.part')) {
                return Result::fail('分片未齐：缺少 '.$i);
            }
        }

        $ext = pathinfo((string) $meta['filename'], PATHINFO_EXTENSION) ?: 'bin';
        $destRel = 'cms/members/'.date('Y/m').'/'.Str::lower(Str::random(16)).'.'.$ext;
        $destFull = Storage::disk('public')->path($destRel);
        Storage::disk('public')->makeDirectory(dirname($destRel));

        $out = fopen($destFull, 'wb');
        if (! $out) {
            return Result::fail('无法写入目标文件');
        }
        $written = 0;
        for ($i = 0; $i < $total; $i++) {
            $part = Storage::disk('local')->path('chunks/'.$uploadId.'/'.$i.'.part');
            $in = fopen($part, 'rb');
            if (! $in) {
                fclose($out);
                @unlink($destFull);

                return Result::fail('读取分片失败');
            }
            while (! feof($in)) {
                $buf = fread($in, 8192);
                if ($buf === false) {
                    break;
                }
                $written += strlen($buf);
                fwrite($out, $buf);
            }
            fclose($in);
        }
        fclose($out);

        if ($written > self::MAX_TOTAL_BYTES) {
            @unlink($destFull);
            $this->cleanup($uploadId);

            return Result::fail('合并后超过大小限制');
        }

        $mime = @mime_content_type($destFull) ?: 'application/octet-stream';
        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $destRel,
            'filename' => $meta['filename'],
            'mime' => $mime,
            'size' => $written,
            'alt' => pathinfo((string) $meta['filename'], PATHINFO_FILENAME),
        ]);
        $url = Storage::disk('public')->url($destRel);

        if ($purpose === 'avatar') {
            if (! str_starts_with($mime, 'image/')) {
                Storage::disk('public')->delete($destRel);
                $media->delete();
                $this->cleanup($uploadId);

                return Result::fail('头像须为图片');
            }
            $member->avatar = $url;
            $member->save();
        }

        $this->cleanup($uploadId);

        return Result::ok([
            'url' => $url,
            'media_id' => $media->id,
            'filename' => $meta['filename'],
            'mime' => $mime,
            'size' => $written,
        ], '上传完成');
    }

    protected function cleanup(string $uploadId): void
    {
        Cache::forget($this->cacheKey($uploadId));
        Storage::disk('local')->deleteDirectory('chunks/'.$uploadId);
    }

    protected function cacheKey(string $uploadId): string
    {
        return 'member_chunk:'.$uploadId;
    }

    protected function safeFilename(string $name): string
    {
        $base = basename(str_replace(["\0", '\\'], '', $name));
        $base = preg_replace('/[^\w.\-\x{4e00}-\x{9fff}]+/u', '_', $base) ?: 'file.bin';

        return Str::limit($base, 180, '');
    }
}
