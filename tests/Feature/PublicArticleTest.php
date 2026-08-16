<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Region;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 200 for published article', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200);
});

it('returns 404 for draft article', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'published_at' => null,
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(404);
});

it('returns 404 for scheduled article in the future', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
        'published_at' => null,
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(404);
});

it('returns 404 for archived article', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Archived,
        'published_at' => now()->subDays(2),
        'archived_at' => now(),
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(404);
});

it('returns 404 for published article with future published_at', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(404);
});

it('returns 404 for unknown slug', function () {
    $this->get(route('articles.show', ['article' => 'unknown-slug']))
        ->assertStatus(404);
});

it('renders article details correctly', function () {
    $category = Category::factory()->create(['name' => 'Politik']);
    $region = Region::factory()->create(['name' => 'Bangka']);
    $media = Media::factory()->create([
        'alt_text' => 'Alt Image',
        'caption' => 'Caption Image',
        'photo_credit' => 'Credit Image'
    ]);
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Test Article Title',
        'subtitle' => 'Test Article Subtitle',
        'content' => '<p>Article body content</p>',
        'category_id' => $category->id,
        'region_id' => $region->id,
        'featured_media_id' => $media->id,
    ]);
    
    $tag = Tag::factory()->create(['name' => 'Test Tag']);
    $article->tags()->attach($tag);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee('Test Article Title')
        ->assertSee('Test Article Subtitle')
        ->assertSee('Oleh Redaksi TINTAPENA')
        ->assertSee('Politik')
        ->assertSee('BANGKA')
        ->assertSee('Alt Image')
        ->assertSee('Caption Image')
        ->assertSee('Credit Image')
        ->assertSee('Article body content')
        ->assertSee('Test Tag');
});

it('does not break when region is null', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'region_id' => null,
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee($article->title);
});

it('works without featured image', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'featured_media_id' => null,
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee($article->title);
});

it('uses seo_title when present', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Normal Title',
        'seo_title' => 'SEO Title Override',
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee('<title>SEO Title Override</title>', false);
});

it('uses title fallback when seo_title is null', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Normal Title',
        'seo_title' => null,
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee('<title>Normal Title</title>', false);
});

it('uses meta_description when present', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'meta_description' => 'Custom Meta Description',
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee('content="Custom Meta Description"', false);
});

it('uses excerpt as meta description fallback', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'meta_description' => null,
        'excerpt' => 'Custom Excerpt Fallback',
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee('content="Custom Excerpt Fallback"', false);
});

it('uses stripped content as meta description fallback', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'meta_description' => null,
        'excerpt' => null,
        'content' => '<p>This is <strong>content</strong> that should be stripped.</p>',
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee('content="This is content that should be stripped."', false);
});

it('renders correct canonical url', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $canonicalUrl = route('articles.show', $article);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee('rel="canonical" href="' . $canonicalUrl . '"', false);
});

it('does not increment views_count', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'views_count' => 0,
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200);

    expect($article->refresh()->views_count)->toBe(0);
});
