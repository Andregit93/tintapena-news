<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Page;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(): Response
    {
        $articles = Article::published()->select('slug', 'published_at')->get();
        $pages = Page::published()->select('slug', 'updated_at')->get();

        return response()->view('sitemap.index', [
            'articles' => $articles,
            'pages' => $pages,
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
