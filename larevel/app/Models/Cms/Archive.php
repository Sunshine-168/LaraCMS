<?php

namespace App\Models\Cms;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Archive extends Model
{
    protected $table = 'archives';

    public $timestamps = false;

    protected $guarded = [];

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'normal')
            ->whereNull('delete_time');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function modelDef(): BelongsTo
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }

    public function publishTimeText(): ?string
    {
        if (empty($this->publish_time)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $this->publish_time)->format('Y-m-d H:i:s');
    }

    public function routeKeyValue(): string
    {
        return $this->diyname ?: (string) $this->getKey();
    }

    public function url(): string
    {
        $category = $this->category;

        if (! $category) {
            return '#';
        }

        return route('cms.content.show', [
            'catname' => $category->name,
            'id' => $this->routeKeyValue(),
        ]);
    }

    public function extra(): array
    {
        $model = $this->modelDef;

        if (! $model || empty($model->tablename)) {
            return [];
        }

        $record = DB::table($model->tablename)->where('id', $this->getKey())->first();

        return $record ? (array) $record : [];
    }
}
