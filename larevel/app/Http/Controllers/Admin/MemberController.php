<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Services\Admin\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $memberService)
    {
    }

    /** 会员列表 */
    public function index(): View
    {
        return view('admin.members.index', [
            'members' => $this->memberService->paginate(),
        ]);
    }

    /** 新建会员 */
    public function create(): View
    {
        return view('admin.members.form', [
            'member' => new Member(['is_active' => true]),
            'groups' => MemberGroup::query()->where('is_active', true)->orderBy('sort')->get(),
        ]);
    }

    /** 保存会员 */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:cms_members,email',
            'password' => 'required|string|min:8',
        ]);
        $result = $this->memberService->save(null, $request->all());

        return redirect()->route('admin.members.index')->with('status', $result['msg']);
    }

    /** 编辑会员 */
    public function edit(Member $member): View
    {
        return view('admin.members.form', [
            'member' => $member,
            'groups' => MemberGroup::query()->where('is_active', true)->orderBy('sort')->get(),
        ]);
    }

    /** 更新会员 */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:cms_members,email,'.$member->id,
            'password' => 'nullable|string|min:8',
        ]);
        $result = $this->memberService->save($member, $request->all());

        return redirect()->route('admin.members.index')->with('status', $result['msg']);
    }

    /** 删除会员 */
    public function destroy(Member $member): RedirectResponse
    {
        $result = $this->memberService->delete($member);

        return redirect()->route('admin.members.index')->with('status', $result['msg']);
    }
}
