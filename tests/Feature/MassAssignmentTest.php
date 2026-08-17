<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\Category;
use App\Models\User;
use App\Enums\ArticleStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prevents mass assignment of protected article fields', function () {
    $category = Category::factory()->create();
    $author = User::factory()->create();
    $otherUser = User::factory()->create();

    $article = Article::factory()->create([
        'title' => 'Original Title',
        'category_id' => $category->id,
        'author_id' => $author->id,
        'status' => ArticleStatus::Draft,
        'views_count' => 0,
        'scheduled_at' => null,
        'published_at' => null,
        'archived_at' => null,
    ]);

    // We do not expect a MassAssignmentException because the environment might silently discard
    $article->fill([
        'title' => 'Changed Title',
        'author_id' => $otherUser->id,
        'status' => ArticleStatus::Published->value,
        'views_count' => 999999,
        'scheduled_at' => now()->addDay(),
        'published_at' => now(),
        'archived_at' => now(),
    ]);

    // SAFE field changes
    expect($article->title)->toBe('Changed Title');

    // PROTECTED fields remain unchanged
    expect($article->author_id)->toBe($author->id);
    expect($article->status)->toBe(ArticleStatus::Draft);
    expect($article->views_count)->toBe(0);
    expect($article->scheduled_at)->toBeNull();
    expect($article->published_at)->toBeNull();
    expect($article->archived_at)->toBeNull();
});

it('prevents mass assignment of protected media fields', function () {
    $uploader = User::factory()->create();
    $otherUser = User::factory()->create();

    $media = Media::factory()->create([
        'uploaded_by' => $uploader->id,
        'disk' => 'public',
        'filename' => 'original.jpg',
        'mime_type' => 'image/jpeg',
        'extension' => 'jpg',
        'size' => 1024,
        'width' => 800,
        'height' => 600,
        'alt_text' => 'Original Alt',
    ]);

    $media->fill([
        'alt_text' => 'Changed Alt',
        'uploaded_by' => $otherUser->id,
        'disk' => 's3',
        'filename' => 'hacked.jpg',
        'mime_type' => 'application/x-msdownload',
        'extension' => 'exe',
        'size' => 999999,
        'width' => 9999,
        'height' => 9999,
    ]);

    // SAFE field changes
    expect($media->alt_text)->toBe('Changed Alt');

    // PROTECTED fields remain unchanged
    expect($media->uploaded_by)->toBe($uploader->id);
    expect($media->disk)->toBe('public');
    expect($media->filename)->toBe('original.jpg');
    expect($media->mime_type)->toBe('image/jpeg');
    expect($media->extension)->toBe('jpg');
    expect($media->size)->toBe(1024);
    expect($media->width)->toBe(800);
    expect($media->height)->toBe(600);
});

it('prevents mass assignment of ArticleViewStat system fields', function () {
    $stat = new \App\Models\ArticleViewStat();

    try {
        $stat->fill([
            'article_id' => 1,
            'period_start' => now(),
            'views_count' => 50,
        ]);
    } catch (\Illuminate\Database\Eloquent\MassAssignmentException $e) {
        expect(true)->toBeTrue();
        return;
    }

    $this->fail('MassAssignmentException was not thrown');
});
