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

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $unused = $request->query('unused') === '1';

        return view('admin.tags.index', [
            'tags' => $this->tags->paginate(30, $keyword, $unused),
            'q' => $keyword,
            'unused' => $unused,
        ]);
    }

    public function create(): View
    {
        return view('admin.tags.form', ['tag' => new Tag]);
    }

    public function store(Request $request): RedirectResponse
    {
        $result = $this->tags->save(null, $request->all());
        $back = array_filter([
            'q' => $request->input('keep_q'),
            'unused' => $request->input('keep_unused'),
        ], fn ($v) => $v !== null && $v !== '');
        $redirect = redirect()->route('admin.tags.index', $back)
            ->with($result['ok'] ? 'status' : 'error', $result['msg']);
        if ($result['ok'] && empty($request->input('locales')) && trim((string) $request->input('title')) !== '') {
            $redirect->with('tag_quick_added', true);
        }

        return $redirect;
    }

    public function edit(Tag $tag): View
    {
        $tag->loadCount('entries');

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
