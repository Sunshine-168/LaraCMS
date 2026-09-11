<?php

namespace App\Services\Cms\Collect;

use App\Models\Cms\Collector;
use App\Models\Cms\CollectorLog;
use App\Models\Cms\Entry;
use App\Services\Cms\HtmlCacheService;
use App\Support\CurrentSite;
use App\Support\Locale;
use App\Support\Result;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CollectorService
{
    public function __construct(private readonly HtmlCacheService $htmlCache) {}

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
                'html' => $this->fetchHtml($collector),
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
        $this->assertPublicHttpUrl($collector->source_url);
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

    /**
     * 列表页 CSS 选择器 + 可选详情页正文（只跟同源链接，不翻页、不爬全站）。
     *
     * @return list<array{guid:string,title:string,link:string,summary:string,content:string,published:?string,cover:?string}>
     */
    protected function fetchHtml(Collector $collector): array
    {
        $this->assertPublicHttpUrl($collector->source_url);
        $opts = $collector->options ?? [];
        $itemSel = trim((string) ($opts['item_selector'] ?? ''));
        if ($itemSel === '') {
            throw new \RuntimeException('HTML 采集需要填写 item_selector');
        }

        $listHtml = $this->httpGet($collector->source_url);
        $xpath = $this->htmlXPath($listHtml);
        $nodes = $xpath->query(CssSelector::toXPath($itemSel, false));
        if ($nodes === false || $nodes->length === 0) {
            throw new \RuntimeException('列表选择器未匹配到条目：'.$itemSel);
        }

        $linkSel = trim((string) ($opts['link_selector'] ?? 'a'));
        $titleSel = trim((string) ($opts['title_selector'] ?? ''));
        $summarySel = trim((string) ($opts['summary_selector'] ?? ''));
        $coverSel = trim((string) ($opts['cover_selector'] ?? ''));
        $detailSel = trim((string) ($opts['detail_content_selector'] ?? ''));
        $baseHost = $this->hostOf($collector->source_url);

        $items = [];
        foreach ($nodes as $node) {
            if (! $node instanceof \DOMNode) {
                continue;
            }
            $href = $this->firstAttr($xpath, $node, $linkSel !== '' ? $linkSel : 'a', 'href');
            $link = $href ? $this->absoluteUrl($collector->source_url, $href) : '';
            $title = $titleSel !== ''
                ? $this->firstText($xpath, $node, $titleSel)
                : $this->firstText($xpath, $node, $linkSel !== '' ? $linkSel : 'a');
            if ($title === '' && $node instanceof \DOMElement) {
                $title = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
            }
            $title = Str::limit($title, 180, '');
            if ($title === '') {
                continue;
            }
            $summary = $summarySel !== '' ? $this->firstText($xpath, $node, $summarySel) : '';
            $coverHref = $coverSel !== '' ? $this->firstAttr($xpath, $node, $coverSel, 'src') : null;
            $cover = $coverHref ? $this->absoluteUrl($collector->source_url, $coverHref) : $this->guessCover($node instanceof \DOMElement ? ($node->ownerDocument?->saveHTML($node) ?: '') : '');
            $content = $summary;
            if ($detailSel !== '' && $link !== '' && $this->hostOf($link) === $baseHost) {
                try {
                    $this->assertPublicHttpUrl($link);
                    $detail = $this->httpGet($link, 15);
                    $dx = $this->htmlXPath($detail);
                    $contentNode = $dx->query(CssSelector::toXPath($detailSel, false));
                    if ($contentNode && $contentNode->length > 0) {
                        $content = $this->innerHtml($contentNode->item(0));
                    }
                } catch (\Throwable) {
                    // 详情失败仍用摘要入库
                }
            }
            $max = max(1, min(30, (int) $collector->limit_items));
            $guid = $link !== '' ? $link : md5($title);
            $items[] = [
                'guid' => $guid,
                'title' => $title,
                'link' => $link,
                'summary' => $summary,
                'content' => $content,
                'published' => null,
                'cover' => $cover,
            ];
            if (count($items) >= $max) {
                break;
            }
        }

        return $items;
    }

    protected function httpGet(string $url, int $timeout = 25): string
    {
        $response = Http::timeout($timeout)
            ->withHeaders(['User-Agent' => 'CMS-Collector/1.0'])
            ->get($url);
        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status().' '.$url);
        }
        $body = $response->body();
        if (strlen($body) > 1_500_000) {
            $body = substr($body, 0, 1_500_000);
        }

        return $body;
    }

    protected function htmlXPath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument;
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return new \DOMXPath($dom);
    }

    protected function firstText(\DOMXPath $xpath, \DOMNode $context, string $selector): string
    {
        $nodes = $xpath->query(CssSelector::toXPath($selector, true), $context);
        if (! $nodes || $nodes->length === 0) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', $nodes->item(0)?->textContent ?? '') ?? '');
    }

    protected function firstAttr(\DOMXPath $xpath, \DOMNode $context, string $selector, string $attr): ?string
    {
        $nodes = $xpath->query(CssSelector::toXPath($selector, true), $context);
        if (! $nodes || $nodes->length === 0) {
            return null;
        }
        $node = $nodes->item(0);
        if ($node instanceof \DOMElement && $node->hasAttribute($attr)) {
            return trim($node->getAttribute($attr));
        }
        if ($context instanceof \DOMElement && strtolower($context->nodeName) === strtolower($selector) && $context->hasAttribute($attr)) {
            return trim($context->getAttribute($attr));
        }

        return null;
    }

    protected function innerHtml(?\DOMNode $node): string
    {
        if (! $node || ! $node->ownerDocument) {
            return '';
        }
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return trim($html);
    }

    protected function absoluteUrl(string $base, string $href): string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:')) {
            return '';
        }
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $origin = $scheme.'://'.$host.$port;
        if (str_starts_with($href, '//')) {
            return $scheme.':'.$href;
        }
        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }
        $dir = rtrim(str_replace('\\', '/', dirname($parts['path'] ?? '/')), '/');

        return $origin.($dir === '' ? '' : $dir).'/'.$href;
    }

    protected function hostOf(string $url): string
    {
        return strtolower((string) parse_url($url, PHP_URL_HOST));
    }

    protected function assertPublicHttpUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('仅允许 http/https 源地址');
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw new \RuntimeException('不允许内网地址');
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ok = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($ok === false) {
                throw new \RuntimeException('不允许内网地址');
            }
        }
    }

    /** @return list<array{guid:string,title:string,link:string,summary:string,content:string,published:?string,cover:?string}> */
    protected function fetchJson(Collector $collector): array
    {
        $this->assertPublicHttpUrl($collector->source_url);
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
                $publishedAt = Carbon::parse($item['published']);
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
