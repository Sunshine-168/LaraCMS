<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Cms\FrontendService;
use App\Services\Cms\MenuQueryService;
use App\Services\Cms\SeoService;
use App\Support\Locale;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 旧 Pages/Posts 前台（未注册路由）。现网前台走 SiteController + Entry。
 * 后台入口仅在 CMS_SHOW_LEGACY_ADMIN=true 时出现。
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly FrontendService $frontend,
        private readonly MenuQueryService $menus,
        private readonly SeoService $seo,
    ) {}

    /** 站点首页 */
    public function index(): View
    {
        $page = $this->frontend->homepage();
        abort_unless($page, 404, '请先在后台设置首页');

        return $this->renderTheme('page', [
            'page' => $page,
            'menuItems' => $this->menus->items('header'),
            'seo' => $this->seo->forContent($page),
        ]);
    }

    /** 普通页面 */
    public function page(string $slug): View
    {
        $page = $this->frontend->pageBySlug($slug);
        abort_unless($page, 404);

        return $this->renderTheme('page', [
            'page' => $page,
            'menuItems' => $this->menus->items('header'),
            'seo' => $this->seo->forContent($page),
        ]);
    }

    /** 文章列表 */
    public function posts(): View
    {
        return $this->renderTheme('posts', [
            'posts' => $this->frontend->posts(),
            'menuItems' => $this->menus->items('header'),
            'seo' => $this->seo->forContent(null, '文章 - '.config('app.name')),
        ]);
    }

    /** 文章详情 */
    public function post(string $slug): View
    {
        $post = $this->frontend->postBySlug($slug);
        abort_unless($post, 404);

        return $this->renderTheme('post', [
            'post' => $post,
            'menuItems' => $this->menus->items('header'),
            'seo' => $this->seo->forContent($post),
        ]);
    }

    /** sitemap.xml */
    public function sitemap(): Response
    {
        $urls = $this->seo->sitemapUrls();
        $xml = view('themes.'.config('cms.theme', 'default').'.sitemap', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    protected function renderTheme(string $view, array $data = []): View
    {
        $theme = config('cms.theme', 'default');
        Locale::set(Locale::current());

        return view("themes.{$theme}.{$view}", $data);
    }
}
