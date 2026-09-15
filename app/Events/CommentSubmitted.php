<?php

namespace App\Events;

use App\Models\Cms\Comment;
use App\Models\Cms\Entry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public Entry $entry,
    ) {}
}
