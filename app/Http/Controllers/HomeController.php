<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\HomepageSlot;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(Request $request)
    {
        $now = now();
        $from24h = $now->copy()->subHours(24);

        $slots = HomepageSlot::with([
            'article' => fn($q) => $q->published()->with(['category', 'region', 'featuredMedia'])
        ])->where('is_active', true)->orderBy('sort_order')->get();

        $headlineMain = $slots->firstWhere('slot_key', 'headline_main')?->article;
        $supportingHeadlines = $slots
            ->whereIn('slot_key', ['headline_2', 'headline_3'])
            ->pluck('article')
            ->filter()
            ->values();

        $editorPicks = $slots
            ->whereIn('slot_key', [
                'editor_pick_1',
                'editor_pick_2',
                'editor_pick_3',
                'editor_pick_4',
            ])
            ->pluck('article')
            ->filter()
            ->values();

        $latestArticles = Article::published()
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $popularArticles = Article::published()
            ->withPeriodViews($from24h, $now)
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('period_views')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $regionalArticles = Article::published()
            ->whereNotNull('region_id')
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $politikArticles = Article::published()
            ->whereHas('category', function ($query) {
                $query->whereIn('slug', ['politik', 'pemerintahan']);
            })
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $ekonomiArticles = Article::published()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'ekonomi');
            })
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $hukumArticles = Article::published()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'hukum-kriminal');
            })
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $pariwisataArticles = Article::published()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'pariwisata');
            })
            ->with(['category', 'region', 'featuredMedia'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return view('pages.home', compact(
            'headlineMain',
            'supportingHeadlines',
            'editorPicks',
            'latestArticles',
            'popularArticles',
            'regionalArticles',
            'politikArticles',
            'ekonomiArticles',
            'hukumArticles',
            'pariwisataArticles'
        ));
    }
}
