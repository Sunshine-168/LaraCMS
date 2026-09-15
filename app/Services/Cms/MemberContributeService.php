<?php

namespace App\Services\Cms;

use App\Events\EntrySaved;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Services\Admin\FieldBindService;
use App\Support\Locale;
use App\Support\Result;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MemberContributeService
{
    public function __construct(
        private readonly HtmlCacheService $htmlCache,
        private readonly FieldBindService $fieldBind,
        private readonly OpsNotifyService $opsNotify,
    ) {}

    public function modelsFor(Member $member): Collection
    {
        if (! $member->canMember('contribute')) {
            return collect();
        }

        return ContentModel::query()
            ->where('is_active', true)
            ->where('allow_contribute', true)
            ->where('kind', 'list')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function categoriesFor(ContentModel $model): Collection
    {
        return Category::query()
            ->where('model_id', $model->id)
            ->where('type', 'list')
            ->published()
            ->orderBy('sort_order')
            ->get();
    }

    /** @param  array<string, mixed>  $input */
    public function save(Member $member, ?Entry $entry, array $input, bool $submit = false): array
    {
        if (! $member->canMember('contribute')) {
            return Result::fail(cms_ui('cannot_contribute'));
        }

        $models = $this->modelsFor($member);
        $modelId = (int) ($input['model_id'] ?? $entry?->model_id ?? 0);
        $model = $models->firstWhere('id', $modelId);
        if (! $model) {
            return Result::fail(cms_ui('cannot_contribute_model'));
        }

        if ($entry) {
            $lock = $this->assertOwnedEditable($member, $entry);
            if ($lock) {
                return Result::fail($lock);
            }
        } else {
            $entry = new Entry;
            $entry->member_id = $member->id;
            $entry->status = 'draft';
        }
        $wasPending = $entry->exists && $entry->status === 'pending';
        $creating = ! $entry->exists;

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            return Result::fail(cms_ui('fill_title'));
        }

        $categoryId = (int) ($input['category_id'] ?? 0);
        $category = $this->categoriesFor($model)->firstWhere('id', $categoryId);
        if (! $category) {
            return Result::fail(cms_ui('pick_category'));
        }

        $slug = $this->uniqueSlug((string) ($input['slug'] ?? ''), $title, $entry->exists ? (int) $entry->id : null);

        $entry->model_id = $model->id;
        $entry->category_id = $category->id;
        $entry->cover = trim((string) ($input['cover'] ?? '')) ?: null;
        $locales = array_unique([Locale::default(), Locale::current()]);
        foreach ($locales as $locale) {
            $entry->setTranslation('title', $locale, $title);
            $entry->setTranslation('slug', $locale, $slug);
            $entry->setTranslation('excerpt', $locale, trim((string) ($input['excerpt'] ?? '')));
            $entry->setTranslation('content', $locale, (string) ($input['content'] ?? ''));
        }
        $entry->member_id = $member->id;
        $attrs = $this->mergeAttrs($entry, $model, (int) $category->id, $input);
        if (is_string($attrs)) {
            return Result::fail($attrs);
        }
        $entry->attrs = $attrs;

        if ($submit) {
            $entry->status = 'pending';
            $entry->published_at = null;
        } elseif ($entry->status === 'published') {
            return Result::fail(cms_ui('published_locked'));
        } elseif ($entry->exists && $entry->status === 'pending') {
            $entry->status = 'pending';
            $entry->published_at = null;
        } else {
            $entry->status = 'draft';
            $entry->published_at = null;
        }

        $entry->rebuildSearchText();
        $entry->save();
        $entry->categories()->sync([$category->id]);
        $this->htmlCache->forgetByEntry($entry);
        if ($submit && ! $wasPending) {
            $this->opsNotify->entryPending($entry->loadMissing('member'));
        }
        event(new EntrySaved($entry, $creating, false));

        return Result::ok($entry, $submit ? cms_ui('submitted_review') : cms_ui('draft_saved'));
    }

    public function submit(Member $member, Entry $entry): array
    {
        return $this->save($member, $entry, [
            'model_id' => $entry->model_id,
            'category_id' => $entry->category_id,
            'title' => $entry->t('title'),
            'slug' => $entry->t('slug'),
            'excerpt' => $entry->t('excerpt'),
            'content' => $entry->t('content'),
            'cover' => $entry->cover,
            'attrs' => $entry->attrs ?? [],
        ], true);
    }

    public function assertOwnedEditable(Member $member, Entry $entry): ?string
    {
        if ((int) $entry->member_id !== (int) $member->id) {
            return cms_ui('only_own_posts');
        }
        if ($entry->status === 'published') {
            return cms_ui('published_locked');
        }

        return null;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'published' => cms_ui('status_published'),
            'pending' => cms_ui('status_pending'),
            'draft' => cms_ui('status_draft'),
            default => $status,
        };
    }

    protected function uniqueSlug(string $raw, string $title, ?int $ignoreId): string
    {
        $slug = Str::slug($raw !== '' ? $raw : $title);
        if ($slug === '') {
            $slug = 'm'.strtolower(Str::random(8));
        }
        $locale = Locale::default();
        $base = mb_substr($slug, 0, 80);
        $try = $base;
        $i = 1;
        while (Entry::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug->'.$locale, $try)
            ->exists()
        ) {
            $try = $base.'-'.$i++;
        }

        return $try;
    }

    /**
     * 只接受当前栏目可见的模型字段，避免投稿写入任意 attrs。
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|string
     */
    protected function mergeAttrs(Entry $entry, ContentModel $model, int $categoryId, array $input): array|string
    {
        $current = is_array($entry->attrs) ? $entry->attrs : [];
        $posted = $input['attrs'] ?? [];
        if (! is_array($posted)) {
            $posted = [];
        }
        foreach ($this->fieldBind->resolveFields($model, $categoryId) as $field) {
            $name = (string) $field->name;
            if ($name === '' || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                continue;
            }
            $type = (string) $field->type;
            if (in_array($type, ['checkbox', 'switch'], true)) {
                $current[$name] = ! empty($posted[$name]) ? '1' : '0';
            } elseif (array_key_exists($name, $posted)) {
                $val = $posted[$name];
                if (is_array($val)) {
                    $val = implode(',', array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $val));
                } elseif (is_bool($val)) {
                    $val = $val ? '1' : '0';
                } else {
                    $val = is_scalar($val) ? trim((string) $val) : '';
                }
                if (mb_strlen($val) > 20000) {
                    $val = mb_substr($val, 0, 20000);
                }
                $current[$name] = $val;
            }
            if ($field->is_required && ! in_array($type, ['checkbox', 'switch'], true)
                && trim((string) ($current[$name] ?? '')) === '') {
                return cms_ui('field_required', ['name' => $field->label]);
            }
        }

        return $current;
    }
}
