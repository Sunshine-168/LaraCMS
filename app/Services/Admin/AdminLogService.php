<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\AdminLog;
use App\Models\Cms\Category;
use App\Models\Cms\CmsFlag;
use App\Models\Cms\CmsForm;
use App\Models\Cms\CmsLocale;
use App\Models\Cms\Collector;
use App\Models\Cms\Comment;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Media;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Models\Cms\Menu;
use App\Models\Cms\Recommend;
use App\Models\Cms\Redirect;
use App\Models\Cms\Site;
use App\Models\Cms\Tag;
use App\Support\AdminLogAction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminLogService
{
    /**
     * @return array{q:string,mine:bool,from:string,to:string}
     */
    public function filters(Request $request): array
    {
        $from = $this->date($request->query('from'));
        $to = $this->date($request->query('to'));
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'mine' => $request->query('mine') === '1',
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @param  array{q:string,mine:bool,from:string,to:string}  $filters
     */
    public function paginate(array $filters, int $perPage = 40): LengthAwarePaginator
    {
        $query = AdminLog::query()->orderByDesc('id');
        if ($filters['q'] !== '') {
            $like = '%'.$filters['q'].'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('admin_name', 'like', $like)
                    ->orWhere('path', 'like', $like)
                    ->orWhere('detail', 'like', $like);
            });
        }
        if ($filters['mine']) {
            $query->where('admin_id', auth('admin')->id());
        }
        if ($filters['from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $logs = $query->paginate($perPage)->withQueryString();
        $this->hydrate($logs);

        return $logs;
    }

    /** @param  iterable<int, AdminLog>  $logs */
    public function hydrate(iterable $logs): void
    {
        $grouped = [];
        foreach ($logs as $log) {
            $target = AdminLogAction::target((string) $log->path);
            $id = $target['id'] ?? null;
            $resource = $target['resource'] ?? '';
            $log->setAttribute('target_id', $id);
            $log->setAttribute('target_resource', $resource);
            $log->setAttribute('target_name', null);
            $log->setAttribute('target_href', null);
            $log->setAttribute('subject', $this->quotedSubject($log->detail));
            if ($resource !== '' && $id) {
                $grouped[$resource][$id][] = $log;
            }
        }

        foreach ($grouped as $resource => $byId) {
            $names = $this->namesFor($resource, array_keys($byId));
            foreach ($byId as $id => $items) {
                $name = $names[$id] ?? null;
                $href = $name ? AdminLogAction::editUrl($resource, $id) : null;
                foreach ($items as $log) {
                    $log->setAttribute('target_name', $name);
                    $log->setAttribute('target_href', $href);
                    if (! $log->subject && $name) {
                        $log->setAttribute('subject', $name);
                    }
                }
            }
        }
    }

    private function quotedSubject(?string $detail): ?string
    {
        $detail = trim((string) $detail);
        if ($detail === '' || str_starts_with($detail, '选了')) {
            return null;
        }

        return $detail;
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, string>
     */
    private function namesFor(string $resource, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $pick = function ($query, callable $label) use ($ids): array {
            $out = [];
            foreach ($query->whereIn('id', $ids)->get() as $row) {
                $name = trim((string) $label($row));
                if ($name !== '') {
                    $out[(int) $row->id] = $name;
                }
            }

            return $out;
        };

        return match ($resource) {
            'tags' => $pick(Tag::query(), fn ($m) => $m->t('title')),
            'entries' => $pick(Entry::query()->withTrashed(), fn ($m) => $m->t('title')),
            'categories' => $pick(Category::query(), fn ($m) => $m->t('title')),
            'members' => $pick(Member::query(), fn ($m) => $m->name),
            'admin-users' => $pick(AdminUser::query(), fn ($m) => $m->name),
            'sites' => $pick(Site::query(), fn ($m) => $m->name),
            'locales' => $pick(CmsLocale::query(), fn ($m) => $m->label ?: $m->native),
            'redirects' => $pick(Redirect::query(), fn ($m) => $m->source_path),
            'forms' => $pick(CmsForm::query(), fn ($m) => $m->name),
            'comments' => $pick(Comment::query()->withTrashed(), fn ($m) => $m->author_name ?: Str::limit(strip_tags((string) $m->content), 24)),
            'collectors' => $pick(Collector::query(), fn ($m) => $m->name),
            'menus' => $pick(Menu::query(), fn ($m) => $m->name),
            'flags' => $pick(CmsFlag::query(), fn ($m) => $m->title),
            'recommends' => $pick(Recommend::query(), fn ($m) => $m->title ?: $m->name),
            'models' => $pick(ContentModel::query(), fn ($m) => $m->name),
            'roles' => $pick(AdminRole::query(), fn ($m) => $m->name),
            'member-groups' => $pick(MemberGroup::query(), fn ($m) => $m->name),
            'media' => $pick(Media::query(), fn ($m) => $m->filename),
            default => [],
        };
    }

    private function date(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        return $value;
    }
}
