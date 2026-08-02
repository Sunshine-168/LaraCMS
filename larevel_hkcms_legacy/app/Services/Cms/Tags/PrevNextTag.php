<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use App\Models\Cms\Archive;
use Illuminate\Support\HtmlString;

class PrevNextTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function render(string $direction, array $options = []): HtmlString
    {
        $article = $this->findNeighbor($direction, $options);

        if (! $article) {
            $msg = (string) ($options['msg'] ?? ($direction === 'prev' ? '没有上一篇' : '没有下一篇'));

            return new HtmlString(e($msg));
        }

        $len = isset($options['len']) ? (int) $options['len'] : 0;
        $dot = (string) ($options['dot'] ?? '...');
        $target = (string) ($options['target'] ?? '');
        $field = (string) ($options['field'] ?? 'title');
        $text = (string) ($article->{$field} ?? $article->title);

        if ($len > 0 && mb_strlen($text) > $len) {
            $text = mb_substr($text, 0, $len).$dot;
        }

        $attrs = 'href="'.e($article->url).'"';
        if ($target !== '') {
            $attrs .= ' target="'.e($target).'"';
        }

        return new HtmlString('<a '.$attrs.'>'.e($text).'</a>');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function findNeighbor(string $direction, array $options): ?Archive
    {
        $current = $this->context->article();
        $catid = (int) ($options['catid'] ?? $this->context->category()?->id ?? $current?->category_id ?? 0);
        $id = (int) ($options['id'] ?? $current?->id ?? 0);

        if ($id <= 0) {
            return null;
        }

        $query = Archive::query()
            ->with(['category', 'modelDef'])
            ->published();

        if ($catid > 0) {
            $query->where('category_id', $catid);
        }

        if ($direction === 'prev') {
            return $query
                ->where('id', '<', $id)
                ->orderByDesc('id')
                ->first();
        }

        return $query
            ->where('id', '>', $id)
            ->orderBy('id')
            ->first();
    }
}
