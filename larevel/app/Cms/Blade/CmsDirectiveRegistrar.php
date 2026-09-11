<?php

namespace App\Cms\Blade;

use App\Cms\CmsViewContext;
use App\Services\Cms\Tags\AdvTag;
use App\Services\Cms\Tags\ArconeTag;
use App\Services\Cms\Tags\BreadcrumbTag;
use App\Services\Cms\Tags\ChannelTag;
use App\Services\Cms\Tags\ContentTag;
use App\Services\Cms\Tags\FileinfoTag;
use App\Services\Cms\Tags\FilterTag;
use App\Services\Cms\Tags\FormatTag;
use App\Services\Cms\Tags\FormTag;
use App\Services\Cms\Tags\MapTag;
use App\Services\Cms\Tags\MenuTag;
use App\Services\Cms\Tags\OrderTag;
use App\Services\Cms\Tags\PrevNextTag;
use App\Services\Cms\Tags\SeoTag;
use App\Services\Cms\Tags\TagListTag;
use Illuminate\Support\Facades\Blade;

class CmsDirectiveRegistrar
{
    public function register(): void
    {
        Blade::directive('cmsSeo', function (): string {
            return '<?php echo app(\\'.SeoTag::class.'::class)->render(); ?>';
        });

        Blade::directive('cmsBreadcrumb', function (?string $expression): string {
            $expression = $this->expressionOrEmptyArray($expression);

            return '<?php echo app(\\'.BreadcrumbTag::class.'::class)->render('.$expression.'); ?>';
        });

        Blade::directive('cmsPaginate', function (?string $expression): string {
            $expression = trim((string) $expression);

            if ($expression === '') {
                return '<?php echo app(\\'.CmsViewContext::class.'::class)->renderPaginate(); ?>';
            }

            return '<?php echo app(\\'.CmsViewContext::class.'::class)->renderPaginate('.$expression.'); ?>';
        });

        Blade::directive('cmsDate', function (?string $expression): string {
            $expression = $this->expressionOrEmptyArray($expression);

            return '<?php echo app(\\'.FormatTag::class.'::class)->date('.$expression.'); ?>';
        });

        Blade::directive('cmsSubstr', function (?string $expression): string {
            $expression = $this->expressionOrEmptyArray($expression);

            return '<?php echo app(\\'.FormatTag::class.'::class)->substr('.$expression.'); ?>';
        });

        Blade::directive('cmsPrev', function (?string $expression): string {
            $expression = $this->expressionOrEmptyArray($expression);

            return '<?php echo app(\\'.PrevNextTag::class.'::class)->render(\'prev\', '.$expression.'); ?>';
        });

        Blade::directive('cmsNext', function (?string $expression): string {
            $expression = $this->expressionOrEmptyArray($expression);

            return '<?php echo app(\\'.PrevNextTag::class.'::class)->render(\'next\', '.$expression.'); ?>';
        });

        Blade::directive('cmsForm', function (?string $expression): string {
            $expression = $this->expressionOrEmptyArray($expression);

            return '<?php echo app(\\'.FormTag::class.'::class)->render('.$expression.'); ?>';
        });

        Blade::directive('cmsMap', function (?string $expression): string {
            $expression = $this->expressionOrEmptyArray($expression);

            return '<?php echo app(\\'.MapTag::class.'::class)->render('.$expression.'); ?>';
        });

        $this->registerLoop('cmsChannel', ChannelTag::class);
        $this->registerLoop('cmsMenu', MenuTag::class);
        $this->registerLoop('cmsContent', ContentTag::class);
        $this->registerLoop('cmsAdv', AdvTag::class);
        $this->registerLoop('cmsArcone', ArconeTag::class);
        $this->registerLoop('cmsFilter', FilterTag::class);
        $this->registerLoop('cmsTaglist', TagListTag::class);
        $this->registerLoop('cmsOrder', OrderTag::class);
        $this->registerLoop('cmsFileinfo', FileinfoTag::class);
        $this->registerLoop('cmsFormFields', FormTag::class);
    }

    /** @param  class-string  $tagClass */
    private function registerLoop(string $name, string $tagClass): void
    {
        Blade::directive($name, function (?string $expression) use ($tagClass): string {
            $expression = $this->expressionOrEmptyArray($expression);

            return '<?php '.
                '$__cmsTagOptions = '.$expression.'; '.
                '$__cmsTagAs = $__cmsTagOptions[\'as\'] ?? \'item\'; '.
                'foreach (app(\\'.$tagClass.'::class)->get($__cmsTagOptions) as $__cmsTagItem): '.
                '$$__cmsTagAs = $__cmsTagItem; '.
                '?>';
        });

        Blade::directive('end'.$name, function (): string {
            return '<?php endforeach; unset($__cmsTagItem, $__cmsTagAs, $__cmsTagOptions); ?>';
        });
    }

    private function expressionOrEmptyArray(?string $expression): string
    {
        $expression = trim((string) $expression);

        return $expression === '' ? '[]' : $expression;
    }
}
