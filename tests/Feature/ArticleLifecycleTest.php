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
