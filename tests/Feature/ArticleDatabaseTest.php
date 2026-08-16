<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can run migrations successfully', function () {
    // RefreshDatabase trait automatically runs migrations.
    // We just verify that tables exist.
    $this->assertDatabaseHas('migrations', [
        'migration' => '2026_08_16_000330_create_articles_table'
    ]);
});

it('can cast status to ArticleStatus enum', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
    ]);

    expect($article->status)->toBe(ArticleStatus::Scheduled);
    expect($article->status)->toBeInstanceOf(ArticleStatus::class);
});

it('has working article relationships', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $region = Region::factory()->create();
    $media = Media::factory()->create();
    
    $article = Article::factory()->create([
        'author_id' => $user->id,
        'category_id' => $category->id,
        'region_id' => $region->id,
        'featured_media_id' => $media->id,
    ]);
    
    $tags = Tag::factory(2)->create();
    $article->tags()->attach($tags);

    expect($article->author->id)->toBe($user->id)
        ->and($article->category->id)->toBe($category->id)
        ->and($article->region->id)->toBe($region->id)
        ->and($article->featuredMedia->id)->toBe($media->id)
        ->and($article->tags)->toHaveCount(2);
});

it('category has working relationship to articles', function () {
    $category = Category::factory()->create();
    Article::factory(3)->create(['category_id' => $category->id]);
    
    expect($category->articles)->toHaveCount(3);
});

it('region has working relationship to articles', function () {
    $region = Region::factory()->create();
    Article::factory(2)->create(['region_id' => $region->id]);
    
    expect($region->articles)->toHaveCount(2);
});

it('tag has many-to-many relationship to articles', function () {
    $tag = Tag::factory()->create();
    $articles = Article::factory(2)->create();
    
    $tag->articles()->attach($articles);
    
    expect($tag->articles)->toHaveCount(2);
});

it('media uploader relationship works', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create(['uploaded_by' => $user->id]);
    
    expect($media->uploader->id)->toBe($user->id);
});

it('published scope includes a published article with past published_at', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subMinutes(5),
    ]);
    
    $publishedArticles = Article::published()->get();
    
    expect($publishedArticles)->toHaveCount(1)
        ->and($publishedArticles->first()->id)->toBe($article->id);
});

it('published scope excludes non-published articles and future published articles', function () {
    // Draft
    Article::factory()->create([
        'status' => ArticleStatus::Draft,
    ]);
    
    // Scheduled
    Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
    ]);
    
    // Archived
    Article::factory()->create([
        'status' => ArticleStatus::Archived,
        'archived_at' => now()->subDay(),
    ]);
    
    // Published but in the future
    Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->addMinutes(10),
    ]);
    
    $publishedArticles = Article::published()->get();
    
    expect($publishedArticles)->toHaveCount(0);
});
