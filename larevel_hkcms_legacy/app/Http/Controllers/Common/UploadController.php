<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Routine\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $files = $request->file('files', []);

        if ($files === [] || $files === null) {
            return response()->json([
                'msg' => 'No file uploaded',
                'data' => [],
                'success' => false,
                'code' => 400,
            ]);
        }

        $uploaded = [];

        foreach ((array) $files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $relativePath = $file->storeAs(
                'uploads/'.date('Ymd'),
                Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
                'public'
            );

            $publicPath = Storage::url($relativePath);

            $uploaded[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $publicPath,
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
            ];

            $attachment = Attachment::query()->firstOrNew([
                'path' => $publicPath,
                'storage' => 'local',
            ]);

            $attachment->fill([
                'user_id' => (int) Session::get('admin_user_id', 0),
                'user_type' => 1,
                'title' => Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 50, ''),
                'size' => $file->getSize(),
                'ext' => strtolower((string) $file->getClientOriginalExtension()),
                'mime_type' => (string) $file->getMimeType(),
                'md5' => md5_file($file->getRealPath()) ?: '',
                'remark' => '',
                'status' => 'normal',
                'update_time' => time(),
            ]);

            if (! $attachment->exists) {
                $attachment->create_time = time();
            }

            $attachment->save();
        }

        if ($uploaded === []) {
            return response()->json([
                'msg' => 'Upload failed',
                'data' => [],
                'success' => false,
                'code' => 400,
            ]);
        }

        return response()->json([
            'msg' => 'success',
            'data' => $uploaded,
            'success' => true,
            'file_path' => $uploaded[0]['path'],
            'code' => 200,
        ]);
    }
}
