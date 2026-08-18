<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $reserved = [
            'admin',
            'berita',
            'kategori',
            'wilayah',
            'topik',
            'terbaru',
            'terpopuler',
            'cari',
            'kontak',
            'sitemap.xml',
        ];

        if (in_array($slug, $reserved, true)) {
            abort(404);
        }

        $page = Page::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('pages.show', compact('page'));
    }
}
