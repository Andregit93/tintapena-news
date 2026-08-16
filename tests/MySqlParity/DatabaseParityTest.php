<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates article with correct mysql types and enum casting', function () {
    $category = Category::factory()->create();
    $article = Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Draft,
    ]);

    $article->refresh();

    expect($article->status)->toBe(ArticleStatus::Draft);
});

it('created_at date filter respects Asia/Jakarta boundaries on MySQL', function () {
    // August 15 2026, 20:00 UTC is August 16 2026, 03:00 WIB
    $utcInstant = Carbon::create(2026, 8, 15, 20, 0, 0, 'UTC');
    $articleInWibDay = Article::factory()->create([
        'created_at' => $utcInstant,
    ]);

    // August 16 2026, 20:00 UTC is August 17 2026, 03:00 WIB
    $utcInstantNextDay = Carbon::create(2026, 8, 16, 20, 0, 0, 'UTC');
    $articleInNextWibDay = Article::factory()->create([
        'created_at' => $utcInstantNextDay,
    ]);

    // We can test the Livewire component directly, but we mainly want to ensure
    // the query builder logic behaves correctly on MySQL.
    $fromUtc = Carbon::createFromFormat('Y-m-d', '2026-08-16', 'Asia/Jakarta')->startOfDay()->utc();
    $untilUtc = Carbon::createFromFormat('Y-m-d', '2026-08-16', 'Asia/Jakarta')->endOfDay()->utc();

    $results = Article::where('created_at', '>=', $fromUtc)
        ->where('created_at', '<=', $untilUtc)
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($articleInWibDay->id);
});

it('tests media foreign key constraint behavior', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create(['uploaded_by' => $user->id]);
    $category = Category::factory()->create();
    $article = Article::factory()->create([
        'category_id' => $category->id,
        'featured_media_id' => $media->id,
    ]);

    // Media shouldn't be deleteable if we use restrict, but we used nullOnDelete()
    // Let's verify nullOnDelete actually works on MySQL
    $media->delete();

    $article->refresh();
    expect($article->featured_media_id)->toBeNull();
});

it('persists timestamps down to the second accurately on mysql', function () {
    $now = now()->startOfSecond();
    $article = Article::factory()->create([
        'scheduled_at' => $now,
    ]);

    $article->refresh();
    expect($article->scheduled_at->timestamp)->toBe($now->timestamp);
});

it('tests article_tag uniqueness constraint', function () {
    $article = Article::factory()->create();
    $tag = Tag::factory()->create();
    $tag2 = Tag::factory()->create();

    // First attachment should succeed
    $article->tags()->attach($tag->id);
    expect($article->tags()->count())->toBe(1);

    // Different tag should succeed
    $article->tags()->attach($tag2->id);
    expect($article->tags()->count())->toBe(2);

    // Duplicate attachment of same tag should throw QueryException
    try {
        $article->tags()->attach($tag->id);
        $this->fail('Duplicate tag attachment should have thrown a QueryException');
    } catch (QueryException $e) {
        expect($e->getCode())->toBe('23000'); // SQLSTATE[23000]: Integrity constraint violation
    }
});

it('Article::published() resolves correctly under MySQL', function () {
    $category = Category::factory()->create();

    // A. Published + published_at in the past => INCLUDED
    $publishedPast = Article::factory()->published()->create([
        'category_id' => $category->id,
        'published_at' => now()->subDays(1),
    ]);

    // B. Published + published_at exactly now => INCLUDED
    $publishedNow = clone $publishedPast; // just for reference
    $publishedNow = Article::factory()->published()->create([
        'category_id' => $category->id,
        'published_at' => now(),
    ]);

    // C. Published + published_at in the future => EXCLUDED
    $publishedFuture = Article::factory()->published()->create([
        'category_id' => $category->id,
        'published_at' => now()->addDays(1),
    ]);

    // D. Draft => EXCLUDED
    $draft = Article::factory()->draft()->create([
        'category_id' => $category->id,
    ]);

    // E. Scheduled => EXCLUDED
    $scheduled = Article::factory()->scheduled()->create([
        'category_id' => $category->id,
    ]);

    // F. Archived => EXCLUDED
    $archived = Article::factory()->archived()->create([
        'category_id' => $category->id,
    ]);

    $results = Article::published()->get();

    expect($results->pluck('id')->toArray())->toHaveCount(2)
        ->toContain($publishedPast->id)
        ->toContain($publishedNow->id)
        ->not->toContain($publishedFuture->id)
        ->not->toContain($draft->id)
        ->not->toContain($scheduled->id)
        ->not->toContain($archived->id);
});

it('Popular: works under MySQL with proper aggregation and exact boundaries', function () {
    $now = Carbon::create(2026, 8, 16, 12, 0, 0);
    Carbon::setTestNow($now);

    // E. ranking based on period views, not views_count
    $articleHighLifetime = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $now->copy()->subDays(2),
        'views_count' => 10000,
        'title' => 'High Lifetime',
    ]);
    // 0 period views -> excluded or low rank if not excluded (PopularNewsController excludes 0 views)

    $articleIncluded = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $now->copy()->subDays(1),
        'views_count' => 5,
        'title' => 'High Period',
    ]);

    // B. multiple period stat rows are aggregated
    // A. Article::published() + article_view_stats SUM works on MySQL
    // C. future period_start row is excluded
    // D. 24-hour lower boundary works

    // valid stat 1
    $stat1 = new ArticleViewStat;
    $stat1->article_id = $articleIncluded->id;
    $stat1->period_start = $now->copy()->subHours(2);
    $stat1->views_count = 10;
    $stat1->save();

    // valid stat 2 (boundary)
    $stat2 = new ArticleViewStat;
    $stat2->article_id = $articleIncluded->id;
    $stat2->period_start = $now->copy()->subHours(24);
    $stat2->views_count = 15;
    $stat2->save();

    // invalid stat (past boundary)
    $stat3 = new ArticleViewStat;
    $stat3->article_id = $articleIncluded->id;
    $stat3->period_start = $now->copy()->subHours(25);
    $stat3->views_count = 100;
    $stat3->save();

    // invalid stat (future)
    $stat4 = new ArticleViewStat;
    $stat4->article_id = $articleIncluded->id;
    $stat4->period_start = $now->copy()->addHour();
    $stat4->views_count = 100;
    $stat4->save();

    $from = $now->copy()->subHours(24);

    $results = Article::published()
        ->whereHas('viewStats', function ($query) use ($from, $now) {
            $query->where('period_start', '>=', $from)
                ->where('period_start', '<=', $now)
                ->where('views_count', '>', 0);
        })
        ->withSum(['viewStats as period_views' => function ($query) use ($from, $now) {
            $query->where('period_start', '>=', $from)
                ->where('period_start', '<=', $now);
        }], 'views_count')
        ->orderByDesc('period_views')
        ->get();

    // 10 + 15 = 25 views
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($articleIncluded->id);
    expect((int) $results->first()->period_views)->toBe(25);

    Carbon::setTestNow();
});
