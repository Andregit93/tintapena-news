<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\User;
use App\Enums\ArticleStatus;
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
    $utcInstant = \Carbon\Carbon::create(2026, 8, 15, 20, 0, 0, 'UTC');
    $articleInWibDay = Article::factory()->create([
        'created_at' => $utcInstant
    ]);

    // August 16 2026, 20:00 UTC is August 17 2026, 03:00 WIB
    $utcInstantNextDay = \Carbon\Carbon::create(2026, 8, 16, 20, 0, 0, 'UTC');
    $articleInNextWibDay = Article::factory()->create([
        'created_at' => $utcInstantNextDay
    ]);

    // We can test the Livewire component directly, but we mainly want to ensure
    // the query builder logic behaves correctly on MySQL.
    $fromUtc = \Carbon\Carbon::createFromFormat('Y-m-d', '2026-08-16', 'Asia/Jakarta')->startOfDay()->utc();
    $untilUtc = \Carbon\Carbon::createFromFormat('Y-m-d', '2026-08-16', 'Asia/Jakarta')->endOfDay()->utc();

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
    $tag = App\Models\Tag::factory()->create();
    $tag2 = App\Models\Tag::factory()->create();

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
    } catch (\Illuminate\Database\QueryException $e) {
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
