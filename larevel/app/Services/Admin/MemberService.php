<?php

namespace App\Services\Admin;

use App\Models\Cms\Member;
use App\Support\Result;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberService
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Member::query()->with('group')->orderByDesc('id')->paginate($perPage);
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
        $member->is_active = ! empty($input['is_active']);
        $member->group_id = ! empty($input['group_id']) ? (int) $input['group_id'] : null;
        if (! empty($input['password'])) {
            $member->password = $input['password'];
        } elseif (! $member->exists) {
            return Result::fail('新建会员必须设置密码');
        }
        $member->save();

        return Result::ok($member, '会员已保存');
    }

    public function delete(Member $member): array
    {
        $member->delete();

        return Result::ok(null, '已删除');
    }
}
