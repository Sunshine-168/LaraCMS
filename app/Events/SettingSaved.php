<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SettingSaved
{
    use Dispatchable;

    /** @param  list<string>  $keys */
    public function __construct(public array $keys) {}
}
