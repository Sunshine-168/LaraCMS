<?php

namespace App\Services\Cms;

use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use App\Models\Cms\Setting;
use App\Models\Cms\Tag;
use App\Support\Locale;
use App\Support\Result;
use Illuminate\Support\Collection;

class SitemapService
{
    public function __construct(private readonly HtmlCacheService $htmlCache) {}

    /** @return array{categories:bool,entries:bool,tags:bool,html:bool,entry_limit:int,extra:string} */
    public function options(): array
    {
        return [
            'categories' => $this->flag('sitemap_categories', true),
            'entries' => $this->flag('sitemap_entries', true),
            'tags' => $this->flag('sitemap_tags', true),
            'html' => $this->flag('sitemap_html', true),
            'entry_limit' => $this->entryLimit(),
            'extra' => (string) Setting::getValue('sitemap_extra', ''),
        ];
    }

    public function htmlEnabled(): bool
    {
        return $this->flag('sitemap_html', true);
    }

    public function save(array $input): array
    {
        Setting::setValue('sitemap_categories', ! empty($input['sitemap_categories']) ? '1' : '0');
        Setting::setValue('sitemap_entries', ! empty($input['sitemap_entries']) ? '1' : '0');
        Setting::setValue('sitemap_tags', ! empty($input['sitemap_tags']) ? '1' : '0');
        Setting::setValue('sitemap_html', ! empty($input['sitemap_html']) ? '1' : '0');
        Setting::setValue('sitemap_entry_limit', (string) $this->clampLimit($input['sitemap_entry_limit'] ?? 2000));
        Setting::setValue('sitemap_extra', $this->sanitizeExtra((string) ($input['sitemap_extra'] ?? '')));
        $this->htmlCache->forgetAll('sitemap');

        return Result::ok($this->options(), '站点地图已保存');
    }

    /**
     * @return Collection<int, array{loc:string,lastmod:?string,changefreq:string,priority:string,type:string,title:string}>
     */
    public function items(): Collection
    {
        $opt = $this->options();
        $urls = collect();
        $this->push($urls, cms_front_url(''), $this->homeLastmod(), 'daily', '1.0', 'home', '首页');

        if ($opt['categories']) {
            foreach (Category::query()->active()->orderBy('sort_order')->orderBy('id')->get() as $cat) {
                if ($cat->type === 'link') {
                    continue;
                }
                $this->push($urls, $this->abs($cat->frontUrl()), optional($cat->updated_at)?->toAtomString(), 'weekly', '0.8', 'category', (string) ($cat->t('title') ?: $cat->t('slug') ?: '栏目'));
            }
        }

        if ($opt['entries']) {
            $added = 0;
            $limit = $opt['entry_limit'];
            $query = Entry::query()->published()->visibleToGuests()->with(['category', 'modelDef'])
                ->orderByDesc('updated_at')->orderByDesc('id')
                ->limit(min(10000, $limit + 200));
            foreach ($query->get() as $entry) {
                if ($entry->isMemberGated() || $entry->modelDef?->kind === 'single' || $entry->modelDef?->slug === 'link') {
                    continue;
                }
                $this->push($urls, $this->abs((string) $entry->url), optional($entry->updated_at ?: $entry->published_at)?->toAtomString(), 'weekly', '0.6', 'entry', (string) ($entry->t('title') ?: $entry->url));
                $added++;
                if ($added >= $limit) {
                    break;
                }
            }
        }

        if ($opt['tags']) {
            foreach (Tag::query()->orderBy('id')->whereHas('entries', function ($q) {
                $q->published()->visibleToGuests();
            })->get() as $tag) {
                $slug = (string) $tag->t('slug');
                if ($slug === '') {
                    continue;
                }
                $this->push($urls, cms_front_url('tag/'.$slug), optional($tag->updated_at)?->toAtomString(), 'monthly', '0.4', 'tag', (string) ($tag->t('title') ?: $slug));
            }
        }

        foreach ($this->extraLines($opt['extra']) as $line) {
            $this->push($urls, $line, null, 'monthly', '0.3', 'extra', $line);
        }

        return $urls->unique('loc')->values();
    }

    /** 给 XML 模板：loc / lastmod / changefreq / priority */
    public function xmlItems(): Collection
    {
        return $this->items()->map(fn (array $row) => [
            'loc' => $row['loc'],
            'lastmod' => $row['lastmod'],
            'changefreq' => $row['changefreq'],
            'priority' => $row['priority'],
        ]);
    }

