<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Cms\SitemapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapService $sitemaps) {}

    public function edit(): View
    {
        $items = $this->sitemaps->items();

        return view('admin.sitemap.edit', [
            'options' => $this->sitemaps->options(),
            'counts' => $this->sitemaps->counts($items),
            'preview' => $items->take(20),
            'total' => $items->count(),
            'xmlUrl' => cms_front_url('sitemap.xml'),
            'htmlUrl' => cms_front_url('sitemap'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $result = $this->sitemaps->save($request->all());

        return redirect()->route('admin.sitemap.edit')->with('status', $result['msg']);
    }
}
