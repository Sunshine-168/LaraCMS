<?php

namespace Tests\Feature;

use App\Models\Cms\Category;
use App\Models\Cms\Collector;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Site;
use App\Services\Cms\Collect\CollectorService;
use App\Services\Cms\Collect\CssSelector;
use App\Support\CurrentSite;
use Database\Seeders\CmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CollectorHtmlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsSeeder::class);
        CurrentSite::set(Site::default());
        Http::preventStrayRequests();
    }

    public function test_css_selector_to_xpath(): void
    {
        $this->assertSame(
            '//article[contains(concat(\' \', normalize-space(@class), \' \'), \' post \')]',
            CssSelector::toXPath('article.post')
        );
        $this->assertSame('.//h2//a', CssSelector::toXPath('h2 a', true));
        $this->assertSame('//*[@id="list"]', CssSelector::toXPath('#list'));
    }

    public function test_html_list_and_detail_creates_entries_and_dedupes(): void
    {
        Http::fake([
            'https://example.com/blog' => Http::response(
                '<html><body>'.
                '<article class="post"><h2><a href="/p/one">First Post</a></h2><p class="excerpt">Sum one</p></article>'.
                '<article class="post"><h2><a href="/p/two">Second Post</a></h2><p class="excerpt">Sum two</p></article>'.
                '</body></html>',
                200
            ),
            'https://example.com/p/one' => Http::response('<html><article class="body">Hello <strong>world</strong></article>', 200),
            'https://example.com/p/two' => Http::response('<html><article class="body">Second body</article>', 200),
        ]);

        $collector = $this->makeCollector('html', 'https://example.com/blog', [
            'item_selector' => 'article.post',
            'link_selector' => 'h2 a',
            'title_selector' => 'h2 a',
            'summary_selector' => '.excerpt',
            'detail_content_selector' => 'article.body',
        ]);

        $svc = app(CollectorService::class);
        $first = $svc->run($collector);
        $this->assertTrue($first['ok'], $first['msg'] ?? '');
        $this->assertSame(2, $first['data']['created']);

        $one = Entry::query()->where('title->zh_cn', 'First Post')->first();
        $this->assertNotNull($one);
        $this->assertSame('', $one->translation('title', 'en'));
        $this->assertStringContainsString('Hello', (string) $one->t('content'));
        $this->assertSame('https://example.com/p/one', $one->attr('source_url'));

        $second = $svc->run($collector->fresh());
        $this->assertTrue($second['ok']);
        $this->assertSame(0, $second['data']['created']);
        $this->assertSame(2, $second['data']['skipped']);
    }

    public function test_json_guid_and_cover_keys(): void
    {
        Http::fake([
            'https://example.com/api/posts' => Http::response([
                'data' => [
                    [
                        'uuid' => 'u-1',
                        'headline' => 'JSON Headline',
                        'href' => 'https://example.com/j/1',
                        'body' => 'JSON body',
                        'thumb' => 'https://example.com/t.jpg',
                    ],
                ],
            ], 200),
        ]);

        $collector = $this->makeCollector('json', 'https://example.com/api/posts', [
            'list_path' => 'data',
            'title_key' => 'headline',
            'link_key' => 'href',
            'content_key' => 'body',
            'guid_key' => 'uuid',
            'cover_key' => 'thumb',
        ]);

        $result = app(CollectorService::class)->run($collector);
        $this->assertTrue($result['ok'], $result['msg'] ?? '');
        $entry = Entry::query()->where('title->zh_cn', 'JSON Headline')->first();
        $this->assertNotNull($entry);
        $this->assertSame('https://example.com/t.jpg', $entry->cover);
        $this->assertSame('c'.$collector->id.':u-1', $entry->source_guid);
    }

    public function test_private_source_url_is_rejected(): void
    {
        $collector = $this->makeCollector('rss', 'http://127.0.0.1/feed.xml', []);
        $result = app(CollectorService::class)->run($collector);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('内网', $result['msg']);
    }

    public function test_html_paginates_with_page_query(): void
    {
        Http::fake([
            'https://example.com/blog' => Http::response(
                '<html><body>'.
                '<article class="post"><h2><a href="/p/one">First Post</a></h2></article>'.
                '</body></html>',
                200
            ),
            'https://example.com/blog?page=2' => Http::response(
                '<html><body>'.
                '<article class="post"><h2><a href="/p/two">Second Post</a></h2></article>'.
                '</body></html>',
                200
            ),
            'https://example.com/p/one' => Http::response('<html><article class="body">Hello</article>', 200),
            'https://example.com/p/two' => Http::response('<html><article class="body">World</article>', 200),
        ]);

        $collector = $this->makeCollector('html', 'https://example.com/blog', [
            'item_selector' => 'article.post',
            'link_selector' => 'h2 a',
            'title_selector' => 'h2 a',
            'detail_content_selector' => 'article.body',
            'page_count' => 2,
        ]);

        $result = app(CollectorService::class)->run($collector);
        $this->assertTrue($result['ok'], $result['msg'] ?? '');
        $this->assertSame(2, $result['data']['created']);
        $this->assertNotNull(Entry::query()->where('title->zh_cn', 'First Post')->first());
        $this->assertNotNull(Entry::query()->where('title->zh_cn', 'Second Post')->first());
    }

    public function test_html_follows_next_page_link(): void
    {
        Http::fake([
            'https://example.com/news' => Http::response(
                '<html><body>'.
                '<li class="item"><a href="/n/a">Alpha</a></li>'.
                '<a class="next" href="/news?p=2">下一页</a>'.
                '</body></html>',
                200
            ),
            'https://example.com/news?p=2' => Http::response(
                '<html><body>'.
                '<li class="item"><a href="/n/b">Beta</a></li>'.
                '</body></html>',
                200
            ),
        ]);

        $collector = $this->makeCollector('html', 'https://example.com/news', [
            'item_selector' => 'li.item',
            'link_selector' => 'a',
            'page_count' => 2,
            'next_selector' => 'a.next',
        ]);

        $result = app(CollectorService::class)->run($collector);
        $this->assertTrue($result['ok'], $result['msg'] ?? '');
        $this->assertSame(2, $result['data']['created']);
        $this->assertNotNull(Entry::query()->where('title->zh_cn', 'Alpha')->first());
        $this->assertNotNull(Entry::query()->where('title->zh_cn', 'Beta')->first());
    }

    public function test_html_page_url_template(): void
    {
        Http::fake([
            'https://example.com/list_1.html' => Http::response(
                '<html><body><article class="post"><h2><a href="/p/one">One</a></h2></article></body></html>',
                200
            ),
            'https://example.com/list_2.html' => Http::response(
                '<html><body><article class="post"><h2><a href="/p/two">Two</a></h2></article></body></html>',
                200
            ),
        ]);

        $collector = $this->makeCollector('html', 'https://example.com/list_1.html', [
            'item_selector' => 'article.post',
            'link_selector' => 'h2 a',
            'page_count' => 2,
            'page_url' => 'https://example.com/list_{page}.html',
        ]);

        $result = app(CollectorService::class)->run($collector);
        $this->assertTrue($result['ok'], $result['msg'] ?? '');
        $this->assertSame(2, $result['data']['created']);
    }

    /** @param  array<string, mixed>  $options */
    protected function makeCollector(string $type, string $url, array $options): Collector
    {
        $model = ContentModel::query()->where('slug', 'article')->first();
        $cat = Category::query()->where('slug->zh_cn', 'blog')->first();
        $this->assertNotNull($model);
        $this->assertNotNull($cat);

        return Collector::query()->create([
            'name' => 'Test '.$type,
            'type' => $type,
            'source_url' => $url,
            'model_id' => $model->id,
            'category_id' => $cat->id,
            'interval_minutes' => 60,
            'limit_items' => 10,
            'is_active' => true,
            'publish_immediately' => true,
            'options' => $options,
        ]);
    }
}
