<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\Entry;
use Illuminate\Support\HtmlString;

class PrevNextTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /** @param  array<string, mixed>  $options */
    public function render(string $direction, array $options = []): HtmlString
    {
        $article = $this->context->article();
        if (! $article || ! $article->category_id) {
            return new HtmlString('');
        }

        $neighbor = $this->findNeighbor($article, $direction === 'prev');
        if (! $neighbor) {
            $msg = e((string) ($options['msg'] ?? cms_ui('no_more')));

            return new HtmlString('<span class="cms-'.$direction.'">'.$msg.'</span>');
        }

        $text = e($neighbor->t((string) ($options['field'] ?? 'title')));
        $url = e($neighbor->url);

        return new HtmlString('<a class="cms-'.$direction.'" href="'.$url.'">'.$text.'</a>');
    }

    private function findNeighbor(Entry $article, bool $prev): ?Entry
    {
        $query = Entry::query()->published()
            ->where('category_id', $article->category_id)
            ->where('model_id', $article->model_id);

        if ($prev) {
            return $query->where('id', '<', $article->id)->orderByDesc('id')->first();
        }

        return $query->where('id', '>', $article->id)->orderBy('id')->first();
    }
}
