<?php

namespace App\Cms;

use App\Models\Cms\Archive;
use App\Models\Cms\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class CmsViewContext
{
    protected array $site = [];

    protected ?Category $category = null;

    protected ?Archive $article = null;

    protected string $pageTitle = '';

    protected string $seoKeywords = '';

    protected string $seoDescription = '';

    protected ?LengthAwarePaginator $paginator = null;

    public function setSite(array $site): self
    {
        $this->site = $site;

        return $this;
    }

    public function site(): array
    {
        return $this->site;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function category(): ?Category
    {
        return $this->category;
    }

    public function setArticle(?Archive $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function article(): ?Archive
    {
        return $this->article;
    }

    public function setSeo(string $title, string $keywords = '', string $description = ''): self
    {
        $this->pageTitle = $title;
        $this->seoKeywords = $keywords;
        $this->seoDescription = $description;

        return $this;
    }

    public function pageTitle(): string
    {
        return $this->pageTitle !== ''
            ? $this->pageTitle
            : (string) ($this->site['title'] ?? 'CMS');
    }

    public function seoKeywords(): string
    {
        return $this->seoKeywords;
    }

    public function seoDescription(): string
    {
        return $this->seoDescription;
    }

    public function setPaginator(?LengthAwarePaginator $paginator): self
    {
        $this->paginator = $paginator;

        return $this;
    }

    public function paginator(): ?LengthAwarePaginator
    {
        return $this->paginator;
    }

    public function renderPaginate(?string $view = null): Htmlable
    {
        if (! $this->paginator) {
            return new HtmlString('');
        }

        $namespace = config('cms.view_namespace', 'cms_theme');
        $view = $view ?: "{$namespace}::partials.paginate";

        if (! view()->exists($view)) {
            return new HtmlString($this->paginator->links()->toHtml());
        }

        return new HtmlString(view($view, ['paginator' => $this->paginator])->render());
    }
}
