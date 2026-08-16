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

// ============================================================================
// PUBLIC-002: RELATED NEWS TESTS
// ============================================================================

it('shows related article from same category and excludes self', function () {
    $category = Category::factory()->create();
    $article = Article::factory()->published()->create(['category_id' => $category->id]);
    $related = Article::factory()->published()->create(['category_id' => $category->id]);

    $response = $this->get(route('articles.show', $article));
    
    $response->assertStatus(200);
    $response->assertSee('BERITA TERKAIT');
    $response->assertSee($related->title);
    
    $response->assertViewHas('relatedArticles', function ($relatedArticles) use ($article) {
        return !$relatedArticles->contains('id', $article->id);
    });
});

it('does not show Draft, Scheduled, Archived, or future Published as related', function () {
    $category = Category::factory()->create();
    $article = Article::factory()->published()->create(['category_id' => $category->id]);
    
    $draft = Article::factory()->draft()->create(['category_id' => $category->id, 'title' => 'Draft Title']);
    $scheduled = Article::factory()->scheduled()->create(['category_id' => $category->id, 'title' => 'Scheduled Title']);
    $archived = Article::factory()->archived()->create(['category_id' => $category->id, 'title' => 'Archived Title']);
    $future = Article::factory()->published()->create(['category_id' => $category->id, 'published_at' => now()->addDay(), 'title' => 'Future Title']);

    $response = $this->get(route('articles.show', $article));
    $response->assertStatus(200);
    
    $response->assertDontSee('Draft Title');
    $response->assertDontSee('Scheduled Title');
    $response->assertDontSee('Archived Title');
    $response->assertDontSee('Future Title');
});

it('does not show article from another category as related', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    
    $article = Article::factory()->published()->create(['category_id' => $category1->id]);
    $other = Article::factory()->published()->create(['category_id' => $category2->id, 'title' => 'Other Category Title']);

    $response = $this->get(route('articles.show', $article));
    $response->assertDontSee('Other Category Title');
});

it('orders related articles newest first and limits to 4', function () {
    $category = Category::factory()->create();
    $article = Article::factory()->published()->create(['category_id' => $category->id]);
    
    // Create 5 related articles with different dates
    $a1 = Article::factory()->published()->create(['category_id' => $category->id, 'title' => 'Related 1', 'published_at' => now()->subDays(5)]);
    $a2 = Article::factory()->published()->create(['category_id' => $category->id, 'title' => 'Related 2', 'published_at' => now()->subDays(4)]);
    $a3 = Article::factory()->published()->create(['category_id' => $category->id, 'title' => 'Related 3', 'published_at' => now()->subDays(3)]);
    $a4 = Article::factory()->published()->create(['category_id' => $category->id, 'title' => 'Related 4', 'published_at' => now()->subDays(2)]);
    $a5 = Article::factory()->published()->create(['category_id' => $category->id, 'title' => 'Related 5', 'published_at' => now()->subDays(1)]);

    $response = $this->get(route('articles.show', $article));
    
    // a5 is newest, then a4, a3, a2. a1 should be excluded by limit(4).
    $response->assertSee('Related 5');
    $response->assertSee('Related 4');
    $response->assertSee('Related 3');
    $response->assertSee('Related 2');
    $response->assertDontSee('Related 1');
    
    // Check order (string position)
    $pos5 = strpos($response->getContent(), 'Related 5');
    $pos4 = strpos($response->getContent(), 'Related 4');
    $pos3 = strpos($response->getContent(), 'Related 3');
    $pos2 = strpos($response->getContent(), 'Related 2');
    
    expect($pos5 < $pos4)->toBeTrue();
    expect($pos4 < $pos3)->toBeTrue();
    expect($pos3 < $pos2)->toBeTrue();
});

it('remains 200 when no related articles exist', function () {
    $category = Category::factory()->create();
    $article = Article::factory()->published()->create(['category_id' => $category->id]);
    
    $response = $this->get(route('articles.show', $article));
    $response->assertStatus(200);
    $response->assertDontSee('BERITA TERKAIT');
});

// ============================================================================
// PUBLIC-003: SOCIAL SHARE TESTS
// ============================================================================

it('renders social share controls with wrapping behavior and correctly encoded canonical URL', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Test Article "Share" & Co'
    ]);
    
    $canonicalUrl = route('articles.show', $article);
    $encodedUrl = urlencode($canonicalUrl);
    $encodedTitleAndUrl = rawurlencode($article->title . ' ' . $canonicalUrl);
    $encodedTitle = rawurlencode($article->title);

    $response = $this->get(route('articles.show', $article));
    $response->assertStatus(200);
    
    // Wrapper wrapping behavior
    $response->assertSee('flex flex-wrap items-center gap-2 sm:gap-3', false);
    
    // WhatsApp
    $response->assertSee('WhatsApp');
    $response->assertSee('https://api.whatsapp.com/send?text=' . $encodedTitleAndUrl, false);
    
    // Facebook
    $response->assertSee('Facebook');
    $response->assertSee('https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl, false);
    
    // X
    $response->assertSee('X');
    $response->assertSee('https://twitter.com/intent/tweet?text=' . $encodedTitle . '&url=' . $encodedUrl, false);
    
    // Copy Link (Salin Tautan)
    $response->assertSee('Salin Tautan');
    $response->assertSee("data-copy-url=\"{$canonicalUrl}\"", false);
    $response->assertDontSee("x-data=\"{ copied: false, url: '{$canonicalUrl}' }\"", false);
});
