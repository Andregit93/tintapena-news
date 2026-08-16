<?php

namespace App\Http\Controllers;

use App\Models\Tag;

class TagController extends Controller
{
    /**
     * Display the specified tag and its published articles.
     */
    public function show(Tag $tag)
    {
        $articles = $tag->articles()
            ->published()
            ->orderByDesc('articles.published_at')
            ->orderByDesc('articles.id')
            ->with(['category', 'region', 'featuredMedia'])
            ->paginate(10);

        return view('tags.show', [
            'tag' => $tag,
            'articles' => $articles,
        ]);
    }
}
