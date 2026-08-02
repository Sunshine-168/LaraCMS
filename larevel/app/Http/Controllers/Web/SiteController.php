<?php

namespace App\Http\Controllers\Web;

use App\Cms\CmsViewContext;
use App\Http\Controllers\Controller;
use App\Models\Cms\CmsForm;
use App\Models\Cms\Entry;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\Tag;
use App\Services\Cms\SiteFrontService;
use App\Services\Cms\TagAutoLinkService;
use App\Support\Captcha;
use App\Support\Locale;
use App\Support\MailConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteController extends Controller
{
    public function __construct(
        private readonly SiteFrontService $site,
        private readonly CmsViewContext $context,
        private readonly TagAutoLinkService $tagAutoLink,
    ) {
    }

    /** 首页 */
    public function index(): View
    {
        $site = $this->site->bootSite();
        $this->context->setSeo($site['title'], $site['keyword'], $site['description']);

        return view($this->site->themeView('index.index'), compact('site'));
    }

    /** 搜索 */
    public function search(Request $request): View
    {
        $site = $this->site->bootSite();
        $q = trim((string) $request->query('q', ''));
        $this->context->setSeo('搜索 '.$q.' - '.$site['title'], '', '');

        $results = collect();
        if ($q !== '') {
            $locale = Locale::current();
            $results = Entry::query()->with(['category', 'modelDef'])->published()
                ->where(function ($query) use ($q, $locale) {
                    $query->where('search_text', 'like', "%{$q}%")
                        ->orWhere("title->{$locale}", 'like', "%{$q}%")
                        ->orWhere('title->'.Locale::default(), 'like', "%{$q}%")
                        ->orWhere("excerpt->{$locale}", 'like', "%{$q}%");
                })
                ->orderByRaw('CASE WHEN search_text LIKE ? THEN 0 WHEN title LIKE ? THEN 1 ELSE 2 END', ["%{$q}%", "%{$q}%"])
                ->orderByDesc('published_at')
                ->paginate(12)
                ->withQueryString();
            $this->context->setPaginator($results);
        }

        return view($this->site->themeView('search'), [
            'site' => $site,
            'q' => $q,
            'results' => $results,
        ]);
    }

    /** 栏目：hub / list / single / link */
    public function category(string $slug): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $category = $this->site->categoryBySlug($slug);
        abort_unless($category, 404);

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
            $this->context->setArticle($entry);

            return view($this->site->resolveShowTpl($category, $entry), [
                'site' => $site,
                'category' => $category,
                'entry' => $entry,
                'article' => $entry,
            ]);
        }

        return view($this->site->resolveListTpl($category), compact('site', 'category'));
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
        abort_unless($entry->status === 'published', 404);
        $entry->loadMissing(['category', 'modelDef']);
        $category = $entry->category;
        abort_unless($category, 404);

        return $this->renderEntry($site, $category, $entry);
    }

    /** /c/{category} */
    public function categoryById(int $category): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $cat = \App\Models\Cms\Category::query()->active()->with('modelDef')->findOrFail($category);
        if ($cat->type === 'link' && ($cat->getAttributes()['url'] ?? null)) {
            return redirect()->away($cat->getAttributes()['url']);
        }
        $this->context->setCategory($cat);
        $this->context->setSeo($cat->t('meta_title') ?: $cat->t('title').' - '.$site['title'], '', $cat->t('meta_description') ?: '');

        return view($this->site->resolveListTpl($cat), ['site' => $site, 'category' => $cat]);
    }

    /** /c/{category}/{slug} */
    public function showByCatId(int $category, string $slug): View|RedirectResponse
    {
        $site = $this->site->bootSite();
        $cat = \App\Models\Cms\Category::query()->active()->with('modelDef')->findOrFail($category);
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
    protected function renderEntry(array $site, $category, Entry $entry): View|RedirectResponse
    {
        $needMemberView = filter_var($entry->attr('member_only', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($entry->attr('require_login', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($entry->attr('islogin', false), FILTER_VALIDATE_BOOLEAN)
            || $entry->hasFlag('member');
        if ($needMemberView) {
            $member = Auth::guard('member')->user();
            if (! $member) {
                return redirect()->route('member.login')
                    ->with('error', '该内容需会员登录后查看')
                    ->with('url.intended', request()->fullUrl());
            }
            if (! $member->canMember('view_member_content')) {
                return redirect()->route('member.profile')->with('error', '当前会员组无权查看此内容');
            }
        }

        $entry->increment('views');
        $entry->setAttribute('linked_content', $this->tagAutoLink->linkify($entry->t('content')));
        $this->context->setCategory($category);
        $this->context->setArticle($entry);
        $this->context->setSeo(
            $entry->t('meta_title') ?: $entry->t('title').' - '.$site['title'],
            '',
            $entry->t('meta_description') ?: ($entry->t('excerpt') ?: '')
        );

        return view($this->site->resolveShowTpl($category, $entry), [
            'site' => $site,
            'category' => $category,
            'entry' => $entry,
            'article' => $entry,
        ]);
    }

    /** 下载附件并计次（支持会员门槛） */
    public function download(Request $request, Entry $entry): StreamedResponse|RedirectResponse
    {
        abort_unless($entry->status === 'published', 404);

        $needLogin = filter_var($entry->attr('require_login', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var($entry->attr('islogin', false), FILTER_VALIDATE_BOOLEAN);
        if ($needLogin) {
            $member = Auth::guard('member')->user();
            if (! $member) {
                return redirect()->route('member.login')
                    ->with('error', '该资源需会员登录后下载')
                    ->with('url.intended', $request->fullUrl());
            }
            if (! $member->canMember('download')) {
                return redirect()->route('member.profile')->with('error', '当前会员组无权下载');
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
    public function go(Entry $entry): RedirectResponse
    {
        abort_unless($entry->status === 'published', 404);
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
            ->orderByDesc('published_at')->paginate(12);

        return view($this->site->themeView('tag'), compact('site', 'tag', 'entries'));
    }

    /** RSS */
    public function rss(): Response
    {
        $site = $this->site->bootSite();
        $entries = Entry::query()->with(['category', 'modelDef'])->published()
            ->orderByDesc('published_at')->limit(30)->get();
        $xml = view('themes.default.rss', compact('site', 'entries'))->render();

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /** 表单提交（含验证码，按字段定义校验） */
    public function submitForm(Request $request, string $slug): RedirectResponse
    {
        $form = CmsForm::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        if (config('cms.form_captcha', true) && ! Captcha::check($request->input('captcha'))) {
            return back()->with('error', '验证码错误')->withInput();
        }

        $rules = [];
        $allowed = [];
        foreach ($form->fields ?? [] as $field) {
            if (! is_array($field) || empty($field['name'])) {
                continue;
            }
            $name = (string) $field['name'];
            $allowed[] = $name;
            $type = $field['type'] ?? 'text';
            $rule = ! empty($field['required']) ? ['required'] : ['nullable'];
            if ($type === 'email') {
                $rule[] = 'email';
            } elseif ($type === 'number') {
                $rule[] = 'numeric';
            } elseif ($type === 'checkbox') {
                $rule[] = 'array';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:5000';
            }
            $rules[$name] = $rule;
        }
        $validated = $rules === []
            ? $request->except(['_token', 'captcha'])
            : $request->validate($rules);

        $data = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $validated)) {
                $data[$key] = $validated[$key];
            }
        }

        $member = Auth::guard('member')->user();
        if ($member && ! $member->canMember('form_submit')) {
            return back()->with('error', '当前会员组无权提交表单');
        }

        FormSubmission::query()->create([
            'form_id' => $form->id,
            'data' => $data,
            'ip' => $request->ip(),
        ]);

        if ($form->notify_email) {
            try {
                MailConfig::applyFromSettings();
                $body = "表单「{$form->name}」有新提交\nIP: {$request->ip()}\n\n";
                foreach ($data as $k => $v) {
                    $body .= $k.': '.(is_array($v) ? implode(', ', $v) : $v)."\n";
                }
                Mail::raw($body, function ($message) use ($form) {
                    $message->to($form->notify_email)->subject('【CMS】新表单留言：'.$form->name);
                });
            } catch (\Throwable) {
                // 邮件失败不影响用户提交
            }
        }

        return back()->with('status', '提交成功，我们会尽快联系您。');
    }

    /** sitemap */
    public function sitemap(): Response
    {
        $this->site->bootSite();
        $urls = $this->site->sitemapUrls();
        $xml = view($this->site->themeView('sitemap'), compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
