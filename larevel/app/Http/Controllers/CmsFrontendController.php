<?php

namespace App\Http\Controllers;

use App\Models\Cms\Archive;
use App\Models\Cms\Category;
use App\Models\Cms\ContentModel;
use App\Services\Cms\SiteService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CmsFrontendController extends Controller
{
    public function __construct(private readonly SiteService $siteService)
    {
    }

    public function index(): View
    {
        try {
            $site = $this->siteService->all();

            $categories = Category::query()
                ->active()
                ->where('ismenu', 1)
                ->where('parent_id', 0)
                ->orderBy('weigh')
                ->limit(8)
                ->get();

            $articles = Archive::query()
                ->with(['category', 'modelDef'])
                ->published()
                ->orderByDesc('weigh')
                ->orderByDesc('id')
                ->limit(12)
                ->get();
        } catch (QueryException $exception) {
            abort(500, 'CMS 数据表未准备好，请先配置 Laravel 数据库并导入旧项目数据。');
        }

        return view('cms.index', [
            'site' => $site,
            'categories' => $categories,
            'navCategories' => $categories,
            'articles' => $articles,
            'pageTitle' => $site['home_title'] ?? ($site['title'] ?? 'CMS'),
            'seoKeywords' => $site['keyword'] ?? '',
            'seoDescription' => $site['description'] ?? '',
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

        $ids = $this->descendantIds($category)->push($category->id)->all();

        $articles = Archive::query()
            ->with(['category', 'modelDef'])
            ->published()
            ->where(function ($query) use ($ids): void {
                $query->whereIn('category_id', $ids);

                foreach ($ids as $id) {
                    $query->orWhereRaw('find_in_set(?, category_ids)', [(string) $id]);
                }
            })
            ->orderByDesc('weigh')
            ->orderByDesc('id')
            ->paginate((int) ($category->num ?: 10))
            ->withQueryString();

        $pageTitle = str_replace(
            ['$name', '$page', '$site'],
            [$category->seo_title ?: $category->title, (string) $articles->currentPage(), $site['title'] ?? 'CMS'],
            $site['category_format'] ?? '$name - $site'
        );

        return view('cms.list', [
            'site' => $site,
            'navCategories' => $this->navigationCategories(),
            'category' => $category,
            'model' => $model,
            'articles' => $articles,
            'pageTitle' => $pageTitle,
            'seoKeywords' => $category->seo_keywords,
            'seoDescription' => $category->seo_desc,
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

        return view('cms.show', [
            'site' => $site,
            'navCategories' => $this->navigationCategories(),
            'category' => $category,
            'article' => $article,
            'content' => $content,
            'extra' => $extra,
            'pageTitle' => $pageTitle,
            'seoKeywords' => $article->keywords ?: $category->seo_keywords,
            'seoDescription' => $article->description ?: $category->seo_desc,
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
        } catch (QueryException $exception) {
            abort(500, 'CMS 数据表未准备好，请先配置 Laravel 数据库并导入旧项目数据。');
        }

        if (! $category) {
            throw new NotFoundHttpException('栏目不存在');
        }

        $model = $category->modelDef;

        if (! $model instanceof ContentModel) {
            throw new NotFoundHttpException('栏目模型不存在');
        }

        return [$site, $category, $model];
    }

    private function descendantIds(Category $category): Collection
    {
        $all = Category::query()
            ->active()
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        return $this->collectChildren($all, $category->id);
    }

    private function navigationCategories(): Collection
    {
        return Category::query()
            ->active()
            ->where('ismenu', 1)
            ->where('parent_id', 0)
            ->orderBy('weigh')
            ->limit(8)
            ->get();
    }

    private function collectChildren(Collection $grouped, int $parentId): Collection
    {
        $children = collect();

        foreach ($grouped->get($parentId, collect()) as $item) {
            $children->push($item->id);
            $children = $children->merge($this->collectChildren($grouped, $item->id));
        }

        return $children->unique()->values();
    }
}
