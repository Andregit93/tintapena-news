<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleViewStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function createStat(Article $article, Carbon $time, int $views)
{
    $stat = new ArticleViewStat();
    $stat->article_id = $article->id;
    $stat->period_start = $time;
    $stat->views_count = $views;
    $stat->save();
    return $stat;
}

it('A. GET /terpopuler returns 200', function () {
    $this->get(route('articles.popular'))->assertStatus(200);
});

it('B. default period is 24jam', function () {
    $response = $this->get(route('articles.popular'));
    $response->assertViewHas('currentPeriod', '24jam');
});

it('C. explicit periode=24jam works', function () {
    $response = $this->get(route('articles.popular', ['periode' => '24jam']));
    $response->assertViewHas('currentPeriod', '24jam');
});

it('D. periode=7hari works', function () {
    $response = $this->get(route('articles.popular', ['periode' => '7hari']));
    $response->assertViewHas('currentPeriod', '7hari');
});

it('E. unsupported periode falls back to 24jam', function () {
    $response = $this->get(route('articles.popular', ['periode' => '1tahun']));
    $response->assertViewHas('currentPeriod', '24jam');
});

it('F,G,H,I,J. Published article with period views appears, others do not', function () {
    $published = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'title' => 'Title Pub']);
    createStat($published, now()->subHours(5), 10);

    $draft = Article::factory()->create(['status' => ArticleStatus::Draft, 'title' => 'Title Draft']);
    createStat($draft, now()->subHours(5), 10);

    $scheduled = Article::factory()->create(['status' => ArticleStatus::Scheduled, 'scheduled_at' => now()->addDay(), 'title' => 'Title Sched']);
    createStat($scheduled, now()->subHours(5), 10);

    $archived = Article::factory()->create(['status' => ArticleStatus::Archived, 'published_at' => now()->subDays(2), 'archived_at' => now(), 'title' => 'Title Arch']);
    createStat($archived, now()->subHours(5), 10);

    $future = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->addDay(), 'title' => 'Title Future']);
    createStat($future, now()->subHours(5), 10);

    $response = $this->get(route('articles.popular', ['periode' => '24jam']));
    $response->assertSee('Title Pub');
    $response->assertDontSee('Title Draft');
    $response->assertDontSee('Title Sched');
    $response->assertDontSee('Title Arch');
    $response->assertDontSee('Title Future');
});

it('K,L,M,N. time boundaries for 24jam and 7hari', function () {
    $article24h = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(10), 'title' => 'Title 24h']);
    createStat($article24h, now()->subHours(12), 10);

    $article3days = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(10), 'title' => 'Title 3days']);
    createStat($article3days, now()->subDays(3), 10);

    $article10days = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(20), 'title' => 'Title 10days']);
    createStat($article10days, now()->subDays(10), 10);

    $articleFuture = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(10), 'title' => 'Title FutureStat']);
    createStat($articleFuture, now()->addHours(2), 10);

    // Test 24jam
    $response24 = $this->get(route('articles.popular', ['periode' => '24jam']));
    $response24->assertSee('Title 24h');
    $response24->assertDontSee('Title 3days'); // K. older than 24h does not count
    $response24->assertDontSee('Title 10days');
    $response24->assertDontSee('Title FutureStat'); // N. future stat does not count

    // Test 7hari
    $response7d = $this->get(route('articles.popular', ['periode' => '7hari']));
    $response7d->assertSee('Title 24h');
    $response7d->assertSee('Title 3days'); // L. older than 24h DOES count in 7hari
    $response7d->assertDontSee('Title 10days'); // M. older than 7 days does not count
    $response7d->assertDontSee('Title FutureStat');
});

it('O. multiple stat rows for one article are SUMMED', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(10)]);
    createStat($article, now()->subHours(2), 15);
    createStat($article, now()->subHours(5), 25);

    $response = $this->get(route('articles.popular', ['periode' => '24jam']));
    $response->assertSee('40'); // sum is 40
});

