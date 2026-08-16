<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('A. GET /cari returns 200', function () {
    $response = $this->get(route('search'));
    $response->assertStatus(200);
    $response->assertSee('CARI BERITA');
});

it('B. title keyword match works', function () {
    $article = Article::factory()->published()->create([
        'title' => 'The quick brown fox',
    ]);

    $response = $this->get(route('search', ['q' => 'brown']));
    $response->assertStatus(200);
    $response->assertSee('The quick brown fox');
});

it('C. subtitle keyword match works', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Nothing here',
        'subtitle' => 'The quick brown fox jumps',
    ]);

    $response = $this->get(route('search', ['q' => 'brown']));
    $response->assertSee('Nothing here'); // Because title is rendered
});

it('D. excerpt keyword match works', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Nothing here either',
        'excerpt' => 'A brown fox was seen in the area.',
    ]);

    $response = $this->get(route('search', ['q' => 'brown']));
    $response->assertSee('Nothing here either');
    $response->assertSee('A brown fox');
});

it('E. search is not accidentally OR-leaking lifecycle restrictions', function () {
    $keyword = 'secret';

    Article::factory()->draft()->create(['title' => 'Draft secret']);
    Article::factory()->scheduled()->create(['title' => 'Scheduled secret', 'scheduled_at' => now()->addDay()]);
    Article::factory()->archived()->create(['title' => 'Archived secret', 'published_at' => now()->subDay()]);
    Article::factory()->published()->create(['title' => 'Future secret', 'published_at' => now()->addDay()]);

    $response = $this->get(route('search', ['q' => 'secret']));

    $response->assertDontSee('Draft secret');
    $response->assertDontSee('Scheduled secret');
    $response->assertDontSee('Archived secret');
    $response->assertDontSee('Future secret');
    $response->assertSee('Berita tidak ditemukan');
});

it('F. unrelated Published article excluded', function () {
    Article::factory()->published()->create(['title' => 'Apples are red']);
    Article::factory()->published()->create(['title' => 'Bananas are yellow']);

    $response = $this->get(route('search', ['q' => 'Apples']));
    $response->assertSee('Apples are red');
    $response->assertDontSee('Bananas are yellow');
});

it('G. result ordering is published_at DESC and id DESC', function () {
    $time = now()->subHour();
    $article1 = Article::factory()->published()->create([
        'title' => 'Keyword article older id',
        'published_at' => $time,
    ]);
    $article2 = Article::factory()->published()->create([
        'title' => 'Keyword article newer id',
        'published_at' => $time,
    ]);

    $response = $this->get(route('search', ['q' => 'Keyword']));

    $titles = $response->viewData('articles')->pluck('title')->toArray();

    expect($titles[0])->toBe('Keyword article newer id');
    expect($titles[1])->toBe('Keyword article older id');
});

it('H. pagination works and preserves normalized q', function () {
    Article::factory()->count(15)->published()->create([
        'title' => 'Test pagination keyword',
    ]);

    $response = $this->get(route('search', ['q' => ' pagination ']));
    $response->assertStatus(200);
    $articles = $response->viewData('articles');

    expect($articles->count())->toBe(10);

    $content = $response->getContent();
    expect($content)->toContain('q=pagination');

    $response2 = $this->get(route('search', ['q' => 'pagination', 'page' => 2]));
    expect($response2->viewData('articles')->count())->toBe(5);
});

it('I/J. blank or whitespace q returns 200 and does NOT return all articles', function () {
    Article::factory()->count(5)->published()->create();

    $response = $this->get(route('search', ['q' => '   ']));
    $response->assertStatus(200);

    $articles = $response->viewData('articles');
    expect($articles->isEmpty())->toBeTrue();
    $response->assertSee('Masukkan kata kunci untuk mencari berita.');
});

it('K. no results shows empty state', function () {
    $response = $this->get(route('search', ['q' => 'unfindable123']));
    $response->assertStatus(200);
    $response->assertSee('Berita tidak ditemukan');
    $response->assertSee('Coba gunakan kata kunci yang lebih umum');
});

it('L. special LIKE characters do not cause unintended broad matching', function () {
    Article::factory()->published()->create(['title' => 'Normal title']);

    $response = $this->get(route('search', ['q' => '%']));
    $response->assertDontSee('Normal title');
    $response->assertSee('Berita tidak ditemukan');

    $response2 = $this->get(route('search', ['q' => '_']));
    $response2->assertDontSee('Normal title');

    $response3 = $this->get(route('search', ['q' => '\\']));
    $response3->assertDontSee('Normal title');
});

it('M. article links use public article route', function () {
    $article = Article::factory()->published()->create(['title' => 'Link testing keyword']);
    $response = $this->get(route('search', ['q' => 'Link testing']));

    $expectedUrl = route('articles.show', ['article' => $article->slug]);
    $response->assertSee($expectedUrl);
});

it('N/O. null relationships render safely (missing image, nullable category/region)', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Nullable relations keyword',
        'region_id' => null,
        'featured_media_id' => null,
    ]);

    $response = $this->get(route('search', ['q' => 'Nullable']));
    $response->assertStatus(200);
    $response->assertSee('Nullable relations keyword');
});

it('P. SEARCH-002 category filter works', function () {
    $opiniCat = Category::factory()->create(['slug' => 'opini', 'name' => 'Opini']);
    $beritaCat = Category::factory()->create(['slug' => 'nasional', 'name' => 'Nasional']);

    $opiniArticle = Article::factory()->published()->create([
        'title' => 'Target opini keyword',
        'category_id' => $opiniCat->id,
    ]);

    $beritaArticle = Article::factory()->published()->create([
        'title' => 'Target berita keyword',
        'category_id' => $beritaCat->id,
    ]);

    // Default (Semua)
    $response = $this->get(route('search', ['q' => 'Target']));
    $response->assertSee('Target opini keyword');
    $response->assertSee('Target berita keyword');

    // Opini filter
    $responseOpini = $this->get(route('search', ['q' => 'Target', 'filter' => 'opini']));
    $responseOpini->assertSee('Target opini keyword');
    $responseOpini->assertDontSee('Target berita keyword');

    // Berita filter
    $responseBerita = $this->get(route('search', ['q' => 'Target', 'filter' => 'berita']));
    $responseBerita->assertDontSee('Target opini keyword');
    $responseBerita->assertSee('Target berita keyword');
});
