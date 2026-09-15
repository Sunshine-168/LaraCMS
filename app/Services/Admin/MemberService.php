<?php

namespace App\Services\Admin;

use App\Models\Cms\Member;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberService
{
    public function paginate(
        int $perPage = 20,
        string $keyword = '',
        ?int $groupId = null,
        bool $ungrouped = false,
        bool $inactiveOnly = false,
    ): LengthAwarePaginator {
        $query = Member::query()->with('group')->withCount(['contributedEntries', 'comments']);
        $keyword = trim($keyword);
        if ($keyword !== '') {
            $like = '%'.addcslashes($keyword, '%_\\').'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }
        if ($ungrouped) {
            $query->whereNull('group_id');
        } elseif ($groupId) {
            $query->where('group_id', $groupId);
        }
        if ($inactiveOnly) {
            $query->where('is_active', false);
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function save(?Member $member, array $input): array
    {
        $member ??= new Member;
        $member->name = $input['name'] ?? $member->name;
        $member->email = $input['email'] ?? $member->email;
        if (array_key_exists('phone', $input)) {
            $member->phone = $input['phone'] !== '' && $input['phone'] !== null ? $input['phone'] : null;
        }
        if (array_key_exists('bio', $input)) {
            $member->bio = $input['bio'] !== '' && $input['bio'] !== null ? $input['bio'] : null;
        }
        $member->is_active = array_key_exists('is_active', $input)
            ? ! empty($input['is_active'])
            : ($member->exists ? (bool) $member->is_active : true);
        $member->group_id = array_key_exists('group_id', $input)
            ? (! empty($input['group_id']) ? (int) $input['group_id'] : null)
            : $member->group_id;
        if (! empty($input['password'])) {
            $member->password = $input['password'];
        } elseif (! $member->exists) {
            return Result::fail('请设置登录密码');
        }
        $member->save();

        return Result::ok($member, '会员已保存');
    }

    public function toggle(Member $member): array
    {
        $member->is_active = ! $member->is_active;
        $member->save();

        return Result::ok(
            $member,
            $member->is_active ? '已启用，可以登录前台' : '已停用，不能再登录前台'
        );
    }

    public function delete(Member $member): array
    {
        $member->delete();

        return Result::ok(null, '已删除');
    }
}