it('P,Q. ranking orders by SUM(period views) descending and changes between periods', function () {
    $articleA = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(10), 'title' => 'Article A']);
    createStat($articleA, now()->subHours(5), 20); // 20 in 24h

    $articleB = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(10), 'title' => 'Article B']);
    createStat($articleB, now()->subHours(5), 10); // 10 in 24h
    createStat($articleB, now()->subDays(3), 100); // 100 in 3 days

    // In 24jam: A (20) > B (10)
    $response24 = $this->get(route('articles.popular', ['periode' => '24jam']));
    $content24 = $response24->getContent();
    expect(strpos($content24, 'Article A') < strpos($content24, 'Article B'))->toBeTrue();

    // In 7hari: B (110) > A (20)
    $response7d = $this->get(route('articles.popular', ['periode' => '7hari']));
    $content7d = $response7d->getContent();
    expect(strpos($content7d, 'Article B') < strpos($content7d, 'Article A'))->toBeTrue();
});

it('R. deterministic tie breaker uses published_at DESC then id DESC', function () {
    $article1 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(3), 'title' => 'Article 1']);
    $article2 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2), 'title' => 'Article 2']);
    $article3 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2), 'title' => 'Article 3']);
    
    // Equal views (10)
    createStat($article1, now()->subHours(2), 10);
    createStat($article2, now()->subHours(2), 10);
    createStat($article3, now()->subHours(2), 10);

    // Sort order:
    // period_views DESC (all 10)
    // published_at DESC (2 and 3 are newer than 1)
    // id DESC (3 has higher id than 2)
    // Result: 3, 2, 1

    $response = $this->get(route('articles.popular'));
    $content = $response->getContent();
    
    $p3 = strpos($content, 'Article 3');
    $p2 = strpos($content, 'Article 2');
    $p1 = strpos($content, 'Article 1');
    
    expect($p3 < $p2)->toBeTrue();
    expect($p2 < $p1)->toBeTrue();
});

it('S. article with only lifetime views_count but no period stats does NOT become popular', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published, 
        'published_at' => now()->subDay(), 
        'views_count' => 1000,
        'title' => 'Lifetime View Only'
    ]);

    // No stat created.
    $response = $this->get(route('articles.popular'));
    $response->assertDontSee('Lifetime View Only');
});

it('T,U. pagination uses 10 items, page 2 works and ranking number continues from 11', function () {
    for ($i = 1; $i <= 15; $i++) {
        $article = Article::factory()->create([
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDay(),
            'title' => "Ranked Article {$i}"
        ]);
        // View counts to force strict order: 100, 99, 98, ...
        createStat($article, now()->subHours(2), 100 - $i);
    }

    $responsePage1 = $this->get(route('articles.popular'));
    $responsePage1->assertViewHas('articles', function ($articles) {
        return $articles->count() === 10;
    });
    // Check rank number 1 is rendered (it's inside absolute div, so checking exact string is tough, but it should have 1 to 10)
    $responsePage1->assertSee('Ranked Article 1');
    $responsePage1->assertSee('Ranked Article 10');
    $responsePage1->assertDontSee('Ranked Article 11');

    $responsePage2 = $this->get(route('articles.popular', ['page' => 2]));
    $responsePage2->assertViewHas('articles', function ($articles) {
        return $articles->count() === 5;
    });
    $responsePage2->assertSee('Ranked Article 11');
    // Ensure ranking number 11 is displayed
    $responsePage2->assertSee('>11<', false); // The rank badge is {{ $rank }}, surrounded by div
});

it('V. selected periode remains in pagination URLs', function () {
    for ($i = 1; $i <= 15; $i++) {
        $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
        createStat($article, now()->subHours(2), 10);
    }

    $response = $this->get(route('articles.popular', ['periode' => '7hari']));
    $response->assertSee('periode=7hari&amp;page=2', false);
});

it('W. empty state returns 200', function () {
    $response = $this->get(route('articles.popular'));
    $response->assertStatus(200);
    $response->assertSee('Belum ada berita terpopuler untuk periode ini.');
});

it('X,Y. article link uses articles.show and canonical URL is route without query string', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
    createStat($article, now()->subHours(2), 10);

    $response = $this->get(route('articles.popular', ['periode' => '7hari']));
    
    // X. article link
    $url = route('articles.show', ['article' => $article->slug]);
    $response->assertSee($url, false);
    
    // Y. Canonical should just be the base popular route
    $canonicalUrl = route('articles.popular');
    $response->assertSee('rel="canonical" href="' . $canonicalUrl . '"', false);
});

it('Z. article with only zero-view period stat does NOT become popular', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'title' => 'Zero View Article']);
    createStat($article, now()->subHours(2), 0);

    $response = $this->get(route('articles.popular'));
    
    $response->assertDontSee('Zero View Article');
    $response->assertSee('Belum ada berita terpopuler untuk periode ini.');
});

