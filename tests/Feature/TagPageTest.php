<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Tag;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('A,B. valid tag route returns 200 and route name works', function () {
    $tag = Tag::factory()->create(['slug' => 'test-tag']);
    
    $response = $this->get(route('tags.show', ['tag' => $tag->slug]));
    
    $response->assertStatus(200);
});

it('C. nonexistent tag slug returns 404', function () {
    $response = $this->get('/topik/does-not-exist');
    
    $response->assertStatus(404);
});

it('D,E,F,G,H,I,J. correct article lifecycle and isolation by tag', function () {
    $tag = Tag::factory()->create(['name' => 'Valid Tag']);
    $otherTag = Tag::factory()->create(['name' => 'Other Tag']);
    
    // Valid
    $valid = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Valid Published Title',
    ]);
    $valid->tags()->attach($tag->id);
    
    // Without selected tag
    Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'No Tag Title',
    ]);
    
    // Different tag
    $other = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Other Tag Title',
    ]);
    $other->tags()->attach($otherTag->id);
    
    // Draft
    $draft = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'title' => 'Draft Title',
    ]);
    $draft->tags()->attach($tag->id);
    
    // Scheduled
    $scheduled = Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
        'title' => 'Scheduled Title',
    ]);
    $scheduled->tags()->attach($tag->id);
    
    // Archived
    $archived = Article::factory()->create([
        'status' => ArticleStatus::Archived,
        'published_at' => now()->subDays(2),
        'archived_at' => now(),
        'title' => 'Archived Title',
    ]);
    $archived->tags()->attach($tag->id);
    
    // Future published
    $future = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay(),
        'title' => 'Future Published Title',
    ]);
    $future->tags()->attach($tag->id);

    $response = $this->get(route('tags.show', ['tag' => $tag->slug]));
    
    $response->assertStatus(200);
    $response->assertSee('Valid Published Title');
    
    $response->assertDontSee('No Tag Title');
    $response->assertDontSee('Other Tag Title');
    $response->assertDontSee('Draft Title');
    $response->assertDontSee('Scheduled Title');
    $response->assertDontSee('Archived Title');
    $response->assertDontSee('Future Published Title');
});

it('K. article with multiple tags appears correctly once', function () {
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Multi Tag Article',
    ]);
    
    $article->tags()->attach([$tag1->id, $tag2->id]);
    
    $response = $this->get(route('tags.show', ['tag' => $tag1->slug]));
    $response->assertSee('Multi Tag Article');
    
    // It should only be present once in the collection
    $response->assertViewHas('articles', function ($articles) {
        return $articles->count() === 1;
    });
});

it('L,M. orders articles by published_at DESC then id DESC', function () {
    $tag = Tag::factory()->create();

    $old = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(3),
        'title' => 'Article Old',
    ]);
    $old->tags()->attach($tag->id);
    
    $new = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(1),
        'title' => 'Article New',
    ]);
    $new->tags()->attach($tag->id);
    
    $mid1 = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'title' => 'Article Mid 1', // lower ID
    ]);
    $mid1->tags()->attach($tag->id);
    
    $mid2 = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'title' => 'Article Mid 2', // higher ID
    ]);
    $mid2->tags()->attach($tag->id);

    $response = $this->get(route('tags.show', ['tag' => $tag->slug]));
    
    $content = $response->getContent();
    
    $posNew = strpos($content, 'Article New');
    $posMid2 = strpos($content, 'Article Mid 2');
    $posMid1 = strpos($content, 'Article Mid 1');
    $posOld = strpos($content, 'Article Old');
    
    expect($posNew < $posMid2)->toBeTrue();
    expect($posMid2 < $posMid1)->toBeTrue();
    expect($posMid1 < $posOld)->toBeTrue();
});

it('N,O. paginates articles to 10 per page and page 2 works', function () {
    $tag = Tag::factory()->create();

    $articles = Article::factory()->count(15)->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    
    foreach ($articles as $article) {
        $article->tags()->attach($tag->id);
    }

    $responsePage1 = $this->get(route('tags.show', ['tag' => $tag->slug]));
    $responsePage1->assertStatus(200);
    $responsePage1->assertViewHas('articles', function ($articles) {
        return $articles->count() === 10;
    });

    $responsePage2 = $this->get(route('tags.show', ['tag' => $tag->slug, 'page' => 2]));
    $responsePage2->assertStatus(200);
    $responsePage2->assertViewHas('articles', function ($articles) {
        return $articles->count() === 5;
    });
});

it('P,Q. valid tag with zero published articles returns 200 and shows empty state', function () {
    $tag = Tag::factory()->create(['name' => 'Empty Tag']);

    $response = $this->get(route('tags.show', ['tag' => $tag->slug]));
    
    $response->assertStatus(200);
    $response->assertSee('Belum ada berita pada topik ini.');
});

it('R. real tag name is rendered', function () {
    $tag = Tag::factory()->create(['name' => 'Pemilu 2024']);

    $response = $this->get(route('tags.show', ['tag' => $tag->slug]));
    
    $response->assertSee('TOPIK: PEMILU 2024');
});

it('S. article link uses articles.show', function () {
    $tag = Tag::factory()->create();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
    ]);
    $article->tags()->attach($tag->id);

    $response = $this->get(route('tags.show', ['tag' => $tag->slug]));
    
    $url = route('articles.show', ['article' => $article->slug]);
    $response->assertSee($url, false);
});

it('T,U. renders featured image correctly and does not break when missing', function () {
    $tag = Tag::factory()->create();
    
    $media = Media::factory()->create([
        'alt_text' => 'Custom Alt Text'
    ]);
    
    $articleWithImage = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'featured_media_id' => $media->id,
    ]);
    $articleWithImage->tags()->attach($tag->id);
    
    $articleWithoutImage = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDays(2),
        'featured_media_id' => null,
        'title' => 'Article Without Image'
    ]);
    $articleWithoutImage->tags()->attach($tag->id);

    $response = $this->get(route('tags.show', ['tag' => $tag->slug]));
    
    $response->assertStatus(200);
    $response->assertSee(Storage::disk($media->disk)->url($media->path));
    $response->assertSee('Custom Alt Text');
    
    $response->assertSee('Article Without Image');
    $response->assertSee('<svg', false); // placeholder
});

it('V. canonical URL uses tags.show', function () {
    $tag = Tag::factory()->create();
    
    $response = $this->get(route('tags.show', ['tag' => $tag->slug]));
    
    $canonicalUrl = route('tags.show', ['tag' => $tag->slug]);
    $response->assertSee('rel="canonical" href="' . $canonicalUrl . '"', false);
});
