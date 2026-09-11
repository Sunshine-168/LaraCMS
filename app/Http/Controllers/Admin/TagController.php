<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Tag;
use App\Services\Admin\TagAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __construct(private readonly TagAdminService $tags) {}

    public function index(): View
    {
        return view('admin.tags.index', [
            'tags' => $this->tags->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tags.form', ['tag' => new Tag]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->tags->save(null, $request->all());

        return redirect()->route('admin.tags.index')->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.form', compact('tag'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $result = $this->tags->save($tag, $request->all());

        return redirect()->route('admin.tags.index')->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $result = $this->tags->delete($tag);

        return redirect()->route('admin.tags.index')->with('status', $result['msg']);
    }
}
