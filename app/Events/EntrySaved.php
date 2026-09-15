<?php

namespace App\Events;

use App\Models\Cms\Entry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** 后台或会员保存一篇内容之后 */
class EntrySaved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Entry $entry,
        public bool $created,
        public bool $publishedNow,
    ) {}
}
