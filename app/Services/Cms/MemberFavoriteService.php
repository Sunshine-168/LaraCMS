<?php

namespace App\Services\Cms;

use App\Models\Cms\Entry;
use App\Models\Cms\Member;
use App\Models\Cms\MemberFavorite;
use App\Support\Result;

class MemberFavoriteService
{
    public function toggle(Member $member, Entry $entry): array
    {
        if (! $entry->isLive()) {
            return Result::fail(cms_ui('content_missing'));
        }

        $existing = MemberFavorite::query()
            ->where('member_id', $member->id)
            ->where('entry_id', $entry->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return Result::ok(['favorited' => false, 'entry_id' => $entry->id], cms_ui('unfavorited'));
        }

        MemberFavorite::query()->create([
            'member_id' => $member->id,
            'entry_id' => $entry->id,
        ]);

        return Result::ok(['favorited' => true, 'entry_id' => $entry->id], cms_ui('favorited'));
    }
}
