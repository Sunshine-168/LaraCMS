<?php

namespace App\Http\Controllers;

use App\Cms\CmsViewContext;
use App\Models\Cms\Archive;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Services\Cms\SiteService;
use App\Services\Cms\Theme\ThemeResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CmsFrontendController extends Controller
{
    public function __construct(
        private readonly SiteService $siteService,
        private readonly ThemeResolver $themeResolver,
        private readonly CmsViewContext $viewContext,
    ) {
    }

    public function index(): View
    {
        try {
            $site = $this->siteService->all();
        } catch (QueryException) {
            abort(500, 'CMS 数据表未准备好，请先配置 Laravel 数据库并导入旧项目数据。');
        }

        $this->themeResolver->registerViewNamespace();
        $this->viewContext
            ->setSite($site)
            ->setCategory(null)
            ->setArticle(null)
            ->setSeo(
                (string) ($site['home_title'] ?? $site['title'] ?? 'CMS'),
                (string) ($site['keyword'] ?? ''),
                (string) ($site['description'] ?? ''),
            );

        return view($this->themeResolver->resolveExisting($this->themeResolver->homeView()), [
            'site' => $site,
            'category' => null,
            'article' => null,
            'pageTitle' => $this->viewContext->pageTitle(),
            'seoKeywords' => $this->viewContext->seoKeywords(),
            'seoDescription' => $this->viewContext->seoDescription(),
        ]);
    }

    public function list(string $catname): View|RedirectResponse
    {
        [$site, $category, $model] = $this->resolveCategoryContext($catname);

        if ($category->type === 'link' && $category->model_id) {
            $article = Archive::query()
                ->with(['category', 'modelDef'])
                ->published()
                ->where('category_id', $category->id)
                ->orderByDesc('weigh')
                ->orderByDesc('id')
                ->first();

            if ($article) {
                return $this->renderArticleView($site, $article, $category);
            }
        }

        $pageTitle = str_replace(
            ['$name', '$page', '$site'],
            [$category->seo_title ?: $category->title, (string) request()->integer('page', 1), $site['title'] ?? 'CMS'],
            $site['category_format'] ?? '$name - $site'
        );

        $this->themeResolver->registerViewNamespace();
        $this->viewContext
            ->setSite($site)
            ->setCategory($category)
            ->setArticle(null)
            ->setSeo(
                $pageTitle,
                (string) ($category->seo_keywords ?? ''),
                (string) ($category->seo_desc ?? ''),
            );

        $view = $this->themeResolver->resolveExisting($this->themeResolver->categoryView($category));

        return view($view, [
            'site' => $site,
            'category' => $category,
            'model' => $model,
            'article' => null,
            'pageTitle' => $this->viewContext->pageTitle(),
            'seoKeywords' => $this->viewContext->seoKeywords(),
            'seoDescription' => $this->viewContext->seoDescription(),
        ]);
    }

    public function show(string $catname, string $id): View
    {
        [$site, $category] = $this->resolveCategoryContext($catname);

        $article = Archive::query()
            ->with(['category', 'modelDef'])
            ->published()
            ->where(function ($query) use ($id): void {
                if (ctype_digit($id)) {
                    $query->where('id', (int) $id);
                } else {
                    $query->where('diyname', $id);
                }
            })
            ->where(function ($query) use ($category): void {
                $query
                    ->where('category_id', $category->id)
                    ->orWhereRaw('find_in_set(?, category_ids)', [(string) $category->id]);
            })
            ->first();

        if (! $article) {
            throw new NotFoundHttpException('内容不存在');
        }

        return $this->renderArticleView($site, $article, $article->category ?: $category);
    }

    private function renderArticleView(array $site, Archive $article, Category $category): View
    {
        $article->increment('views');

        $extra = $article->extra();
        $content = html_entity_decode((string) ($extra['content'] ?? ''), ENT_QUOTES, 'UTF-8');
        $pageTitle = str_replace(
            ['$title', '$name', '$site'],
            [$article->seotitle ?: $article->title, $category->title, $site['title'] ?? 'CMS'],
            $site['content_format'] ?? '$title - $name - $site'
        );

        $this->themeResolver->registerViewNamespace();
        $this->viewContext
            ->setSite($site)
            ->setCategory($category)
            ->setArticle($article)
            ->setSeo(
                $pageTitle,
                (string) ($article->keywords ?: ($category->seo_keywords ?? '')),
                (string) ($article->description ?: ($category->seo_desc ?? '')),
            );

        $view = $this->themeResolver->resolveExisting($this->themeResolver->showView($article, $category));

        return view($view, [
            'site' => $site,
            'category' => $category,
            'article' => $article,
            'content' => $content,
            'extra' => $extra,
            'pageTitle' => $this->viewContext->pageTitle(),
            'seoKeywords' => $this->viewContext->seoKeywords(),
            'seoDescription' => $this->viewContext->seoDescription(),
        ]);
    }

    private function resolveCategoryContext(string $catname): array
    {
        try {
            $site = $this->siteService->all();

            $category = Category::query()
                ->with('modelDef')
                ->active()
                ->where('name', $catname)
                ->first();
        } catch (QueryException) {
            abort(500, 'CMS 数据表未准备好，请先配置 Laravel 数据库并导入旧项目数据。');
        }

        if (! $category) {
            throw new NotFoundHttpException('栏目不存在');
        }

        $model = $category->modelDef;

        if (! $model instanceof ContentModel && $category->type !== 'link') {
            throw new NotFoundHttpException('栏目模型不存在');
        }

        return [$site, $category, $model];
    }
}
