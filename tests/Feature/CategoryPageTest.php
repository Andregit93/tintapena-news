<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('A,B. valid category route returns 200 and route name works', function () {
    $category = Category::factory()->create(['slug' => 'test-category']);
    
    $response = $this->get(route('categories.show', ['category' => $category->slug]));
    
    $response->assertStatus(200);
});

it('C. nonexistent category slug returns 404', function () {
    $response = $this->get('/kategori/does-not-exist');
    
    $response->assertStatus(404);
});

it('D,E,F,G,H,I. correct article lifecycle and isolation by category', function () {
    $category = Category::factory()->create(['name' => 'Valid Category']);
    $otherCategory = Category::factory()->create(['name' => 'Other Category']);
    
    // Valid
    Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Valid Published Title',
    ]);
    
    // Other category
    Article::factory()->create([
        'category_id' => $otherCategory->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Other Category Title',
    ]);
    
    // Draft
    Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Draft,
        'title' => 'Draft Title',
    ]);
    
    // Scheduled
    Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
        'title' => 'Scheduled Title',
    ]);
    
    // Archived
    Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Archived,
        'published_at' => now()->subDays(2),
        'archived_at' => now(),
        'title' => 'Archived Title',
    ]);
    
    // Future published
    Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
        'title' => 'Future Published Title',
    ]);

    $response = $this->get(route('categories.show', ['category' => $category->slug]));
    
    $response->assertStatus(200);
    $response->assertSee('Valid Published Title');
    
    $response->assertDontSee('Other Category Title');
    $response->assertDontSee('Draft Title');
    $response->assertDontSee('Scheduled Title');
    $response->assertDontSee('Archived Title');
    $response->assertDontSee('Future Published Title');
});

it('J,K. orders articles by published_at DESC then id DESC', function () {
    $category = Category::factory()->create();

    $old = Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(3),
        'title' => 'Article Old',
    ]);
    
    $new = Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(1),
        'title' => 'Article New',
    ]);
    
    $mid1 = Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'title' => 'Article Mid 1', // lower ID
    ]);
    
    $mid2 = Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'title' => 'Article Mid 2', // higher ID
    ]);

    $response = $this->get(route('categories.show', ['category' => $category->slug]));
    
    $content = $response->getContent();
    
    $posNew = strpos($content, 'Article New');
    $posMid2 = strpos($content, 'Article Mid 2');
    $posMid1 = strpos($content, 'Article Mid 1');
    $posOld = strpos($content, 'Article Old');
    
    expect($posNew < $posMid2)->toBeTrue();
    expect($posMid2 < $posMid1)->toBeTrue();
    expect($posMid1 < $posOld)->toBeTrue();
});

it('L,M. paginates articles to 10 per page and page 2 works', function () {
    $category = Category::factory()->create();

    Article::factory()->count(15)->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $responsePage1 = $this->get(route('categories.show', ['category' => $category->slug]));
    $responsePage1->assertStatus(200);
    $responsePage1->assertViewHas('articles', function ($articles) {
        return $articles->count() === 10;
    });

    $responsePage2 = $this->get(route('categories.show', ['category' => $category->slug, 'page' => 2]));
    $responsePage2->assertStatus(200);
    $responsePage2->assertViewHas('articles', function ($articles) {
        return $articles->count() === 5;
    });
});

it('N,O. valid category with zero published articles returns 200 and shows empty state', function () {
    $category = Category::factory()->create(['name' => 'Empty Category']);

    $response = $this->get(route('categories.show', ['category' => $category->slug]));
    
    $response->assertStatus(200);
    $response->assertSee('Belum ada berita pada kategori ini.');
});

it('P. real category name is rendered', function () {
    $category = Category::factory()->create(['name' => 'Ekonomi Politik']);

    $response = $this->get(route('categories.show', ['category' => $category->slug]));
    
    $response->assertSee('EKONOMI POLITIK');
});

it('Q. article link uses articles.show', function () {
    $category = Category::factory()->create();
    $article = Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('categories.show', ['category' => $category->slug]));
    
    $url = route('articles.show', ['article' => $article->slug]);
    $response->assertSee($url, false);
});

it('R,S. renders featured image correctly and does not break when missing', function () {
    $category = Category::factory()->create();
    
    $media = Media::factory()->create([
        'alt_text' => 'Custom Alt Text'
    ]);
    
    $articleWithImage = Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'featured_media_id' => $media->id,
    ]);
    
    $articleWithoutImage = Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'featured_media_id' => null,
        'title' => 'Article Without Image'
    ]);

    $response = $this->get(route('categories.show', ['category' => $category->slug]));
    
    $response->assertStatus(200);
    $response->assertSee(Storage::disk($media->disk)->url($media->path));
    $response->assertSee('Custom Alt Text');
    
    $response->assertSee('Article Without Image');
    $response->assertSee('<svg', false); // placeholder
});

it('T. canonical URL uses categories.show', function () {
    $category = Category::factory()->create();
    
    $response = $this->get(route('categories.show', ['category' => $category->slug]));
    
    $canonicalUrl = route('categories.show', ['category' => $category->slug]);
    $response->assertSee('rel="canonical" href="' . $canonicalUrl . '"', false);
});
