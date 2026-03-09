<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class CategoryPriv extends Model
{
    protected $table = 'category_priv';

    public $timestamps = false;

    protected $guarded = [];
}
