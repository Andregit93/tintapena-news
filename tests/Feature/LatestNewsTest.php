<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 200 for latest news page', function () {
    $this->get(route('articles.latest'))
        ->assertStatus(200);
});

it('shows published articles and excludes draft, scheduled, archived, and future published articles', function () {
    $published = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Valid Published Title',
    ]);
    
    $draft = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'title' => 'Draft Title',
    ]);
    
    $scheduled = Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
        'title' => 'Scheduled Title',
    ]);
    
    $archived = Article::factory()->create([
        'status' => ArticleStatus::Archived,
        'published_at' => now()->subDays(2),
        'archived_at' => now(),
        'title' => 'Archived Title',
    ]);
    
    $future = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
        'title' => 'Future Published Title',
    ]);

    $response = $this->get(route('articles.latest'));
    
    $response->assertStatus(200);
    $response->assertSee('Valid Published Title');
    
    $response->assertDontSee('Draft Title');
    $response->assertDontSee('Scheduled Title');
    $response->assertDontSee('Archived Title');
    $response->assertDontSee('Future Published Title');
});

it('orders articles by published_at descending', function () {
    $old = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(3),
        'title' => 'Old Article',
    ]);
    
    $new = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(1),
        'title' => 'New Article',
    ]);
    
    $mid = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'title' => 'Mid Article',
    ]);

    $response = $this->get(route('articles.latest'));
    
    $content = $response->getContent();
    
    $posNew = strpos($content, 'New Article');
    $posMid = strpos($content, 'Mid Article');
    $posOld = strpos($content, 'Old Article');
    
    expect($posNew < $posMid)->toBeTrue();
    expect($posMid < $posOld)->toBeTrue();
});

it('paginates articles to 10 per page', function () {
    Article::factory()->count(15)->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('articles.latest'));
    
    $response->assertStatus(200);
    
    // Assert there are 10 articles on the first page
    $response->assertViewHas('articles', function ($articles) {
        return $articles->count() === 10;
    });
});

it('shows remaining articles on page 2', function () {
    Article::factory()->count(15)->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('articles.latest', ['page' => 2]));
    
    $response->assertStatus(200);
    
    $response->assertViewHas('articles', function ($articles) {
        return $articles->count() === 5;
    });
});

it('returns 200 and shows empty state when no articles exist', function () {
    $response = $this->get(route('articles.latest'));
    
    $response->assertStatus(200);
    $response->assertSee('Belum ada berita terbaru.');
});

it('uses articles.show route for article links', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('articles.latest'));
    
    $url = route('articles.show', ['article' => $article->slug]);
    $response->assertSee($url, false);
});

it('renders featured image correctly when available', function () {
    $media = Media::factory()->create([
        'alt_text' => 'Custom Alt Text'
    ]);
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'featured_media_id' => $media->id,
    ]);

    $response = $this->get(route('articles.latest'));
    
    $response->assertStatus(200);
    $response->assertSee(Storage::disk($media->disk)->url($media->path));
    $response->assertSee('Custom Alt Text');
});

it('does not break when featured image is missing', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'featured_media_id' => null,
        'title' => 'Article Without Image'
    ]);

    $response = $this->get(route('articles.latest'));
    
    $response->assertStatus(200);
    $response->assertSee('Article Without Image');
    $response->assertSee('<svg', false); // Should render placeholder
});
