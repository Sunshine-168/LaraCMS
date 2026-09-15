<?php

namespace App\Http\Controllers\Web;

use App\Cms\CmsViewContext;
use App\Http\Controllers\Controller;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Models\Cms\Entry;
use App\Models\Cms\Tag;
use App\Services\Cms\CommentService;
use App\Services\Cms\FormSubmitService;
use App\Services\Cms\SiteFrontService;
use App\Services\Cms\TagAutoLinkService;
use App\Support\Cdn;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteController extends Controller
{
    public function __construct(
        private readonly SiteFrontService $site,
        private readonly CmsViewContext $context,
        private readonly TagAutoLinkService $tagAutoLink,
        private readonly CommentService $comments,
        private readonly FormSubmitService $forms,
    ) {}

    /** 首页 */
    public function index(): View
    {
        $site = $this->site->bootSite();
        $this->context->setSeo($site['title'], $site['keyword'], $site['description']);

        return view($this->site->themeView('index.index'), compact('site'));
    }

    /** 搜索（仅 is_searchable 模型；可按模型/栏目过滤） */
    public function search(Request $request): View
    {
        $site = $this->site->bootSite();
        $q = trim((string) $request->query('q', ''));
        $modelId = $request->integer('model_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $this->context->setSeo('搜索 '.$q.' - '.$site['title'], '', '');

        $searchModels = ContentModel::query()
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);
        $searchCategories = Category::query()
            ->where('status', 'published')
            ->when($modelId, fn ($qb) => $qb->where('model_id', $modelId))
            ->whereHas('modelDef', fn ($m) => $m->where('is_searchable', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $results = collect();
        if ($q !== '') {
            $locale = Locale::current();
            $query = Entry::query()->with(['category', 'modelDef'])->published()->visibleToGuests()
                ->whereHas('modelDef', function ($m) use ($modelId) {
                    $m->where('is_searchable', true);
                    if ($modelId) {
                        $m->where('id', $modelId);
                    }
                })
                ->where(function ($inner) use ($q, $locale) {
                    $inner->where('search_text', 'like', "%{$q}%")
                        ->orWhere("title->{$locale}", 'like', "%{$q}%")
                        ->orWhere('title->'.Locale::default(), 'like', "%{$q}%")
                        ->orWhere("excerpt->{$locale}", 'like', "%{$q}%");
                });
            if ($categoryId) {
                $query->inCategories([$categoryId]);
            }
            $results = $query
                ->orderByRaw('CASE WHEN search_text LIKE ? THEN 0 WHEN title LIKE ? THEN 1 ELSE 2 END', ["%{$q}%", "%{$q}%"])
                ->orderByDesc('published_at')
                ->paginate(12)
                ->withQueryString();
            $this->context->setPaginator($results);
        }

        return view($this->site->themeView('search'), [
            'site' => $site,
            'q' => $q,
            'modelId' => $modelId,
            'categoryId' => $categoryId,
            'searchModels' => $searchModels,
            'searchCategories' => $searchCategories,
            'results' => $results,
        ]);
    }

    /** 栏目：hub / list / single / link */
    public function category(string $slug): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $category = $this->site->categoryBySlug($slug);
        abort_unless($category, 404);

        return $this->renderCategory($site, $category);
    }

    /** 文档详情 */
    public function show(string $catSlug, string $slug): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $category = $this->site->categoryBySlug($catSlug);
        abort_unless($category, 404);
        $entry = $this->site->entryBySlug($category, $slug);
        abort_unless($entry, 404);

        return $this->renderEntry($site, $category, $entry);
    }

    /** ID 伪静态：/e/{entry} */
    public function showById(Entry $entry): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        abort_unless($entry->isLive(), 404);
        $entry->loadMissing(['category', 'modelDef']);
        $category = $entry->category;
        abort_unless($category, 404);

        return $this->renderEntry($site, $category, $entry);
    }

    /** /c/{category} */
    public function categoryById(int $category): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $cat = Category::query()->active()->with('modelDef')->findOrFail($category);

        return $this->renderCategory($site, $cat);
    }

    /** /c/{category}/{slug} */
    public function showByCatId(int $category, string $slug): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $cat = Category::query()->active()->with('modelDef')->findOrFail($category);
        $entry = $this->site->entryBySlug($cat, $slug);
        abort_unless($entry, 404);

        return $this->renderEntry($site, $cat, $entry);
    }

    /** /{model}/{cat}/{slug} */
    public function showByModel(string $modelSlug, string $catSlug, string $slug): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $category = $this->site->categoryBySlug($catSlug);
        abort_unless($category && $category->modelDef?->slug === $modelSlug, 404);
        $entry = $this->site->entryBySlug($category, $slug);
        abort_unless($entry, 404);

        return $this->renderEntry($site, $category, $entry);
    }

    /** @param  array<string, mixed>  $site */
    protected function renderCategory(array $site, Category $category): View|RedirectResponse
    {
        if ($category->type === 'link' && ($category->getAttributes()['url'] ?? null)) {
            return redirect()->away($category->getAttributes()['url']);
        }

        $this->context->setCategory($category);
        $this->context->setSeo(
            $category->t('meta_title') ?: $category->t('title').' - '.$site['title'],
            '',
            $category->t('meta_description') ?: ''
        );

        if ($category->type === 'hub') {
            return view($this->site->resolveCategoryTpl($category), compact('site', 'category'));
        }

        if ($category->type === 'single' || $category->modelDef?->kind === 'single') {
            $entry = $this->site->singleEntry($category);
            abort_unless($entry, 404);

            return $this->renderEntry($site, $category, $entry);
        }

        return view($this->site->resolveListTpl($category), compact('site', 'category'));
    }

    /** 文档预览（草稿/未到点发布，token 有效期内） */
    public function preview(string $token): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $entry = Entry::query()->with(['category', 'modelDef', 'tags'])
            ->where('preview_token', $token)
            ->where(function ($q) {
                $q->whereNull('preview_expires_at')->orWhere('preview_expires_at', '>', now());
            })
            ->firstOrFail();
        $category = $entry->category;
        abort_unless($category, 404);

        return $this->renderEntry($site, $category, $entry, preview: true);
    }

    protected function renderEntry(array $site, $category, Entry $entry, bool $preview = false): View|RedirectResponse
    {
        if (! $preview) {
            if ($entry->isMemberGated()) {
                $blocked = $this->memberAccessOrRedirect(
                    request(),
                    'view_member_content',
                    '该内容需会员登录后查看',
                    '当前会员组无权查看此内容'
                );
                if ($blocked) {
                    return $blocked;
                }
            }
            $entry->increment('views');
        }

        $this->context->setPreview($preview);
        $entry->setAttribute('linked_content', Cdn::rewriteHtml($this->tagAutoLink->linkify($entry->t('content'))));
        $this->context->setCategory($category);
        $this->context->setArticle($entry);
        $this->context->setSeo(
            ($preview ? '[预览] ' : '').($entry->t('meta_title') ?: $entry->t('title').' - '.$site['title']),
            '',
            $entry->t('meta_description') ?: ($entry->t('excerpt') ?: '')
        );

        return view($this->site->resolveShowTpl($category, $entry), [
            'site' => $site,
            'category' => $category,
            'entry' => $entry,
            'article' => $entry,
            'isPreview' => $preview,
            'commentsEnabled' => $preview ? false : $this->comments->enabled(),
            'commentsGuest' => $this->comments->guestsAllowed(),
            'commentTree' => $preview ? collect() : $this->comments->approvedTreeForEntry($entry),
        ]);
    }

    /** 下载附件并计次（支持会员门槛） */
    public function download(Request $request, Entry $entry): StreamedResponse|RedirectResponse
    {
        abort_unless($entry->isLive(), 404);

        if ($entry->isMemberGated()) {
            $blocked = $this->memberAccessOrRedirect(
                $request,
                'download',
                '该资源需会员登录后下载',
                '当前会员组无权下载'
            );
            if ($blocked) {
                return $blocked;
            }
        }

        $field = (string) $request->query('field', '');
        $path = $field
            ? $entry->attr($field)
            : ($entry->attr('file') ?: $entry->attr('package'));
        abort_unless($path, 404);

        $entry->increment('downloads_count');

        if (str_starts_with((string) $path, 'http://') || str_starts_with((string) $path, 'https://')) {
            return redirect()->away($path);
        }

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path);
    }

    /** 外链点击计次后跳转 */
    public function go(Request $request, Entry $entry): RedirectResponse
    {
        abort_unless($entry->isLive(), 404);
        if ($entry->isMemberGated()) {
            $blocked = $this->memberAccessOrRedirect(
                $request,
                'view_member_content',
                '该外链需会员登录后访问',
                '当前会员组无权访问此外链'
            );
            if ($blocked) {
                return $blocked;
            }
        }
        $url = $entry->attr('url') ?: $entry->attr('source_url');
        abort_unless($url, 404);
        $entry->increment('clicks');

        return redirect()->away($url);
    }

    /** 标签聚合页 */
    public function tag(string $slug): View
    {
        $site = $this->site->bootSite();
        $locale = Locale::current();
        $tag = Tag::query()->get()->first(function (Tag $t) use ($slug, $locale) {
            return ($t->slug[$locale] ?? null) === $slug
                || ($t->slug[Locale::default()] ?? null) === $slug;
        });
        abort_unless($tag, 404);

        $this->context->setSeo($tag->t('title').' - '.$site['title'], '', '');
        $entries = $tag->entries()->with(['category', 'modelDef'])->published()
            ->visibleToGuests()
            ->orderByDesc('published_at')->paginate(12);

        return $this->site->renderThemePage('tag', compact('site', 'tag', 'entries'));
    }

    /** RSS */
    public function rss(): Response
    {
        $site = $this->site->bootSite();
        $entries = Entry::query()->with(['category', 'modelDef'])->published()
            ->visibleToGuests()
            ->orderByDesc('published_at')->limit(30)->get();
        $xml = view('themes.default.rss', compact('site', 'entries'))->render();

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /** 表单提交（含验证码，按字段定义校验） */
    public function submitForm(Request $request, string $slug): RedirectResponse
    {
        $result = $this->forms->submit($slug, $request, Auth::guard('member')->user());
        if (! $result['ok']) {
            if ($result['msg'] === '表单不存在') {
                abort(404);
            }

            return back()->with('error', $result['msg'])->withInput();
        }

        return back()->with('status', $result['msg']);
    }

    /** sitemap */
    public function sitemap(): Response
    {
        $this->site->bootSite();
        $urls = $this->site->sitemapUrls();
        $xml = view($this->site->themeView('sitemap'), compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $this->site->bootSite();
        $closed = (bool) (int) \App\Models\Cms\Setting::getValue('site_closed', '0');
        $sitemap = rtrim((string) config('app.url'), '/').'/sitemap.xml';
        $lines = ['User-agent: *'];
        if ($closed) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /install';
            $lines[] = 'Disallow: /member';
            $lines[] = 'Disallow: /preview';
        }
        $lines[] = 'Sitemap: '.$sitemap;
        $extra = trim((string) \App\Models\Cms\Setting::getValue('robots_extra', ''));
        if ($extra !== '' && ! $closed) {
            foreach (preg_split('/\R/', $extra) ?: [] as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }
        $lines[] = '';

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    protected function memberAccessOrRedirect(Request $request, string $permission, string $loginMsg, string $permMsg): ?RedirectResponse
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return redirect()->to(cms_route('member.login'))
                ->with('error', $loginMsg)
                ->with('url.intended', $request->fullUrl());
        }
        if (! $member->canMember($permission)) {
            return redirect()->to(cms_route('member.profile'))->with('error', $permMsg);
        }

        return null;
    }
}
