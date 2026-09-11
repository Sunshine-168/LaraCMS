<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cms\Entry;
use App\Services\Cms\MemberContributeService;
use App\Services\Cms\SiteFrontService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberEntryController extends Controller
{
    public function __construct(
        private readonly SiteFrontService $site,
        private readonly MemberContributeService $contribute,
    ) {}

    public function index(): View|RedirectResponse
    {
        $gate = $this->guardMember();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $member = Auth::guard('member')->user();
        $models = $this->contribute->modelsFor($member);
        $entries = $member->contributedEntries()->with(['category', 'modelDef'])
            ->orderByDesc('id')
            ->paginate(12);

        return $this->site->renderThemePage('member.entries', [
            'member' => $member,
            'models' => $models,
            'entries' => $entries,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $gate = $this->guardMember();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $member = Auth::guard('member')->user();
        $models = $this->contribute->modelsFor($member);
        if ($models->isEmpty()) {
            return redirect()->route('member.profile')->with('error', '当前没有开放投稿的模型');
        }
        $model = $models->firstWhere('id', $request->integer('model_id')) ?? $models->first();
        $model->load(['fields.categories']);

        return $this->site->renderThemePage('member.entry-form', [
            'member' => $member,
            'models' => $models,
            'model' => $model,
            'categories' => $this->contribute->categoriesFor($model),
            'entry' => new Entry(['model_id' => $model->id, 'status' => 'draft']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $gate = $this->guardMember();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $submit = $request->input('action') === 'submit';
        $result = $this->contribute->save(Auth::guard('member')->user(), null, $request->all(), $submit);
        if (! $result['ok']) {
            return back()->withErrors(['title' => $result['msg']])->withInput();
        }

        return redirect()->route('member.entries.edit', $result['data'])->with('status', $result['msg']);
    }

    public function edit(Entry $entry): View|RedirectResponse
    {
        $gate = $this->guardMember();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $member = Auth::guard('member')->user();
        if ($msg = $this->contribute->assertOwnedEditable($member, $entry)) {
            return redirect()->route('member.entries.index')->with('error', $msg);
        }
        $models = $this->contribute->modelsFor($member);
        $model = $models->firstWhere('id', $entry->model_id) ?? $entry->modelDef;
        if (! $model) {
            return redirect()->route('member.entries.index')->with('error', '模型不存在');
        }
        $model->load(['fields.categories']);

        return $this->site->renderThemePage('member.entry-form', [
            'member' => $member,
            'models' => $models,
            'model' => $model,
            'categories' => $this->contribute->categoriesFor($model),
            'entry' => $entry,
        ]);
    }

    public function update(Request $request, Entry $entry): RedirectResponse
    {
        $gate = $this->guardMember();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $submit = $request->input('action') === 'submit';
        $result = $this->contribute->save(Auth::guard('member')->user(), $entry, $request->all(), $submit);
        if (! $result['ok']) {
            return back()->withErrors(['title' => $result['msg']])->withInput();
        }

        return redirect()->route('member.entries.edit', $entry)->with('status', $result['msg']);
    }

    private function guardMember(): RedirectResponse|true
    {
        $member = Auth::guard('member')->user();
        if (! $member) {
            return redirect()->route('member.login');
        }
        if (! $member->canMember('contribute')) {
            return redirect()->route('member.profile')->with('error', '当前会员组无权投稿');
        }

        return true;
    }
}
