<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** 当前站换成另一套主题之后 */
class ThemeActivated
{
    use Dispatchable;

    public function __construct(
        public string $slug,
        public string $previous = '',
    ) {}
}
