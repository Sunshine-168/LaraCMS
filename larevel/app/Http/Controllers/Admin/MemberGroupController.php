<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\MemberGroup;
use App\Services\Admin\MemberGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberGroupController extends Controller
{
    public function __construct(private readonly MemberGroupService $groups)
    {
    }

    public function index(): View
    {
        return view('admin.member-groups.index', [
            'groups' => $this->groups->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.member-groups.form', [
            'group' => new MemberGroup(['is_active' => true, 'sort' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'nullable|string|max:80|unique:cms_member_groups,slug',
        ]);
        $result = $this->groups->save(null, $request->all());

        return redirect()->route('admin.member-groups.index')->with('status', $result['msg']);
    }

    public function edit(MemberGroup $member_group): View
    {
        return view('admin.member-groups.form', ['group' => $member_group]);
    }

    public function update(Request $request, MemberGroup $member_group): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'nullable|string|max:80|unique:cms_member_groups,slug,'.$member_group->id,
        ]);
        $result = $this->groups->save($member_group, $request->all());

        return redirect()->route('admin.member-groups.index')->with('status', $result['msg']);
    }

    public function destroy(MemberGroup $member_group): RedirectResponse
    {
        $result = $this->groups->delete($member_group);

        return redirect()->route('admin.member-groups.index')->with('status', $result['msg']);
    }
}
