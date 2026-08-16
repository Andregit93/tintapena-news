<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display the specified category and its published articles.
     */
    public function show(Category $category)
    {
        $articles = Article::published()
            ->where('category_id', $category->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->with(['category', 'region', 'featuredMedia'])
            ->paginate(10);

        return view('categories.show', [
            'category' => $category,
            'articles' => $articles,
        ]);
    }
}
