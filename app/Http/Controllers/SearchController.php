<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $rawQuery = $request->query('q');
        $query = is_string($rawQuery) ? trim($rawQuery) : '';
        $rawFilter = $request->query('filter', 'semua');
        $filter = is_string($rawFilter) ? strtolower(trim($rawFilter)) : 'semua';

        // Normalize filter to default if unsupported
        if (! in_array($filter, ['semua', 'berita', 'opini'], true)) {
            $filter = 'semua';
        }

        if ($query === '') {
            // Return early for empty query, no search performed
            return view('search.index', [
                'articles' => collect(), // Empty collection
                'q' => $query,
                'filter' => $filter,
                'hasRunSearch' => false,
            ]);
        }

        // Escape wildcard characters for LIKE
        $safeQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $likePattern = "%{$safeQuery}%";

        $articlesQuery = Article::published()
            ->with(['category', 'region', 'featuredMedia'])
            ->where(function ($q) use ($likePattern) {
                $q->where('title', 'like', $likePattern)
                    ->orWhere('subtitle', 'like', $likePattern)
                    ->orWhere('excerpt', 'like', $likePattern);
            });

        if ($filter === 'opini') {
            $articlesQuery->whereHas('category', function ($q) {
                $q->where('slug', 'opini');
            });
        } elseif ($filter === 'berita') {
            $articlesQuery->whereHas('category', function ($q) {
                $q->where('slug', '!=', 'opini');
            });
        }

        $articles = $articlesQuery
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends(['q' => $query, 'filter' => $filter]);

        return view('search.index', [
            'articles' => $articles,
            'q' => $query,
            'filter' => $filter,
            'hasRunSearch' => true,
        ]);
    }
}
