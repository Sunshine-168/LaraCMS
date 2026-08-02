<?php

namespace App\Services\Cms\Api;

use App\Models\Cms\Archive;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\ModelField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContentApiService
{
    public function listPage(array $filters, int $page, int $limit): array
    {
        $model = $this->resolveModel($filters);
        $table = $model->tablename;
        $extraColumns = $this->listExtraColumns($table);

        $query = DB::table('archives as A')->join($table.' as B', 'A.id', '=', 'B.id');
        $this->applyFilters($query, $filters, $model->id);

        $count = (clone $query)->count();
        $rows = $query
            ->orderBy('A.weigh')
            ->orderByDesc('A.publish_time')
            ->forPage($page, $limit)
            ->get(array_merge(['A.*'], array_map(fn ($column) => 'B.'.$column, $extraColumns)));

        $categoryIds = collect($rows)->pluck('category_id')->filter()->unique()->all();
        $categories = Category::query()->whereIn('id', $categoryIds)->get()->keyBy('id');

        $lists = collect($rows)->map(function ($row) use ($categories): array {
            $item = (array) $row;
            $item['category'] = $categories[(int) ($item['category_id'] ?? 0)]?->toArray();
            return $item;
        })->values()->all();

        return compact('lists', 'count');
    }

    public function details(int $id, int $page): ?array
    {
        $archive = Archive::query()->with(['category', 'modelDef'])->find($id);

        if (! $archive || ! $archive->modelDef) {
            return null;
        }

        $table = $archive->modelDef->tablename;
        $extra = $archive->extra();

        if (isset($extra['content'])) {
            $chunks = explode('#page#', html_entity_decode((string) $extra['content'], ENT_QUOTES, 'UTF-8'));
            $extra['content'] = $chunks[$page - 1] ?? ($chunks[0] ?? '');
        }

        $data = array_merge($archive->toArray(), $extra);
        $data['category'] = $archive->category?->toArray();
        $data['model'] = $archive->modelDef->toArray();
        $data['fields'] = ModelField::query()
            ->active()
            ->where('model_id', $archive->model_id)
            ->get()
            ->keyBy('field_name')
            ->map(fn (ModelField $field) => $field->toArray())
            ->all();
        $data['_sub_table'] = $table;

        return $data;
    }

    public function fetchRecommendContent(array $payload, string $lang): array
    {
        $limit = max(1, (int) ($payload['limit'] ?? 10));
        $order = (string) ($payload['order'] ?? 'weigh asc,id desc');
        $categoryIds = array_values(array_filter((array) ($payload['column'] ?? []), fn ($item) => is_numeric($item)));
        $model = $this->resolveRecommendModel((string) ($payload['model'] ?? ''));

        if (! $model) {
            return [];
        }

        $query = Archive::query()
            ->with('category')
            ->where('model_id', $model->id)
            ->where('status', 'normal')
            ->where('lang', $lang);

        if ($categoryIds !== []) {
            $query->whereIn('category_id', array_map('intval', $categoryIds));
        }

        foreach (explode(',', $order) as $segment) {
            $parts = preg_split('/\s+/', trim($segment));
            $column = $parts[0] ?? null;
            $direction = strtolower($parts[1] ?? 'asc');

            if ($column && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) {
                $query->orderBy($column, $direction === 'desc' ? 'desc' : 'asc');
            }
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(function (Archive $archive): array {
                $data = $archive->toArray();
                $data['category'] = $archive->category?->toArray();
                return $data;
            })
            ->all();
    }

    private function resolveModel(array $filters): ContentModel
    {
        if (! empty($filters['model_id']) && is_numeric($filters['model_id'])) {
            $model = ContentModel::query()->find((int) $filters['model_id']);
        } else {
            $catId = $filters['catid'];
            $catId = is_array($catId) ? (int) ($catId[0] ?? 0) : (int) $catId;
            $category = Category::query()->find($catId);
            $model = $category ? ContentModel::query()->find((int) $category->model_id) : null;
        }

        if (! $model || empty($model->tablename)) {
            abort(404, '内容模型不存在');
        }

        return $model;
    }

    private function resolveRecommendModel(string $value): ?ContentModel
    {
        if ($value === '') {
            return null;
        }

        return ContentModel::query()
            ->where('diyname', $value)
            ->orWhere('tablename', $value)
            ->orWhere('controller', $value)
            ->first();
    }

    private function applyFilters(QueryBuilder $query, array $filters, int $modelId): void
    {
        $query
            ->when(isset($filters['model_id']) && is_numeric($filters['model_id']), fn ($q) => $q->where('A.model_id', (int) $filters['model_id']))
            ->when(isset($filters['catid']) && $filters['catid'] !== '', function ($q) use ($filters): void {
                if (is_array($filters['catid'])) {
                    $q->whereIn('A.category_id', array_map('intval', $filters['catid']));
                } else {
                    $q->where('A.category_id', (int) $filters['catid']);
                }
            })
            ->when(isset($filters['lang']) && $filters['lang'] !== '', fn ($q) => $q->where('A.lang', $filters['lang']))
            ->when(isset($filters['title']) && $filters['title'] !== '', fn ($q) => $q->where('A.title', 'like', '%'.$filters['title'].'%'))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($q) => $q->where('A.status', $filters['status']));
    }

    private function listExtraColumns(string $table): array
    {
        return collect(Schema::getColumnListing($table))
            ->reject(fn (string $column) => in_array($column, ['id', 'content'], true))
            ->values()
            ->all();
    }
}