    /** @return array<string, list<array{loc:string,title:string}>> */
    public function htmlGroups(): array
    {
        $groups = [
            'home' => [],
            'category' => [],
            'entry' => [],
            'tag' => [],
            'extra' => [],
        ];
        foreach ($this->items() as $row) {
            $type = $row['type'];
            if (! isset($groups[$type])) {
                $type = 'extra';
            }
            $groups[$type][] = ['loc' => $row['loc'], 'title' => $row['title']];
        }

        return $groups;
    }

    /** @return array{home:int,category:int,entry:int,tag:int,extra:int,total:int} */
    public function counts(?Collection $items = null): array
    {
        $items ??= $this->items();
        $counts = ['home' => 0, 'category' => 0, 'entry' => 0, 'tag' => 0, 'extra' => 0];
        foreach ($items as $row) {
            $type = $row['type'] ?? 'extra';
            if (! isset($counts[$type])) {
                $type = 'extra';
            }
            $counts[$type]++;
        }
        $counts['total'] = $items->count();

        return $counts;
    }

    protected function homeLastmod(): ?string
    {
        $entryAt = Entry::query()->published()->max('updated_at');
        $catAt = Category::query()->active()->max('updated_at');
        $stamp = collect([$entryAt, $catAt])->filter()->max();
        if (! $stamp) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($stamp)->toAtomString();
    }

    protected function flag(string $key, bool $default): bool
    {
        $raw = Setting::getValue($key, $default ? '1' : '0');

        return $raw === null || $raw === '' ? $default : $raw !== '0' && $raw !== 'false';
    }

    protected function entryLimit(): int
    {
        return $this->clampLimit(Setting::getValue('sitemap_entry_limit', '2000'));
    }

    protected function clampLimit(mixed $value): int
    {
        return max(50, min(10000, (int) $value));
    }

    /** robots.txt 里各语言的 Sitemap 行 */
    public function robotsSitemapLines(): array
    {
        $lines = [];
        foreach (Locale::codes() as $code) {
            $lines[] = 'Sitemap: '.cms_front_url('sitemap.xml', $code);
        }

        return array_values(array_unique($lines));
    }

    protected function abs(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '#') {
            return '';
        }
        if (! preg_match('#^https?://#i', $path)) {
            $path = url($path);
        }
        if ($path === '' || str_contains($path, '?') || str_contains($path, '#')) {
            return $path;
        }
        $trimmed = rtrim($path, '/');

        return $trimmed !== '' && preg_match('#^https?://#i', $trimmed) ? $trimmed : $path;
    }

    /**
     * @param  Collection<int, array{loc:string,lastmod:?string,changefreq:string,priority:string,type:string,title:string}>  $urls
     */
    protected function push(Collection $urls, string $loc, ?string $lastmod, string $changefreq, string $priority, string $type, string $title): void
    {
        $row = $this->row($loc, $lastmod, $changefreq, $priority, $type, $title);
        if ($row) {
            $urls->push($row);
        }
    }

    /**
     * @return array{loc:string,lastmod:?string,changefreq:string,priority:string,type:string,title:string}|null
     */
    protected function row(string $loc, ?string $lastmod, string $changefreq, string $priority, string $type, string $title): ?array
    {
        $loc = $this->abs($loc);
        if ($loc === '') {
            return null;
        }

        return [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'type' => $type,
            'title' => $title,
        ];
    }

    /** @return list<string> */
    protected function extraLines(string $raw): array
    {
        $raw = $this->sanitizeExtra($raw);
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('#^https?://#i', $line)) {
                $out[] = $line;
                continue;
            }
            $path = '/'.ltrim($line, '/');
            $out[] = url($path);
        }

        return $out;
    }

    public function sanitizeExtra(string $raw): string
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '' || str_contains($line, '<') || str_contains($line, '>')) {
                continue;
            }
            if (preg_match('#^[a-z][a-z0-9+\-.]*:#i', $line) && ! preg_match('#^https?://#i', $line)) {
                continue;
            }
            if (! preg_match('#^(https?://|/)#i', $line)) {
                continue;
            }
            $lines[] = mb_substr($line, 0, 200);
            if (count($lines) >= 40) {
                break;
            }
        }

        return implode("\n", $lines);
    }
}
