<?php

namespace App\Models\Cms;

use App\Models\Concerns\BelongsToSite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use BelongsToSite;

    protected $table = 'cms_media';

    protected $fillable = ['site_id', 'disk', 'path', 'filename', 'mime', 'size', 'alt', 'collection',];

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
