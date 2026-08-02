<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'cms_media';

    protected $fillable = [
        'disk', 'path', 'filename', 'mime', 'size', 'alt', 'collection',
    ];

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
