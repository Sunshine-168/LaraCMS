<?php

namespace App\Events;

use App\Models\Cms\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentModerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public string $status,
    ) {}
}
