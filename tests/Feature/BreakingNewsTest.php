<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Filament\Pages\ManageBreakingNews;
use App\Models\Article;
use App\Models\BreakingNews;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('1. Guest cannot access /admin/breaking-news', function () {
    $this->get('/admin/breaking-news')->assertRedirect('/admin/login');
});

it('2. Authenticated admin can access manager', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/breaking-news')->assertStatus(200);
});

it('3. Admin can create Breaking News from Published internal article', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'internal',
            'article_id' => $article->id,
            'is_active' => false,
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('breaking_news', [
        'article_id' => $article->id,
        'headline' => null,
        'target_url' => null,
    ]);
});

it('4. Draft article cannot be selected/saved as internal Breaking News', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'internal',
            'article_id' => $article->id,
        ])
        ->assertHasTableActionErrors();
});

it('5. Scheduled article rejected', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Scheduled, 'published_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'internal',
            'article_id' => $article->id,
        ])
        ->assertHasTableActionErrors();
});

it('6. Archived article rejected', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Archived]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'internal',
            'article_id' => $article->id,
        ])
        ->assertHasTableActionErrors();
});

it('7. Future-dated Published article rejected', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->addDay()]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'internal',
            'article_id' => $article->id,
        ])
        ->assertHasTableActionErrors();
});

it('8. Manual headline + valid URL can be created', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Manual News',
            'target_url' => 'https://example.com',
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('breaking_news', [
        'article_id' => null,
        'headline' => 'Manual News',
        'target_url' => 'https://example.com',
    ]);
});

it('9. Manual headline missing is rejected', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'target_url' => 'https://example.com',
        ])
        ->assertHasTableActionErrors(['headline']);
});

it('10. Manual URL missing is rejected', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
        ])
        ->assertHasTableActionErrors(['target_url']);
});

it('11. Invalid manual URL is rejected', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
            'target_url' => 'javascript:alert(1)',
        ])
        ->assertHasTableActionErrors();
});

it('12. Mixed internal + manual source cannot persist ambiguously', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);

    // Create an internal item first, then edit it to manual and ensure article_id is cleared
    $record = BreakingNews::forceCreate([
        'article_id' => $article->id,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('edit', $record, data: [
            'source_type' => 'manual',
            'headline' => 'Manual',
            'target_url' => 'https://example.com'
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('breaking_news', [
        'id' => $record->id,
        'article_id' => null,
        'headline' => 'Manual',
        'target_url' => 'https://example.com',
    ]);
});

it('13. Empty source rejected', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => '',
            'target_url' => '',
        ])
        ->assertHasTableActionErrors();
});

it('14. created_by is authenticated admin', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
            'target_url' => 'https://example.com',
        ]);

    $this->assertDatabaseHas('breaking_news', [
        'created_by' => $user->id,
    ]);
});

it('15. client cannot override created_by', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
            'target_url' => 'https://example.com',
            'created_by' => $otherUser->id,
        ]);

    $this->assertDatabaseHas('breaking_news', [
        'created_by' => $user->id, // not $otherUser->id
    ]);
});

it('16. starts_at persists', function () {
    $user = User::factory()->create();
    $start = now()->addHour()->startOfMinute();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
            'target_url' => 'https://example.com',
            'starts_at' => $start->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);

    $this->assertDatabaseHas('breaking_news', [
        'starts_at' => $start,
    ]);
});

it('17. ends_at persists', function () {
    $user = User::factory()->create();
    $start = now()->addHour()->startOfMinute();
    $end = now()->addHours(2)->startOfMinute();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
            'target_url' => 'https://example.com',
            'starts_at' => $start->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'ends_at' => $end->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);

    $this->assertDatabaseHas('breaking_news', [
        'ends_at' => $end,
    ]);
});

it('18. ends_at before starts_at rejected', function () {
    $user = User::factory()->create();
    $start = now()->addHours(2)->startOfMinute();
    $end = now()->addHour()->startOfMinute();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
            'target_url' => 'https://example.com',
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $end->format('Y-m-d H:i:s'),
        ])
        ->assertHasTableActionErrors();
});

