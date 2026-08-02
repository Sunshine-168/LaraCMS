<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

class SiteConfig extends Model
{
    protected $table = 'config';

    public $timestamps = false;

    protected $guarded = [];
}
