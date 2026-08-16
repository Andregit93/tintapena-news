<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Models\Category;
use App\Models\Region;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('Latest: deterministic pagination with duplicate published_at', function () {
    // Create 15 articles with the exact same published_at
    $time = now()->subHour();
    Article::factory()->count(15)->create([
        'status' => ArticleStatus::Published,
        'published_at' => $time,
    ]);

    $response1 = $this->get(route('articles.latest', ['page' => 1]));
    $response2 = $this->get(route('articles.latest', ['page' => 2]));

    $page1Titles = $response1->viewData('articles')->pluck('title')->toArray();
    $page2Titles = $response2->viewData('articles')->pluck('title')->toArray();

    // Ensure no overlap
    $intersection = array_intersect($page1Titles, $page2Titles);
    expect($intersection)->toBeEmpty();
    expect(count($page1Titles))->toBe(10);
    expect(count($page2Titles))->toBe(5);
});

it('Popular: deterministic pagination with duplicate period_views and published_at', function () {
    $time = now()->subHour();
    
    $articles = Article::factory()->count(15)->create([
        'status' => ArticleStatus::Published,
        'published_at' => $time,
    ]);

    foreach ($articles as $article) {
        $stat = new ArticleViewStat();
        $stat->article_id = $article->id;
        $stat->period_start = $time;
        $stat->views_count = 100;
        $stat->save();
    }

    $response1 = $this->get(route('articles.popular', ['page' => 1]));
    $response2 = $this->get(route('articles.popular', ['page' => 2]));

    $page1Titles = $response1->viewData('articles')->pluck('title')->toArray();
    $page2Titles = $response2->viewData('articles')->pluck('title')->toArray();

    $intersection = array_intersect($page1Titles, $page2Titles);
    expect($intersection)->toBeEmpty();
    expect(count($page1Titles))->toBe(10);
    expect(count($page2Titles))->toBe(5);
});

it('Category: deterministic pagination with duplicate published_at', function () {
    $category = Category::factory()->create();
    $time = now()->subHour();
    Article::factory()->count(15)->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => $time,
    ]);

    $response1 = $this->get(route('categories.show', ['category' => $category->slug, 'page' => 1]));
    $response2 = $this->get(route('categories.show', ['category' => $category->slug, 'page' => 2]));

    $page1Titles = $response1->viewData('articles')->pluck('title')->toArray();
    $page2Titles = $response2->viewData('articles')->pluck('title')->toArray();

    $intersection = array_intersect($page1Titles, $page2Titles);
    expect($intersection)->toBeEmpty();
});

it('Region: deterministic pagination with duplicate published_at', function () {
    $region = Region::factory()->create();
    $time = now()->subHour();
    Article::factory()->count(15)->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => $time,
    ]);

    $response1 = $this->get(route('regions.show', ['region' => $region->slug, 'page' => 1]));
    $response2 = $this->get(route('regions.show', ['region' => $region->slug, 'page' => 2]));

    $page1Titles = $response1->viewData('articles')->pluck('title')->toArray();
    $page2Titles = $response2->viewData('articles')->pluck('title')->toArray();

    $intersection = array_intersect($page1Titles, $page2Titles);
    expect($intersection)->toBeEmpty();
});

it('Tag: deterministic pagination with duplicate published_at', function () {
    $tag = Tag::factory()->create();
    $time = now()->subHour();
    $articles = Article::factory()->count(15)->create([
        'status' => ArticleStatus::Published,
        'published_at' => $time,
    ]);
    
    foreach ($articles as $article) {
        $article->tags()->attach($tag->id);
    }

    $response1 = $this->get(route('tags.show', ['tag' => $tag->slug, 'page' => 1]));
    $response2 = $this->get(route('tags.show', ['tag' => $tag->slug, 'page' => 2]));

    $page1Titles = $response1->viewData('articles')->pluck('title')->toArray();
    $page2Titles = $response2->viewData('articles')->pluck('title')->toArray();

    $intersection = array_intersect($page1Titles, $page2Titles);
    expect($intersection)->toBeEmpty();
});

it('All public lists enforce strictly published boundary', function () {
    $category = Category::factory()->create();
    $region = Region::factory()->create();
    $tag = Tag::factory()->create();

    // Create leaky states
    $draft = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'title' => 'Leak Draft',
        'category_id' => $category->id,
        'region_id' => $region->id,
    ]);
    $draft->tags()->attach($tag->id);

    $scheduled = Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
        'title' => 'Leak Scheduled',
        'category_id' => $category->id,
        'region_id' => $region->id,
    ]);
    $scheduled->tags()->attach($tag->id);

    $archived = Article::factory()->create([
        'status' => ArticleStatus::Archived,
        'published_at' => now()->subDay(),
        'title' => 'Leak Archived',
        'category_id' => $category->id,
        'region_id' => $region->id,
    ]);
    $archived->tags()->attach($tag->id);

    $futurePublished = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
        'title' => 'Leak Future',
        'category_id' => $category->id,
        'region_id' => $region->id,
    ]);
    $futurePublished->tags()->attach($tag->id);

    $routes = [
        route('articles.latest'),
        route('categories.show', ['category' => $category->slug]),
        route('regions.show', ['region' => $region->slug]),
        route('tags.show', ['tag' => $tag->slug]),
    ];

    foreach ($routes as $url) {
        $response = $this->get($url);
        $response->assertDontSee('Leak Draft');
        $response->assertDontSee('Leak Scheduled');
        $response->assertDontSee('Leak Archived');
        $response->assertDontSee('Leak Future');
    }
});