it('19. ends_at equal starts_at rejected', function () {
    $user = User::factory()->create();
    $time = now()->addHour()->startOfMinute();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
            'target_url' => 'https://example.com',
            'starts_at' => $time->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'ends_at' => $time->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ])
        ->assertHasTableActionErrors();
});

it('20. null start/end allowed', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Test',
            'target_url' => 'https://example.com',
            'starts_at' => null,
            'ends_at' => null,
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('breaking_news', [
        'starts_at' => null,
        'ends_at' => null,
    ]);
});

it('21. valid internal item can activate', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
    $record = BreakingNews::forceCreate([
        'article_id' => $article->id,
        'created_by' => $user->id,
        'is_active' => false,
    ]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('activate', $record);

    expect($record->fresh()->is_active)->toBeTrue();
});

it('22. valid manual item can activate', function () {
    $user = User::factory()->create();
    $record = BreakingNews::forceCreate([
        'headline' => 'Test',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => false,
    ]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('activate', $record);

    expect($record->fresh()->is_active)->toBeTrue();
});

it('23. active item can deactivate', function () {
    $user = User::factory()->create();
    $record = BreakingNews::forceCreate([
        'headline' => 'Test',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('deactivate', $record);

    expect($record->fresh()->is_active)->toBeFalse();
});

it('24. activation does not change Article status', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
    $record = BreakingNews::forceCreate([
        'article_id' => $article->id,
        'created_by' => $user->id,
        'is_active' => false,
    ]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('activate', $record);

    expect($article->fresh()->status)->toBe(ArticleStatus::Published);
});

it('25. stale internal item whose article became Archived cannot be newly activated', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Archived]);
    $record = BreakingNews::forceCreate([
        'article_id' => $article->id,
        'created_by' => $user->id,
        'is_active' => false,
    ]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('activate', $record);

    // Should fail via Notification, remains inactive
    expect($record->fresh()->is_active)->toBeFalse();
});

it('26. active manual item with no schedule appears', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Manual Valid',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertSee('Manual Valid');
});

it('27. inactive item does not appear', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Inactive Manual',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => false,
    ]);

    $this->get('/')->assertDontSeeText('Inactive Manual');
});

it('28. future starts_at item does not appear', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Future Manual',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
        'starts_at' => now()->addHour(),
    ]);

    $this->get('/')->assertDontSeeText('Future Manual');
});

it('29. past ends_at item does not appear', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Past Manual',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
        'ends_at' => now()->subHour(),
    ]);

    $this->get('/')->assertDontSeeText('Past Manual');
});

it('30. starts_at == now appears', function () {
    Carbon::setTestNow(now());
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Exact Start Manual',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
        'starts_at' => now(),
    ]);

    $this->get('/')->assertSee('Exact Start Manual');
    Carbon::setTestNow(); // reset
});

it('31. ends_at == now does not appear', function () {
    Carbon::setTestNow(now());
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Exact End Manual',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
        'ends_at' => now(),
    ]);

    $this->get('/')->assertDontSeeText('Exact End Manual');
    Carbon::setTestNow(); // reset
});

it('32. active internal Published item appears', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Internal Headline Appears', 'status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
    BreakingNews::forceCreate([
        'article_id' => $article->id,
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertSee('Internal Headline Appears');
});

it('33. internal ticker link uses articles.show', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Internal Link Test', 'status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
    BreakingNews::forceCreate([
        'article_id' => $article->id,
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertSee(route('articles.show', $article));
});

it('34. manual ticker uses target_url', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Target URL Test',
        'target_url' => 'https://external-example.com/some/path',
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertSee('https://external-example.com/some/path');
});

it('35. stale article-backed item is hidden if article becomes Draft/Archived/etc.', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Stale Internal Headline', 'status' => ArticleStatus::Draft]);
    BreakingNews::forceCreate([
        'article_id' => $article->id,
        'created_by' => $user->id,
        'is_active' => true, // Still active in DB
    ]);

    $this->get('/')->assertDontSeeText('Stale Internal Headline');
});

it('36. malformed manual URL from direct/corrupt DB record is not rendered as link', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Corrupt URL Test',
        'target_url' => 'javascript:alert(1)',
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    // We expect the text might not render if we strip it, or it renders without the a-tag.
    // Component is set to not render corrupt URLs entirely.
    $this->get('/')->assertDontSeeText('Corrupt URL Test');
});

