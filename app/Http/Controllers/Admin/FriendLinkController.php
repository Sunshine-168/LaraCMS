<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\RecommendItem;
use App\Services\Admin\FriendLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FriendLinkController extends Controller
{
    public function __construct(private readonly FriendLinkService $links) {}

    public function index(): View
    {
        return view('admin.friend-links.index', [
            'links' => $this->links->items(),
            'block' => $this->links->block(),
        ]);
    }

    public function create(): View
    {
        return view('admin.friend-links.form', [
            'link' => new RecommendItem(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->links->add($request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        $go = redirect()->route('admin.friend-links.index')->with('status', $result['msg']);
        if (! $request->boolean('from_form')) {
            $go->with('friend_link_quick_added', true);
        }

        return $go;
    }

    public function edit(RecommendItem $friend_link): View
    {
        return view('admin.friend-links.form', [
            'link' => $this->links->owned($friend_link),
        ]);
    }

    public function update(Request $request, RecommendItem $friend_link): RedirectResponse
    {
        $result = $this->links->save($this->links->owned($friend_link), $request->all());
        if (! $result['ok']) {
            return back()->with('error', $result['msg'])->withInput();
        }

        return redirect()->route('admin.friend-links.index')->with('status', $result['msg']);
    }

    public function destroy(RecommendItem $friend_link): RedirectResponse
    {
        $result = $this->links->delete($this->links->owned($friend_link));

        return redirect()->route('admin.friend-links.index')->with('status', $result['msg']);
    }

    public function toggle(RecommendItem $friend_link): RedirectResponse
    {
        $result = $this->links->toggle($this->links->owned($friend_link));

        return back()->with('status', $result['msg']);
    }
}
