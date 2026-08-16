<?php

namespace App\Http\Controllers;

use App\Models\Article;

class LatestNewsController extends Controller
{
    /**
     * Display a listing of the latest published articles.
     */
    public function index()
    {
        $articles = Article::published()
            ->orderByDesc('published_at')
            ->with(['category', 'region', 'featuredMedia'])
            ->paginate(10);

        return view('articles.latest', [
            'articles' => $articles,
        ]);
    }
}
