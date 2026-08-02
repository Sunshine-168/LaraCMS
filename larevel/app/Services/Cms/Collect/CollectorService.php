<?php

namespace App\Services\Cms\Collect;

use App\Models\Cms\Collector;
use App\Models\Cms\CollectorLog;
use App\Models\Cms\Entry;
use App\Services\Cms\HtmlCacheService;
use App\Support\CurrentSite;
use App\Support\Locale;
use App\Support\Result;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CollectorService
{
    public function __construct(private readonly HtmlCacheService $htmlCache)
    {
    }

    /** 运行所有到期采集器 */
    public function runDue(): array
    {
        $ran = 0;
        $created = 0;
        Collector::query()->withoutGlobalScopes()->where('is_active', true)->orderBy('id')->each(function (Collector $c) use (&$ran, &$created) {
            if (! $c->due()) {
                return;
            }
            $result = $this->run($c);
            $ran++;
            $created += (int) ($result['data']['created'] ?? 0);
        });

        return Result::ok(compact('ran', 'created'), "已执行 {$ran} 个采集器，新建 {$created} 条");
    }

    /** 运行单个采集器 */
    public function run(Collector $collector): array
    {
        $siteId = (int) ($collector->site_id ?: CurrentSite::idOrDefault() ?: 0);
        if ($siteId > 0) {
            return CurrentSite::run($siteId, fn () => $this->runInSite($collector));
        }

        return $this->runInSite($collector);
    }

    protected function runInSite(Collector $collector): array
    {
        try {
            $items = match ($collector->type) {
                'json' => $this->fetchJson($collector),
                default => $this->fetchRss($collector),
            };

            $fetched = count($items);
            $created = 0;
            $skipped = 0;
            $limit = max(1, (int) $collector->limit_items);

            foreach (array_slice($items, 0, $limit) as $item) {
                if ($this->upsertEntry($collector, $item)) {
                    $created++;
                } else {
                    $skipped++;
                }
            }

            $status = 'ok';
            $message = "拉取 {$fetched}，新建 {$created}，跳过 {$skipped}";
            $this->finish($collector, $status, $message, $fetched, $created, $skipped);

            if ($created > 0) {
                if ($collector->category_id) {
                    $collector->loadMissing('category');
                    if ($collector->category) {
                        $this->htmlCache->forgetByCategory($collector->category);
                    } else {
                        $this->htmlCache->forgetAll('collector:'.$collector->id);
                    }
                } else {
                    $this->htmlCache->forgetAll('collector:'.$collector->id);
                }
            }

            return Result::ok(compact('fetched', 'created', 'skipped'), $message);
        } catch (\Throwable $e) {
            $this->finish($collector, 'fail', $e->getMessage(), 0, 0, 0);

            return Result::fail('采集失败：'.$e->getMessage());
        }
    }

    /** @return list<array{guid:string,title:string,link:string,summary:string,content:string,published:?string,cover:?string}> */
    protected function fetchRss(Collector $collector): array
    {
        $response = Http::timeout(25)
            ->withHeaders(['User-Agent' => 'CMS-Collector/1.0'])
            ->get($collector->source_url);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status());
        }

        $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
        if (! $xml) {
            throw new \RuntimeException('无法解析 RSS/Atom XML');
        }

        $items = [];
        // RSS 2.0
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $node) {
                $link = trim((string) ($node->link ?? ''));
                $guid = trim((string) ($node->guid ?? $link));
                $items[] = [
                    'guid' => $guid ?: md5($link.(string) $node->title),
                    'title' => trim((string) ($node->title ?? '无标题')),
                    'link' => $link,
                    'summary' => trim(strip_tags((string) ($node->description ?? ''))),
                    'content' => trim((string) ($node->children('content', true)->encoded ?? $node->description ?? '')),
                    'published' => trim((string) ($node->pubDate ?? '')),
                    'cover' => $this->guessCover((string) ($node->description ?? '')),
                ];
            }
        }
        // Atom
        if ($items === [] && isset($xml->entry)) {
            foreach ($xml->entry as $node) {
                $link = '';
                foreach ($node->link ?? [] as $l) {
                    $href = (string) ($l['href'] ?? '');
                    if ($href && ((string) ($l['rel'] ?? 'alternate') === 'alternate' || $link === '')) {
                        $link = $href;
                    }
                }
                $id = trim((string) ($node->id ?? $link));
                $items[] = [
                    'guid' => $id ?: md5($link.(string) $node->title),
                    'title' => trim((string) ($node->title ?? '无标题')),
                    'link' => $link,
                    'summary' => trim(strip_tags((string) ($node->summary ?? ''))),
                    'content' => trim((string) ($node->content ?? $node->summary ?? '')),
                    'published' => trim((string) ($node->updated ?? $node->published ?? '')),
                    'cover' => null,
                ];
            }
        }

        return $items;
    }

    /** @return list<array{guid:string,title:string,link:string,summary:string,content:string,published:?string,cover:?string}> */
    protected function fetchJson(Collector $collector): array
    {
        $response = Http::timeout(25)
            ->withHeaders(['User-Agent' => 'CMS-Collector/1.0', 'Accept' => 'application/json'])
            ->get($collector->source_url);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status());
        }

        $json = $response->json();
        $opts = $collector->options ?? [];
        $listPath = (string) ($opts['list_path'] ?? 'items');
        $list = $listPath === '' || $listPath === '.' ? $json : data_get($json, $listPath, []);
        if (! is_array($list)) {
            throw new \RuntimeException('JSON 列表路径无效：'.$listPath);
        }

        $map = [
            'title' => $opts['title_key'] ?? 'title',
            'link' => $opts['link_key'] ?? 'url',
            'summary' => $opts['summary_key'] ?? 'summary',
            'content' => $opts['content_key'] ?? 'content',
            'guid' => $opts['guid_key'] ?? 'id',
            'published' => $opts['published_key'] ?? 'published_at',
            'cover' => $opts['cover_key'] ?? 'cover',
        ];

        $items = [];
        foreach ($list as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = (string) data_get($row, $map['title'], '');
            $link = (string) data_get($row, $map['link'], '');
            $guid = (string) (data_get($row, $map['guid']) ?: $link ?: md5($title));
            if ($title === '') {
                continue;
            }
            $items[] = [
                'guid' => $guid,
                'title' => $title,
                'link' => $link,
                'summary' => (string) data_get($row, $map['summary'], ''),
                'content' => (string) data_get($row, $map['content'], data_get($row, $map['summary'], '')),
                'published' => (string) data_get($row, $map['published'], ''),
                'cover' => data_get($row, $map['cover']),
            ];
        }

        return $items;
    }

    /** @param  array{guid:string,title:string,link:string,summary:string,content:string,published:?string,cover:?string}  $item */
    protected function upsertEntry(Collector $collector, array $item): bool
    {
        $guid = 'c'.$collector->id.':'.$item['guid'];
        if (Entry::query()->where('source_guid', $guid)->exists()) {
            return false;
        }

        $locale = Locale::default();
        $baseSlug = Str::slug(Str::limit($item['title'], 60, '')) ?: 'item-'.substr(md5($guid), 0, 8);
        $slug = $baseSlug;
        $i = 1;
        while (Entry::query()->where("slug->{$locale}", $slug)->exists()) {
            $slug = $baseSlug.'-'.$i++;
        }

        $publishedAt = now();
        if (! empty($item['published'])) {
            try {
                $publishedAt = \Carbon\Carbon::parse($item['published']);
            } catch (\Throwable) {
            }
        }

        $body = $item['content'] !== '' ? strip_tags($item['content'], '<p><br><a><img><ul><ol><li><strong><em><h2><h3>') : $item['summary'];
        if ($item['link']) {
            $body .= "\n\n来源：".$item['link'];
        }

        Entry::query()->create([
            'category_id' => $collector->category_id,
            'model_id' => $collector->model_id,
            'title' => [$locale => $item['title'], 'en' => $item['title']],
            'slug' => [$locale => $slug, 'en' => $slug],
            'excerpt' => [$locale => Str::limit($item['summary'], 200), 'en' => Str::limit($item['summary'], 200)],
            'content' => [$locale => $body, 'en' => $body],
            'cover' => $item['cover'] ?: null,
            'attrs' => [
                'source_url' => $item['link'],
                'collected' => true,
            ],
            'source_guid' => $guid,
            'collector_id' => $collector->id,
            'status' => $collector->publish_immediately ? 'published' : 'draft',
            'published_at' => $collector->publish_immediately ? $publishedAt : null,
            'sort_order' => 0,
        ]);

        return true;
    }

    protected function finish(
        Collector $collector,
        string $status,
        string $message,
        int $fetched,
        int $created,
        int $skipped,
    ): void {
        $collector->forceFill([
            'last_run_at' => now(),
            'last_status' => $status,
            'last_message' => Str::limit($message, 500),
        ])->save();

        CollectorLog::query()->create([
            'collector_id' => $collector->id,
            'status' => $status,
            'fetched' => $fetched,
            'created' => $created,
            'skipped' => $skipped,
            'message' => Str::limit($message, 1000),
        ]);
    }

    protected function guessCover(string $html): ?string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }

        return null;
    }
}
