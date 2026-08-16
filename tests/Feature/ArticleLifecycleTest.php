<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Enums\ArticleStatus;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\PreviewArticle;
use App\Actions\Articles\PublishArticle;
use App\Actions\Articles\ScheduleArticle;
use App\Actions\Articles\ArchiveArticle;
use Livewire\Livewire;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->category = Category::factory()->create(['is_active' => true]);
});

// ==========================================
// PREVIEW
// ==========================================
it('guest cannot access article preview', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    
    $this->get('/admin/berita/' . $article->id . '/preview')
        ->assertRedirect('/admin/login');
});

it('authenticated admin can preview Draft and headers are sent', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Test Preview',
        'content' => '<p>Content preview</p>',
        'status' => ArticleStatus::Draft, 
        'category_id' => $this->category->id, 
        'author_id' => $this->admin->id
    ]);
    
    $response = $this->get('/admin/berita/' . $article->id . '/preview');
    $response->assertSuccessful();
    $response->assertSee('Test Preview');
    $response->assertSee('Content preview');
    
    // Check security headers
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
    
    // Draft status remains Draft
    expect($article->refresh()->status)->toBe(ArticleStatus::Draft);
    // Views count does not increment (assuming defaults to 0)
    expect($article->views_count)->toBe(0);
});

// ==========================================
// PUBLISH
// ==========================================
it('valid Draft can be Published via Action', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->callAction('publish')
        ->assertHasNoActionErrors();

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Published);
    expect($article->published_at)->not->toBeNull();
    expect($article->scheduled_at)->toBeNull();
    expect($article->archived_at)->toBeNull();
    expect($article->author_id)->toBe($this->admin->id);
});

it('invalid Draft cannot publish', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => null, // Invalid
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    $action = new PublishArticle();
    expect(fn() => $action->execute($article))->toThrow(InvalidArgumentException::class);
});

it('Archived cannot be published', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Archived,
        'author_id' => $this->admin->id
    ]);

    $action = new PublishArticle();
    expect(fn() => $action->execute($article))->toThrow(InvalidArgumentException::class);
});

it('Published article cannot be republished', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new PublishArticle();
    expect(fn() => $action->execute($article))->toThrow(InvalidArgumentException::class);
});

it('future Scheduled article cannot be manually/early published', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Scheduled, 'scheduled_at' => now()->addDay(), 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new PublishArticle();
    expect(fn() => $action->execute($article, now()))->toThrow(InvalidArgumentException::class);
});

it('Draft cannot publish with arbitrary supplied publishedAt', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new PublishArticle();
    expect(fn() => $action->execute($article, now()->addDays(5)))->toThrow(InvalidArgumentException::class);
});

it('Manual Draft publish uses now()', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new PublishArticle();
    
    Carbon::setTestNow(Carbon::create(2025, 1, 1, 12, 0, 0));
    $publishedArticle = $action->execute($article);
    expect($publishedArticle->published_at->toDateTimeString())->toBe('2025-01-01 12:00:00');
    Carbon::setTestNow(null);
});

it('Due Scheduled article rejects publishedAt different from scheduled_at', function () {
    $scheduledAt = now()->subMinutes(10);
    $article = Article::factory()->create([
        'status' => ArticleStatus::Scheduled, 
        'scheduled_at' => $scheduledAt, 
        'category_id' => $this->category->id, 
        'author_id' => $this->admin->id
    ]);
    
    $action = new PublishArticle();
    expect(fn() => $action->execute($article, now()))->toThrow(InvalidArgumentException::class);
});

it('Due Scheduled article succeeds when publishedAt equals original scheduled_at', function () {
    $scheduledAt = now()->subMinutes(10)->startOfSecond();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Scheduled, 
        'scheduled_at' => $scheduledAt, 
        'category_id' => $this->category->id, 
        'author_id' => $this->admin->id
    ]);
    
    $action = new PublishArticle();
    $publishedArticle = $action->execute($article, $scheduledAt);
    
    expect($publishedArticle->status)->toBe(ArticleStatus::Published);
    expect($publishedArticle->published_at->toDateTimeString())->toBe($scheduledAt->toDateTimeString());
});

// ==========================================
// SCHEDULE
// ==========================================
it('valid Draft can be Scheduled', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    $futureDate = now()->addDays(2)->toDateTimeString();

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->callAction('schedule', data: [
            'scheduled_at' => $futureDate
        ])
        ->assertHasNoActionErrors();

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Scheduled);
    expect($article->scheduled_at->toDateTimeString())->toBe($futureDate);
    expect($article->published_at)->toBeNull();
});