it('37. empty Breaking collection does not break homepage', function () {
    // DB is empty for BreakingNews
    $this->get('/')->assertStatus(200);
});

it('38. multiple active items use deterministic order', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Older News',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
        'created_at' => now()->subDays(2),
    ]);
    BreakingNews::forceCreate([
        'headline' => 'Newer News',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
        'created_at' => now()->subDays(1),
    ]);

    $response = $this->get('/');
    $content = $response->getContent();

    $posNew = strpos($content, 'Newer News');
    $posOld = strpos($content, 'Older News');

    expect($posNew)->toBeLessThan($posOld);
});

it('39. manual headline is safely escaped', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => '<script>alert("XSS")</script>',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertDontSee('<script>alert("XSS")</script>', false)
        ->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', false);
});

it('40. homepage remains HTTP 200 with Breaking News enabled', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Test',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertStatus(200);
});

it('41. created_by is preserved on edit', function () {
    $creatorA = User::factory()->create();
    $adminB = User::factory()->create();

    $record = BreakingNews::forceCreate([
        'headline' => 'Original',
        'target_url' => 'https://example.com',
        'created_by' => $creatorA->id,
    ]);

    Livewire::actingAs($adminB)
        ->test(ManageBreakingNews::class)
        ->callTableAction('edit', $record, data: [
            'source_type' => 'manual',
            'headline' => 'Edited by B',
            'target_url' => 'https://example.com',
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('breaking_news', [
        'id' => $record->id,
        'created_by' => $creatorA->id, // Remains A
        'headline' => 'Edited by B',
    ]);
});

it('42. Manual -> Internal normalization', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);

    $record = BreakingNews::forceCreate([
        'headline' => 'Manual Headline',
        'target_url' => 'https://example.com',
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('edit', $record, data: [
            'source_type' => 'internal',
            'article_id' => $article->id,
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('breaking_news', [
        'id' => $record->id,
        'article_id' => $article->id,
        'headline' => null,
        'target_url' => null,
        'created_by' => $user->id,
    ]);
});

it('43. invalid source_type is rejected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'invalid-value',
            'headline' => 'Manual',
            'target_url' => 'https://example.com',
        ])
        ->assertHasTableActionErrors(['source_type']);
});

it('44. Future-Published stale public test', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create([
        'title' => 'Future Article',
        'status' => ArticleStatus::Published,
        'published_at' => now()->addDay()
    ]);

    BreakingNews::forceCreate([
        'article_id' => $article->id,
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertDontSeeText('Future Article');
});

it('45. URL query-string regression', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Query String Test',
        'target_url' => 'https://example.com/news?a=1&b=2',
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')
        ->assertSee('?a=1&amp;b=2', false)
        ->assertDontSee('?a=1&amp;amp;b=2', false);
});

it('46. Admin persistence accepts starts_at=null and ends_at=future', function () {
    $user = User::factory()->create();
    $end = now()->addHours(2)->startOfMinute();

    Livewire::actingAs($user)
        ->test(ManageBreakingNews::class)
        ->callTableAction('create', null, data: [
            'source_type' => 'manual',
            'headline' => 'Null Start Future End',
            'target_url' => 'https://example.com',
            'starts_at' => null,
            'ends_at' => $end->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'is_active' => true,
        ])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('breaking_news', [
        'starts_at' => null,
        'ends_at' => $end,
        'is_active' => true,
    ]);
});

it('47. Public visibility accepts starts_at=null and ends_at=future', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Null Start Future End Visible',
        'target_url' => 'https://example.com',
        'starts_at' => null,
        'ends_at' => now()->addHours(2),
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')
        ->assertSee('Null Start Future End Visible');
});

it('48. Public visibility rejects starts_at=null and ends_at=past', function () {
    $user = User::factory()->create();
    BreakingNews::forceCreate([
        'headline' => 'Null Start Past End Invisible',
        'target_url' => 'https://example.com',
        'starts_at' => null,
        'ends_at' => now()->subHours(2),
        'created_by' => $user->id,
        'is_active' => true,
    ]);

    $this->get('/')
        ->assertDontSee('Null Start Past End Invisible');
});
