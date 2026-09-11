<?php

namespace App\Cms;

use App\Models\Cms\Category;
use App\Models\Cms\Entry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class CmsViewContext
{
    protected array $site = [];

    protected ?Category $category = null;

    protected ?Entry $article = null;

    protected string $pageTitle = '';

    protected string $seoKeywords = '';

    protected string $seoDescription = '';

    protected ?LengthAwarePaginator $paginator = null;

    protected bool $isPreview = false;

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

    public function setArticle(?Entry $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function article(): ?Entry
    {
        return $this->article;
    }

    public function setPreview(bool $preview): self
    {
        $this->isPreview = $preview;

        return $this;
    }

    public function isPreview(): bool
    {
        return $this->isPreview;
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
            : (string) ($this->site['title'] ?? config('app.name'));
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

        $theme = config('cms.theme', 'default');
        $view = $view ?: "themes.{$theme}.partials.paginate";

        if (! view()->exists($view)) {
            foreach (['cms.partials.paginate', 'themes.enterprise.partials.paginate'] as $fallback) {
                if (view()->exists($fallback)) {
                    $view = $fallback;
                    break;
                }
            }
        }

        if (! view()->exists($view)) {
            return new HtmlString($this->paginator->links()->toHtml());
        }

        return new HtmlString(view($view, ['paginator' => $this->paginator])->render());
    }
}
