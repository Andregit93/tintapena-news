<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Region;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('A,B. valid region route returns 200 and route name works', function () {
    $region = Region::factory()->create(['slug' => 'test-region']);
    
    $response = $this->get(route('regions.show', ['region' => $region->slug]));
    
    $response->assertStatus(200);
});

it('C. nonexistent region slug returns 404', function () {
    $response = $this->get('/wilayah/does-not-exist');
    
    $response->assertStatus(404);
});

it('D,E,F,G,H,I,J. correct article lifecycle, isolation by region, and null-region exclusion', function () {
    $region = Region::factory()->create(['name' => 'Valid Region']);
    $otherRegion = Region::factory()->create(['name' => 'Other Region']);
    
    // Valid
    Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Valid Published Title',
    ]);
    
    // Other region
    Article::factory()->create([
        'region_id' => $otherRegion->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Other Region Title',
    ]);
    
    // Null region
    Article::factory()->create([
        'region_id' => null,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Null Region Title',
    ]);
    
    // Draft
    Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Draft,
        'title' => 'Draft Title',
    ]);
    
    // Scheduled
    Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
        'title' => 'Scheduled Title',
    ]);
    
    // Archived
    Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Archived,
        'published_at' => now()->subDays(2),
        'archived_at' => now(),
        'title' => 'Archived Title',
    ]);
    
    // Future published
    Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
        'title' => 'Future Published Title',
    ]);

    $response = $this->get(route('regions.show', ['region' => $region->slug]));
    
    $response->assertStatus(200);
    $response->assertSee('Valid Published Title');
    
    $response->assertDontSee('Other Region Title');
    $response->assertDontSee('Null Region Title');
    $response->assertDontSee('Draft Title');
    $response->assertDontSee('Scheduled Title');
    $response->assertDontSee('Archived Title');
    $response->assertDontSee('Future Published Title');
});

it('K,L. orders articles by published_at DESC then id DESC', function () {
    $region = Region::factory()->create();

    $old = Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(3),
        'title' => 'Article Old',
    ]);
    
    $new = Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(1),
        'title' => 'Article New',
    ]);
    
    $mid1 = Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'title' => 'Article Mid 1', // lower ID
    ]);
    
    $mid2 = Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'title' => 'Article Mid 2', // higher ID
    ]);

    $response = $this->get(route('regions.show', ['region' => $region->slug]));
    
    $content = $response->getContent();
    
    $posNew = strpos($content, 'Article New');
    $posMid2 = strpos($content, 'Article Mid 2');
    $posMid1 = strpos($content, 'Article Mid 1');
    $posOld = strpos($content, 'Article Old');
    
    expect($posNew < $posMid2)->toBeTrue();
    expect($posMid2 < $posMid1)->toBeTrue();
    expect($posMid1 < $posOld)->toBeTrue();
});

it('M,N. paginates articles to 10 per page and page 2 works', function () {
    $region = Region::factory()->create();

    Article::factory()->count(15)->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $responsePage1 = $this->get(route('regions.show', ['region' => $region->slug]));
    $responsePage1->assertStatus(200);
    $responsePage1->assertViewHas('articles', function ($articles) {
        return $articles->count() === 10;
    });

    $responsePage2 = $this->get(route('regions.show', ['region' => $region->slug, 'page' => 2]));
    $responsePage2->assertStatus(200);
    $responsePage2->assertViewHas('articles', function ($articles) {
        return $articles->count() === 5;
    });
});

it('O,P. valid region with zero published articles returns 200 and shows empty state', function () {
    $region = Region::factory()->create(['name' => 'Empty Region']);

    $response = $this->get(route('regions.show', ['region' => $region->slug]));
    
    $response->assertStatus(200);
    $response->assertSee('Belum ada berita pada wilayah ini.');
});

it('Q. real region name is rendered', function () {
    $region = Region::factory()->create(['name' => 'Bangka Tengah']);

    $response = $this->get(route('regions.show', ['region' => $region->slug]));
    
    $response->assertSee('BANGKA TENGAH');
});

it('R. article link uses articles.show', function () {
    $region = Region::factory()->create();
    $article = Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('regions.show', ['region' => $region->slug]));
    
    $url = route('articles.show', ['article' => $article->slug]);
    $response->assertSee($url, false);
});

it('S,T. renders featured image correctly and does not break when missing', function () {
    $region = Region::factory()->create();
    
    $media = Media::factory()->create([
        'alt_text' => 'Custom Alt Text'
    ]);
    
    $articleWithImage = Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'featured_media_id' => $media->id,
    ]);
    
    $articleWithoutImage = Article::factory()->create([
        'region_id' => $region->id,
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'featured_media_id' => null,
        'title' => 'Article Without Image'
    ]);

    $response = $this->get(route('regions.show', ['region' => $region->slug]));
    
    $response->assertStatus(200);
    $response->assertSee(Storage::disk($media->disk)->url($media->path));
    $response->assertSee('Custom Alt Text');
    
    $response->assertSee('Article Without Image');
    $response->assertSee('<svg', false); // placeholder
});

it('U. canonical URL uses regions.show', function () {
    $region = Region::factory()->create();
    
    $response = $this->get(route('regions.show', ['region' => $region->slug]));
    
    $canonicalUrl = route('regions.show', ['region' => $region->slug]);
    $response->assertSee('rel="canonical" href="' . $canonicalUrl . '"', false);
});

it('V,W. renders region navigation and links use regions.show', function () {
    $region1 = Region::factory()->create(['name' => 'Region One']);
    $region2 = Region::factory()->create(['name' => 'Region Two']);
    
    $response = $this->get(route('regions.show', ['region' => $region1->slug]));
    
    $response->assertStatus(200);
    
    // Check names
    $response->assertSee('REGION ONE');
    $response->assertSee('REGION TWO');
    
    // Check links
    $url1 = route('regions.show', ['region' => $region1->slug]);
    $url2 = route('regions.show', ['region' => $region2->slug]);
    
    $response->assertSee('href="' . $url1 . '"', false);
    $response->assertSee('href="' . $url2 . '"', false);
    
    // Check active state
    // bg-[#1A2BC4] text-white is for active
    // bg-gray-100 text-[#5D6470] is for inactive
    // So the active region (Region One) should be near the active classes
});
