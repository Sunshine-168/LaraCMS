<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\MemberGroup;
use App\Services\Admin\MemberGroupService;
use App\Support\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberGroupController extends Controller
{
    public function __construct(private readonly MemberGroupService $groups) {}

    public function index(): View
    {
        return view('admin.member-groups.index', [
            'groups' => $this->groups->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.member-groups.form', [
            'group' => new MemberGroup(['is_active' => true, 'permissions' => MemberGroup::permissionKeys()]),
            'usage' => 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validateGroup($request);
        $result = $this->groups->save(null, $request->all());
        $redirect = redirect()->route('admin.member-groups.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
        if ($result['ok'] && ! $request->boolean('from_form')) {
            $redirect->with('group_quick_added', true);
        }

        return $redirect;
    }

    public function edit(MemberGroup $member_group): View
    {
        $member_group->loadCount('members');

        return view('admin.member-groups.form', [
            'group' => $member_group,
            'usage' => (int) $member_group->members_count,
        ]);
    }

    public function update(Request $request, MemberGroup $member_group): RedirectResponse
    {
        $this->validateGroup($request, $member_group);
        $result = $this->groups->save($member_group, $request->all());

        return redirect()->route('admin.member-groups.index')->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function toggle(MemberGroup $member_group): RedirectResponse
    {
        $result = $this->groups->toggle($member_group);

        return redirect()->route('admin.member-groups.index')->with('status', $result['msg']);
    }

    public function move(Request $request, MemberGroup $member_group): RedirectResponse
    {
        $result = $this->groups->move($member_group, (string) $request->input('dir'));

        return redirect()->route('admin.member-groups.index')
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function destroy(MemberGroup $member_group): RedirectResponse
    {
        $result = $this->groups->delete($member_group);

        return redirect()->route('admin.member-groups.index')->with('status', $result['msg']);
    }

    protected function validateGroup(Request $request, ?MemberGroup $group = null): void
    {
        $request->validate([
            'name' => 'required|string|max:80',
            'slug' => [
                'nullable', 'string', 'max:80',
                Rule::unique('cms_member_groups', 'slug')
                    ->where(fn ($q) => $q->where('site_id', CurrentSite::idOrDefault()))
                    ->ignore($group?->id),
            ],
        ]);
    }
}
