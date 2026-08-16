<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class PopularNewsController extends Controller
{
    /**
     * Display a listing of popular articles based on period.
     */
    public function index(Request $request)
    {
        $period = $request->query('periode', '24jam');
        
        if (!in_array($period, ['24jam', '7hari'])) {
            $period = '24jam';
        }

        $now = now();
        $from = $period === '7hari' ? $now->copy()->subDays(7) : $now->copy()->subHours(24);

        $articles = Article::published()
            ->whereHas('viewStats', function ($query) use ($from, $now) {
                $query->where('period_start', '>=', $from)
                      ->where('period_start', '<=', $now);
            })
            ->withSum(['viewStats as period_views' => function ($query) use ($from, $now) {
                $query->where('period_start', '>=', $from)
                      ->where('period_start', '<=', $now);
            }], 'views_count')
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('period_views')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('articles.popular', [
            'articles' => $articles,
            'currentPeriod' => $period,
        ]);
    }
}
