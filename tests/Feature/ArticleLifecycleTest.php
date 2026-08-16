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
use Filament\Notifications\Notification;

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

it('sanitizes malicious HTML in article preview', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Test Preview',
        'content' => '<p>Safe paragraph</p><strong>Safe bold</strong><script>alert(1)</script><img src=x onerror="alert(1)"><a href="javascript:alert(1)">bad link</a>',
        'status' => ArticleStatus::Draft, 
        'category_id' => $this->category->id, 
        'author_id' => $this->admin->id
    ]);
    
    $response = $this->get('/admin/berita/' . $article->id . '/preview');
    $response->assertSuccessful();
    
    // Should still contain safe tags
    $response->assertSee('<p>Safe paragraph</p>', false);
    $response->assertSee('<strong>Safe bold</strong>', false);
    
    // Should NOT contain dangerous content
    $response->assertDontSee('<script>alert(1)</script>', false);
    $response->assertDontSee('onerror=', false);
    $response->assertDontSee('javascript:', false);
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

it('invalid Draft cannot publish but fails gracefully in UI', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content', // Make it valid so it passes form validation
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    // Force the domain action to throw InvalidArgumentException to simulate a business logic failure
    app()->bind(PublishArticle::class, function () {
        return new class extends PublishArticle {
            public function execute(Article $article, ?\Carbon\CarbonInterface $publishedAt = null): Article
            {
                throw new \InvalidArgumentException('Simulated domain failure');
            }
        };
    });
    
    // Test Livewire component catches and notifies
    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->mountAction('publish')
        ->callMountedAction()
        ->assertNotified();

    // Status remains draft
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Draft);
});

it('real incomplete Draft cannot publish and fails gracefully in UI', function () {
    $this->actingAs($this->admin);
    // Real incomplete draft with null content
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => null, 
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    // Do NOT mock PublishArticle here! We want the real domain logic to throw the exception.
    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->mountAction('publish')
        ->callMountedAction()
        ->assertNotified(); // Expect danger notification

    // Status and timestamps remain unchanged
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Draft);
    expect($article->published_at)->toBeNull();
    expect($article->scheduled_at)->toBeNull();
    expect($article->archived_at)->toBeNull();
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

    // Simulate user selecting a date in the UI (which operates in Asia/Jakarta)
    $futureDateUtc = now()->addDays(2)->startOfMinute();
    $futureDateJkt = $futureDateUtc->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->mountAction('schedule')
        ->setActionData([
            'scheduled_at' => $futureDateJkt
        ])
        ->callMountedAction()
        ->assertNotified();

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Scheduled);
    expect($article->scheduled_at->format('Y-m-d H:i:s'))->toBe($futureDateUtc->format('Y-m-d H:i:s'));
    expect($article->published_at)->toBeNull();
});

it('stores scheduled_at in UTC regardless of UI timezone', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content',
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    // Suppose admin enters 2026-08-16 10:00:00 (which the Filament component sends in UTC, i.e., 03:00:00)
    // Actually Filament sends it in the app timezone which is UTC! Wait, if we set FilamentTimezone to Asia/Jakarta,
    // Filament DatePicker will parse the user's input (in Asia/Jakarta) and convert it to App timezone (UTC) before sending it to the backend?
    // Yes! Filament automatically converts the timezone back to config('app.timezone') upon submission!
    // So the stored value should be in UTC.
    $futureDateUtc = now()->addDays(2)->startOfMinute();
    $futureDateJkt = $futureDateUtc->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'); 

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->mountAction('schedule')
        ->setActionData([
            'scheduled_at' => $futureDateJkt
        ])
        ->callMountedAction()
        ->assertNotified();

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Scheduled);
    expect($article->scheduled_at->format('Y-m-d H:i:s'))->toBe($futureDateUtc->format('Y-m-d H:i:s'));
});

it('incomplete Draft can still be saved but scheduling fails gracefully', function () {
    $this->actingAs($this->admin);
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => 'Valid content', // Valid for form save
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    // Force the domain action to throw InvalidArgumentException to simulate a business logic failure
    app()->bind(ScheduleArticle::class, function () {
        return new class extends ScheduleArticle {
            public function execute(Article $article, \Carbon\CarbonInterface $scheduledAt): Article
            {
                throw new \InvalidArgumentException('Simulated domain failure');
            }
        };
    });

    // UI operates in Asia/Jakarta, so we pass a JKT time string
    $futureDateUtc = now()->addDays(2)->startOfMinute();
    $futureDateJkt = $futureDateUtc->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->mountAction('schedule')
        ->setActionData([
            'scheduled_at' => $futureDateJkt
        ])
        ->callMountedAction()
        ->assertNotified();
        
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Draft);
});

it('real incomplete Draft cannot schedule and fails gracefully in UI', function () {
    $this->actingAs($this->admin);
    // Real incomplete draft with null content
    $article = Article::factory()->create([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
        'content' => null, 
        'category_id' => $this->category->id,
        'status' => ArticleStatus::Draft,
        'author_id' => $this->admin->id
    ]);

    $futureDateUtc = now()->addDays(2)->startOfMinute();
    $futureDateJkt = $futureDateUtc->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

    // Do NOT mock ScheduleArticle!
    Livewire::test(EditArticle::class, ['record' => $article->id])
        ->mountAction('schedule')
        ->setActionData([
            'scheduled_at' => $futureDateJkt
        ])
        ->callMountedAction()
        ->assertNotified(); // Expect danger notification
        
    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Draft);
    expect($article->published_at)->toBeNull();
    expect($article->scheduled_at)->toBeNull();
    expect($article->archived_at)->toBeNull();
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

it('scheduled command returns failure if one article fails but others succeed', function () {
    $articleFail = Article::factory()->create([
        'status' => ArticleStatus::Scheduled, 
        'scheduled_at' => now()->subMinutes(2), 
        'category_id' => $this->category->id, 
        'author_id' => $this->admin->id,
        'content' => null // This will cause PublishArticle domain action to fail
    ]);
    
    $articleSuccess = Article::factory()->create([
        'status' => ArticleStatus::Scheduled, 
        'scheduled_at' => now()->subMinute(), 
        'category_id' => $this->category->id, 
        'author_id' => $this->admin->id,
        'content' => 'Valid content' // This will succeed
    ]);

    $exitCode = Artisan::call('articles:publish-scheduled');
    
    // Command returns Command::FAILURE (1)
    expect($exitCode)->toBe(\Illuminate\Console\Command::FAILURE);
    
    // The failed article remains scheduled
    $articleFail->refresh();
    expect($articleFail->status)->toBe(ArticleStatus::Scheduled);
    expect($articleFail->published_at)->toBeNull();
    
    // The successful article gets published
    $articleSuccess->refresh();
    expect($articleSuccess->status)->toBe(ArticleStatus::Published);
    expect($articleSuccess->published_at)->not->toBeNull();
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
