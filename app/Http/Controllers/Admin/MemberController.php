<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Member;
use App\Models\Cms\MemberGroup;
use App\Services\Admin\MemberService;
use App\Support\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $memberService) {}

    /** 会员列表 */
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $groupKey = (string) $request->query('group', '');
        $ungrouped = $groupKey === 'none';
        $groupId = (! $ungrouped && $groupKey !== '') ? (int) $groupKey : null;
        $inactiveOnly = $request->query('off') === '1';

        return view('admin.members.index', [
            'members' => $this->memberService->paginate(20, $keyword, $groupId, $ungrouped, $inactiveOnly),
            'groups' => MemberGroup::query()->orderBy('sort')->orderBy('id')->get(),
            'q' => $keyword,
            'groupKey' => $groupKey,
            'inactiveOnly' => $inactiveOnly,
        ]);
    }

    /** 新建会员 */
    public function create(): View
    {
        $defaultGroup = MemberGroup::query()->where('is_active', true)->orderBy('sort')->orderBy('id')->first();

        return view('admin.members.form', $this->formData(new Member([
            'is_active' => true,
            'group_id' => $defaultGroup?->id,
        ])));
    }

    /** @return array<string, mixed> */
    protected function formData(Member $member): array
    {
        return [
            'member' => $member,
            'groups' => MemberGroup::query()->orderBy('sort')->orderBy('id')->get(),
        ];
    }

    /** 保存会员 */
    public function store(Request $request): RedirectResponse
    {
        $this->validateMember($request);
        $result = $this->memberService->save(null, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.members.index')->with('status', $result['msg']);
    }

    /** 编辑会员 */
    public function edit(Member $member): View
    {
        $member->loadCount(['contributedEntries', 'comments']);

        return view('admin.members.form', $this->formData($member));
    }

    /** 更新会员 */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $this->validateMember($request, $member);
        $result = $this->memberService->save($member, $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.members.index')->with('status', $result['msg']);
    }

    /** 删除会员 */
    public function destroy(Request $request, Member $member): RedirectResponse
    {
        $result = $this->memberService->delete($member);

        return redirect()->route('admin.members.index', $this->keepFilters($request))->with('status', $result['msg']);
    }

    public function toggle(Member $member): RedirectResponse
    {
        $result = $this->memberService->toggle($member);

        return back()->with('status', $result['msg']);
    }

    protected function validateMember(Request $request, ?Member $member = null): void
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => [
                'required', 'email',
                Rule::unique('cms_members', 'email')
                    ->where(fn ($q) => $q->where('site_id', CurrentSite::idOrDefault()))
                    ->ignore($member?->id),
            ],
            'password' => ($member?->exists ? 'nullable' : 'required').'|string|min:8',
            'phone' => 'nullable|string|max:32',
            'bio' => 'nullable|string|max:500',
            'group_id' => 'nullable|exists:cms_member_groups,id',
        ]);
    }

    /** @return array<string, string> */
    protected function keepFilters(Request $request): array
    {
        return array_filter([
            'q' => $request->input('keep_q'),
            'group' => $request->input('keep_group'),
            'off' => $request->input('keep_off'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
