<?php

namespace App\Services\Cms\Tags;

use App\Cms\CmsViewContext;
use Illuminate\Support\HtmlString;

class SeoTag
{
    public function __construct(private readonly CmsViewContext $context)
    {
    }

    public function render(): HtmlString
    {
        $title = e($this->context->pageTitle());
        $keywords = e($this->context->seoKeywords());
        $description = e($this->context->seoDescription());

        return new HtmlString(
            "<title>{$title}</title>\n".
            "<meta name=\"keywords\" content=\"{$keywords}\">\n".
            "<meta name=\"description\" content=\"{$description}\">"
        );
    }
}