it('Popular: normalizes invalid period and paginates without carrying invalid period string', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subHour(),
    ]);
    
    $stat = new ArticleViewStat();
    $stat->article_id = $article->id;
    $stat->period_start = now()->subHour();
    $stat->views_count = 100;
    $stat->save();

    // The user queries with an invalid 'periode=abc'
    $response = $this->get(route('articles.popular', ['periode' => 'abc']));
    
    $response->assertStatus(200);
    // It should behave as 24jam and show the article
    $response->assertSee($article->title);
    
    // The paginator URL must not contain 'periode=abc'. It should either be omitted or be '24jam'.
    // In our implementation we use appends(['periode' => '24jam']).
    $content = $response->getContent();
    expect($content)->not->toContain('periode=abc');
    expect($content)->toContain('periode=24jam');
});

it('Popular: 24h exact boundaries', function () {
    $now = \Carbon\Carbon::create(2026, 8, 16, 12, 0, 0);
    \Carbon\Carbon::setTestNow($now);
    
    $articleIncluded = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $now->copy()->subHours(25),
        'title' => 'Included 24h',
    ]);
    
    $articleExcluded = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $now->copy()->subHours(25),
        'title' => 'Excluded 24h',
    ]);
    
    // Stat exactly now - 24 hours => INCLUDED
    $stat1 = new ArticleViewStat();
    $stat1->article_id = $articleIncluded->id;
    $stat1->period_start = $now->copy()->subHours(24);
    $stat1->views_count = 100;
    $stat1->save();
    
    // Stat 1 second before now - 24 hours => EXCLUDED
    $stat2 = new ArticleViewStat();
    $stat2->article_id = $articleExcluded->id;
    $stat2->period_start = $now->copy()->subHours(24)->subSecond();
    $stat2->views_count = 100;
    $stat2->save();
    
    // Stat exactly now => INCLUDED
    $articleIncludedNow = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $now->copy()->subHours(25),
        'title' => 'Included Now',
    ]);
    $stat3 = new ArticleViewStat();
    $stat3->article_id = $articleIncludedNow->id;
    $stat3->period_start = $now->copy();
    $stat3->views_count = 100;
    $stat3->save();
    
    // Stat 1 second after now => EXCLUDED
    $articleExcludedFuture = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $now->copy()->subHours(25),
        'title' => 'Excluded Future',
    ]);
    $stat4 = new ArticleViewStat();
    $stat4->article_id = $articleExcludedFuture->id;
    $stat4->period_start = $now->copy()->addSecond();
    $stat4->views_count = 100;
    $stat4->save();
    
    $response = $this->get(route('articles.popular', ['periode' => '24jam']));
    
    $response->assertSee('Included 24h');
    $response->assertSee('Included Now');
    $response->assertDontSee('Excluded 24h');
    $response->assertDontSee('Excluded Future');
    
    \Carbon\Carbon::setTestNow(); // reset
});

it('Popular: 7d exact boundaries', function () {
    $now = \Carbon\Carbon::create(2026, 8, 16, 12, 0, 0);
    \Carbon\Carbon::setTestNow($now);
    
    $articleIncluded = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $now->copy()->subDays(8),
        'title' => 'Included 7d',
    ]);
    
    $articleExcluded = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $now->copy()->subDays(8),
        'title' => 'Excluded 7d',
    ]);
    
    // Stat exactly now - 7 days => INCLUDED
    $stat1 = new ArticleViewStat();
    $stat1->article_id = $articleIncluded->id;
    $stat1->period_start = $now->copy()->subDays(7);
    $stat1->views_count = 100;
    $stat1->save();
    
    // Stat 1 second before now - 7 days => EXCLUDED
    $stat2 = new ArticleViewStat();
    $stat2->article_id = $articleExcluded->id;
    $stat2->period_start = $now->copy()->subDays(7)->subSecond();
    $stat2->views_count = 100;
    $stat2->save();
    
    $response = $this->get(route('articles.popular', ['periode' => '7hari']));
    
    $response->assertSee('Included 7d');
    $response->assertDontSee('Excluded 7d');
    
    \Carbon\Carbon::setTestNow(); // reset
});

