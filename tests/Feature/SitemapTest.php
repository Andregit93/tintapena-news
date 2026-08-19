<?php

use App\Enums\ArticleStatus;
use App\Enums\PageStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->category = Category::factory()->create(['is_active' => true]);
});

it('returns valid XML with correct content type', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    // Quick XML parse check to ensure it's structurally sound
    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();
    expect($xml->getName())->toBe('urlset');
});

it('includes required fixed public URLs', function () {
    $response = $this->get('/sitemap.xml');

    $content = $response->getContent();

    expect($content)->toContain(route('home'));
    expect($content)->toContain(route('articles.latest'));
    expect($content)->toContain(route('articles.popular'));
    expect($content)->toContain(route('contact.show'));
});

it('includes published articles', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'category_id' => $this->category->id,
        'author_id' => $this->admin->id,
    ]);

    $response = $this->get('/sitemap.xml');
    expect($response->getContent())->toContain(route('articles.show', $article->slug));
});

it('excludes non-public articles', function () {
    $draft = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'title' => 'Draft Secret',
        'category_id' => $this->category->id,
        'author_id' => $this->admin->id,
    ]);

    $scheduled = Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'title' => 'Scheduled Secret',
        'scheduled_at' => now()->addDay(),
        'category_id' => $this->category->id,
        'author_id' => $this->admin->id,
    ]);

    $archived = Article::factory()->create([
        'status' => ArticleStatus::Archived,
        'title' => 'Archived Secret',
        'category_id' => $this->category->id,
        'author_id' => $this->admin->id,
    ]);

    $futurePublished = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'title' => 'Future Secret',
        'published_at' => now()->addDay(),
        'category_id' => $this->category->id,
        'author_id' => $this->admin->id,
    ]);

    $response = $this->get('/sitemap.xml');
    $content = $response->getContent();

    expect($content)->not->toContain(route('articles.show', $draft->slug));
    expect($content)->not->toContain(route('articles.show', $scheduled->slug));
    expect($content)->not->toContain(route('articles.show', $archived->slug));
    expect($content)->not->toContain(route('articles.show', $futurePublished->slug));
});

it('includes published static pages but excludes draft pages', function () {
    $publishedPage = Page::factory()->create([
        'status' => PageStatus::Published,
        'title' => 'Published Page',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $draftPage = Page::factory()->create([
        'status' => PageStatus::Draft,
        'title' => 'Draft Page',
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $response = $this->get('/sitemap.xml');
    $content = $response->getContent();

    expect($content)->toContain(route('pages.show', $publishedPage->slug));
    expect($content)->not->toContain(route('pages.show', $draftPage->slug));
});

it('does not leak admin or preview URLs', function () {
    $response = $this->get('/sitemap.xml');
    $content = $response->getContent();

    expect($content)->not->toContain('/admin');
    expect($content)->not->toContain('/preview');
});

it('resolves /sitemap.xml before catch-all route', function () {
    // If catch-all catches it, it would try to find a Page with slug 'sitemap.xml'
    // and since there isn't one, it returns 404, or if one exists, it returns HTML.
    $response = $this->get('/sitemap.xml');
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
});

it('escapes characters properly to ensure valid XML', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        // Create a slug that contains characters that need escaping, e.g. &
        'slug' => 'test-&-slug-with-<tags>',
        'category_id' => $this->category->id,
        'author_id' => $this->admin->id,
    ]);

    $response = $this->get('/sitemap.xml');
    $response->assertStatus(200);

    // Test xml parsing
    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();
});
