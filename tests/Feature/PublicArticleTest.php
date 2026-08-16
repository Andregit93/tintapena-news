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
        ->assertSee('Oleh ' . $article->author->name)
        ->assertDontSee('Oleh Redaksi TINTAPENA')
        ->assertSee('Politik')
        ->assertSee('BANGKA')
        ->assertSee('Alt Image')
        ->assertSee('Caption Image')
        ->assertSee('Credit Image')
        ->assertSee('Article body content')
        ->assertSee('Test Tag')
        ->assertDontSee('WhatsApp')
        ->assertDontSee('Facebook')
        ->assertDontSee('Salin Tautan')
        ->assertDontSee('IKLAN 728 x 90');
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

it('uses title fallback when seo_title is null, empty or whitespace', function ($seoTitle) {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Normal Title',
        'seo_title' => $seoTitle,
    ]);

    $this->get(route('articles.show', $article))
        ->assertStatus(200)
        ->assertSee('<title>Normal Title</title>', false);
})->with([null, '', '   ']);

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

it('safely HTML-escapes SEO fields', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Title With <script>alert("xss")</script>',
        'seo_title' => 'SEO <script>alert("xss")</script>',
        'meta_description' => 'Meta <script>alert("xss")</script>',
    ]);

    $response = $this->get(route('articles.show', $article));
    $response->assertStatus(200);

    // Verify it doesn't see the unescaped script tag in metadata output
    $response->assertDontSee('<title>SEO <script>alert("xss")</script></title>', false);
    $response->assertDontSee('content="Meta <script>alert("xss")</script>"', false);
    
    // Verify it sees the escaped version
    $response->assertSee(e('SEO <script>alert("xss")</script>'), false);
    $response->assertSee(e('Meta <script>alert("xss")</script>'), false);
});

it('sanitizes malicious HTML in article content', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'content' => '<p>Safe paragraph</p><strong>Safe bold</strong><script>alert(1)</script><img src=x onerror="alert(1)"><a href="javascript:alert(1)">bad link</a>',
    ]);

    $response = $this->get(route('articles.show', $article));
    $response->assertStatus(200);

    // Should still contain safe tags
    $response->assertSee('<p>Safe paragraph</p>', false);
    $response->assertSee('<strong>Safe bold</strong>', false);
    
    // Should NOT contain dangerous content
    $response->assertDontSee('<script>alert(1)</script>', false);
    $response->assertDontSee('onerror=', false);
    $response->assertDontSee('javascript:', false);
});

it('displays published_at and updated_at in Asia/Jakarta timezone', function () {
    // Set current time to a specific UTC instant
    $utcTime = Carbon::create(2026, 8, 16, 3, 0, 0, 'UTC');
    Carbon::setTestNow($utcTime);
    
    // 03:00 UTC = 10:00 WIB
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => $utcTime,
        'updated_at' => (clone $utcTime)->addHours(2), // 05:00 UTC = 12:00 WIB
    ]);

    $response = $this->get(route('articles.show', $article));
    $response->assertStatus(200);

    // Verify it displays WIB 10:00 for published_at
    $response->assertSee('16 Agustus 2026, 10:00 WIB');
    
    // Verify it displays WIB 12:00 for updated_at
    $response->assertSee('Diperbarui 12:00 WIB');
    
    Carbon::setTestNow(); // reset
});

