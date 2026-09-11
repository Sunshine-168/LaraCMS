<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Post;
use App\Services\Admin\CategoryService;
use App\Services\Admin\PostService;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
        private readonly CategoryService $categoryService,
    ) {
    }

    /** 文章列表 */
    public function index(): View
    {
        return view('admin.posts.index', [
            'posts' => $this->postService->paginate(),
        ]);
    }

    /** 新建文章 */
    public function create(): View
    {
        return view('admin.posts.form', [
            'post' => new Post(['status' => 'draft']),
            'categories' => $this->categoryService->all(),
            'locales' => Locale::codes(),
        ]);
    }

    /** 保存新建文章 */
    public function store(Request $request): RedirectResponse
    {
        $result = $this->postService->save(null, $request->all());

        return redirect()->route('admin.posts.index')->with('status', $result['msg']);
    }

    /** 编辑文章 */
    public function edit(Post $post): View
    {
        return view('admin.posts.form', [
            'post' => $post,
            'categories' => $this->categoryService->all(),
            'locales' => Locale::codes(),
        ]);
    }

    /** 更新文章 */
    public function update(Request $request, Post $post): RedirectResponse
    {
        $result = $this->postService->save($post, $request->all());

        return redirect()->route('admin.posts.index')->with('status', $result['msg']);
    }

    /** 删除文章 */
    public function destroy(Post $post): RedirectResponse
    {
        $result = $this->postService->delete($post);

        return redirect()->route('admin.posts.index')->with('status', $result['msg']);
    }
}
