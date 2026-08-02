<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\Category;
use Illuminate\Support\HtmlString;

class BreadcrumbTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /** @param  array<string, mixed>  $options */
    public function render(array $options = []): HtmlString
    {
        $symbol = e((string) ($options['symbol'] ?? ' / '));
        $class = e((string) ($options['class'] ?? 'breadcrumb'));
        $parts = [];

        $home = e((string) ($this->context->site()['title'] ?? config('app.name')));
        $parts[] = '<a href="/">'.$home.'</a>';

        $category = $this->context->category();
        foreach ($this->ancestors($category) as $cat) {
            $parts[] = '<a href="'.e($cat->frontUrl()).'">'.e($cat->t('title')).'</a>';
        }

        if ($article = $this->context->article()) {
            $parts[] = '<span>'.e($article->t('title')).'</span>';
        }

        return new HtmlString('<nav class="'.$class.'">'.implode($symbol, $parts).'</nav>');
    }

    /** @return list<Category> */
    private function ancestors(?Category $category): array
    {
        if (! $category) {
            return [];
        }
        $chain = [];
        $cursor = $category;
        $guard = 0;
        while ($cursor && $guard < 20) {
            array_unshift($chain, $cursor);
            $cursor = $cursor->parent_id
                ? Category::query()->find((int) $cursor->parent_id)
                : null;
            $guard++;
        }

        return $chain;
    }
}