it('past datetime rejected for scheduling', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    $pastDate = now()->subDay()->toDateTimeString();

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->callAction('schedule', data: [
            'scheduled_at' => $pastDate
        ])
        ->assertHasActionErrors(['scheduled_at' => 'after']);
        
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Draft);
});

it('Published article cannot be scheduled', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new ScheduleArticle();
    expect(fn() => $action->execute($article, now()->addDay()))->toThrow(InvalidArgumentException::class);
});

it('Scheduled article cannot be scheduled again', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Scheduled, 'scheduled_at' => now()->addDay(), 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new ScheduleArticle();
    expect(fn() => $action->execute($article, now()->addDays(2)))->toThrow(InvalidArgumentException::class);
});

it('Archived article cannot be scheduled', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Archived, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new ScheduleArticle();
    expect(fn() => $action->execute($article, now()->addDay()))->toThrow(InvalidArgumentException::class);
});

// ==========================================
// SCHEDULER
// ==========================================
it('due Scheduled article becomes Published', function () {
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Scheduled,
        'author_id' => $this->admin->id,
        'scheduled_at' => now()->subMinute()
    ]);
    
    $originalScheduledAt = clone $article->scheduled_at;

    Artisan::call('articles:publish-scheduled');

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Published);
    expect($article->published_at->toDateTimeString())->toBe($originalScheduledAt->toDateTimeString());
    expect($article->scheduled_at)->toBeNull();
});

it('future Scheduled article remains Scheduled', function () {
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Scheduled,
        'author_id' => $this->admin->id,
        'scheduled_at' => now()->addHour()
    ]);

    Artisan::call('articles:publish-scheduled');

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Scheduled);
    expect($article->published_at)->toBeNull();
    expect($article->scheduled_at)->not->toBeNull();
});

it('scheduled publish command remains idempotent when run twice', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Scheduled, 'scheduled_at' => now()->subMinute(), 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    Artisan::call('articles:publish-scheduled');
    Artisan::call('articles:publish-scheduled');
    
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Published);
});

// ==========================================
// ARCHIVE
// ==========================================
it('Published article can be Archived', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Published,
        'author_id' => $this->admin->id,
        'published_at' => now()->subDay()
    ]);

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->callAction('archive')
        ->assertHasNoActionErrors();

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Archived);
    expect($article->archived_at)->not->toBeNull();
    expect($article->published_at)->not->toBeNull();
});

it('Draft cannot be archived', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    $action = new ArchiveArticle();
    expect(fn() => $action->execute($article))->toThrow(InvalidArgumentException::class);
});

it('Scheduled article cannot be archived', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Scheduled, 'scheduled_at' => now()->addDay(), 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new ArchiveArticle();
    expect(fn() => $action->execute($article))->toThrow(InvalidArgumentException::class);
});

it('Archived article cannot be archived again', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Archived, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);
    $action = new ArchiveArticle();
    expect(fn() => $action->execute($article))->toThrow(InvalidArgumentException::class);
});

// ==========================================
// FILAMENT ACTION VISIBILITY
// ==========================================
it('Draft sees Preview, Publish, Schedule actions', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create(['status' => ArticleStatus::Draft, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->assertActionVisible('preview')
        ->assertActionVisible('publish')
        ->assertActionVisible('schedule')
        ->assertActionHidden('archive');
});

it('Published sees Preview and Archive actions', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->assertActionVisible('preview')
        ->assertActionHidden('publish')
        ->assertActionHidden('schedule')
        ->assertActionVisible('archive');
});

it('Archived only sees Preview action', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create(['status' => ArticleStatus::Archived, 'category_id' => $this->category->id, 'author_id' => $this->admin->id]);

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->assertActionVisible('preview')
        ->assertActionHidden('publish')
        ->assertActionHidden('schedule')
        ->assertActionHidden('archive');
});

it('preview displays alt text/caption/photo_credit correctly', function () {
    $this->actingAs($this->admin);
    $media = App\Models\Media::factory()->create([
        'alt_text' => 'Custom Alt Text View',
        'caption' => 'Custom Caption View',
        'photo_credit' => 'Custom Credit View',
        'mime_type' => 'image/jpeg',
    ]);
    
    $article = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'category_id' => $this->category->id,
        'author_id' => $this->admin->id,
        'featured_media_id' => $media->id,
    ]);

    $response = $this->get('/admin/berita/' . $article->id . '/preview');
    $response->assertSee('Custom Alt Text View');
    $response->assertSee('Custom Caption View');
    $response->assertSee('Custom Credit View');
});
