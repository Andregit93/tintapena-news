<?php

use App\Enums\AdvertisementType;
use App\Enums\ContactStatus;
use App\Enums\PageStatus;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Models\BreakingNews;
use App\Models\ContactMessage;
use App\Models\HomepageSlot;
use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('migrations run successfully', function () {
    $this->assertDatabaseHas('migrations', [
        'migration' => '2026_08_16_001121_create_homepage_slots_table'
    ]);
});

it('HomepageSlot relationships work', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();

    $slot = HomepageSlot::factory()->create([
        'article_id' => $article->id,
        'updated_by' => $user->id,
    ]);

    expect($slot->article->id)->toBe($article->id)
        ->and($slot->updatedBy->id)->toBe($user->id);
});

it('slot_key uniqueness is enforced', function () {
    HomepageSlot::factory()->create(['slot_key' => 'hero']);

    $this->expectException(QueryException::class);
    HomepageSlot::factory()->create(['slot_key' => 'hero']);
});

it('BreakingNews relationships work', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();

    $news = BreakingNews::factory()->create([
        'article_id' => $article->id,
        'created_by' => $user->id,
    ]);

    expect($news->article->id)->toBe($article->id)
        ->and($news->creator->id)->toBe($user->id);
});

it('ArticleViewStat relationship works', function () {
    $article = Article::factory()->create();

    $stat = ArticleViewStat::factory()->create([
        'article_id' => $article->id,
    ]);

    expect($stat->article->id)->toBe($article->id);
});

it('ArticleViewStat unique(article_id, period_start) is enforced', function () {
    $article = Article::factory()->create();
    $time = now()->startOfHour();

    ArticleViewStat::factory()->create([
        'article_id' => $article->id,
        'period_start' => $time,
    ]);

    $this->expectException(QueryException::class);
    ArticleViewStat::factory()->create([
        'article_id' => $article->id,
        'period_start' => $time,
    ]);
});

it('AdvertisementType enum cast works', function () {
    $ad = Advertisement::factory()->create([
        'type' => AdvertisementType::Script,
    ]);

    expect($ad->type)->toBe(AdvertisementType::Script)
        ->and($ad->type)->toBeInstanceOf(AdvertisementType::class);
});

it('Advertisement relationship to Media works', function () {
    $media = Media::factory()->create();
    $ad = Advertisement::factory()->create([
        'media_id' => $media->id,
    ]);

    expect($ad->media->id)->toBe($media->id);
});

it('PageStatus enum cast works', function () {
    $page = Page::factory()->create([
        'status' => PageStatus::Draft,
    ]);

    expect($page->status)->toBe(PageStatus::Draft)
        ->and($page->status)->toBeInstanceOf(PageStatus::class);
});

it('Page creator and updater relationships work', function () {
    $creator = User::factory()->create();
    $updater = User::factory()->create();

    $page = Page::factory()->create([
        'created_by' => $creator->id,
        'updated_by' => $updater->id,
    ]);

    expect($page->creator->id)->toBe($creator->id)
        ->and($page->updater->id)->toBe($updater->id);
});

it('Setting setting_key uniqueness is enforced', function () {
    Setting::factory()->create(['setting_key' => 'site_title']);

    $this->expectException(QueryException::class);
    Setting::factory()->create(['setting_key' => 'site_title']);
});

it('ContactStatus enum cast works', function () {
    $msg = ContactMessage::factory()->create([
        'status' => ContactStatus::Read,
    ]);

    expect($msg->status)->toBe(ContactStatus::Read)
        ->and($msg->status)->toBeInstanceOf(ContactStatus::class);
});

it('ContactMessage persistence works', function () {
    $msg = ContactMessage::factory()->create([
        'subject' => 'Hello World',
    ]);

    $this->assertDatabaseHas('contact_messages', [
        'id' => $msg->id,
        'subject' => 'Hello World',
    ]);
});

it('deleting an Article cascades its article_view_stats', function () {
    $article = Article::factory()->create();
    $stat = ArticleViewStat::factory()->create(['article_id' => $article->id]);

    $article->delete();

    $this->assertDatabaseMissing('article_view_stats', [
        'id' => $stat->id,
    ]);
});

it('deleting referenced Article does NOT delete homepage_slots or breaking_news; article_id becomes null', function () {
    $article = Article::factory()->create();
    $slot = HomepageSlot::factory()->create(['article_id' => $article->id]);
    $news = BreakingNews::factory()->create(['article_id' => $article->id]);

    $article->delete();

    $slot->refresh();
    $news->refresh();

    expect($slot->article_id)->toBeNull()
        ->and($news->article_id)->toBeNull();
});

it('deleting Media does NOT delete Advertisement; media_id becomes null', function () {
    $media = Media::factory()->create();
    $ad = Advertisement::factory()->create(['media_id' => $media->id]);

    $media->delete();

    $ad->refresh();

    expect($ad->media_id)->toBeNull();
});
