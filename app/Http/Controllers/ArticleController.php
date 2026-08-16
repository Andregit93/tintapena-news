<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function show(string $slug)
    {
        $article = Article::published()
            ->where('slug', $slug)
            ->with(['author', 'category', 'region', 'featuredMedia', 'tags'])
            ->firstOrFail();

        return view('articles.show', compact('article'));
    }
}
