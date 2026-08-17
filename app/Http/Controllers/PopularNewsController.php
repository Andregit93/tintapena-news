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
            ->withPeriodViews($from, $now)
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('period_views')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends(['periode' => $period]);

        return view('articles.popular', [
            'articles' => $articles,
            'currentPeriod' => $period,
        ]);
    }
}
