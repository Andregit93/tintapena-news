<?php

namespace App\Http\Controllers;

use App\Models\Article;

class ArticleController extends Controller
{
    public function show(string $slug)
    {
        $article = Article::published()
            ->where('slug', $slug)
            ->with(['author', 'category', 'region', 'featuredMedia', 'tags'])
            ->firstOrFail();

        $relatedArticles = Article::published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->with(['category', 'region', 'featuredMedia'])
            ->limit(4)
            ->get();

        app(\App\Actions\Articles\RecordArticleView::class)->execute($article);

        return view('articles.show', compact('article', 'relatedArticles'));
    }
}
