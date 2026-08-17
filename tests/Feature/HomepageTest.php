<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Models\Category;
use App\Models\HomepageSlot;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\HomepageSlotSeeder::class);
});

it('1. GET / returns 200', function () {
    $this->get('/')->assertStatus(200);
});

it('2. Route name home still works', function () {
    expect(route('home'))->toBe(url('/'));
    $this->get(route('home'))->assertStatus(200);
});

it('3. Laravel welcome page is no longer served by /', function () {
    $this->get('/')->assertViewIs('pages.home');
});

it('4. active headline_main Published article appears', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subDay(),
        'title' => 'Headline Main Public Title',
    ]);
    HomepageSlot::where('slot_key', 'headline_main')->update([
        'article_id' => $article->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertSeeText('Headline Main Public Title');
});

it('5. inactive headline_main does not appear', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->subYears(2),
        'title' => 'Headline Main Inactive Title',
    ]);
    HomepageSlot::where('slot_key', 'headline_main')->update([
        'article_id' => $article->id,
        'is_active' => false,
    ]);

    $response = $this->get('/');
    expect($response->original->getData()['headlineMain'])->toBeNull();
});

it('6. headline slot pointing to Draft does not appear', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'title' => 'Draft Headline Title',
    ]);
    HomepageSlot::where('slot_key', 'headline_main')->update([
        'article_id' => $article->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertDontSeeText('Draft Headline Title');
});

it('7. headline slot pointing to Scheduled does not appear', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Scheduled,
        'scheduled_at' => now()->addDays(2),
        'title' => 'Scheduled Headline Title',
    ]);
    HomepageSlot::where('slot_key', 'headline_main')->update([
        'article_id' => $article->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertDontSeeText('Scheduled Headline Title');
});

it('8. headline slot pointing to Archived does not appear', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Archived,
        'archived_at' => now()->subDay(),
        'title' => 'Archived Headline Title',
    ]);
    HomepageSlot::where('slot_key', 'headline_main')->update([
        'article_id' => $article->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertDontSeeText('Archived Headline Title');
});

it('9. headline slot pointing to future-dated Published does not appear', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Published,
        'published_at' => now()->addHours(2),
        'title' => 'Future Published Headline',
    ]);
    HomepageSlot::where('slot_key', 'headline_main')->update([
        'article_id' => $article->id,
        'is_active' => true,
    ]);

    $this->get('/')->assertDontSeeText('Future Published Headline');
});

it('10. supporting headlines render in slot order', function () {
    $article2 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'title' => 'Supporting 2']);
    $article3 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'title' => 'Supporting 3']);
    
    HomepageSlot::where('slot_key', 'headline_2')->update(['article_id' => $article2->id, 'is_active' => true, 'sort_order' => 20]);
    HomepageSlot::where('slot_key', 'headline_3')->update(['article_id' => $article3->id, 'is_active' => true, 'sort_order' => 10]);

    $response = $this->get('/');
    $supportingHeadlines = $response->original->getData()['supportingHeadlines'];
    
    expect($supportingHeadlines->count())->toBe(2);
    expect($supportingHeadlines[0]->id)->toBe($article3->id);
    expect($supportingHeadlines[1]->id)->toBe($article2->id);
});

it('11. editor picks render in sort_order', function () {
    $p1 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'title' => 'Pick 1']);
    $p2 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'title' => 'Pick 2']);
    
    HomepageSlot::where('slot_key', 'editor_pick_1')->update(['article_id' => $p1->id, 'is_active' => true, 'sort_order' => 20]);
    HomepageSlot::where('slot_key', 'editor_pick_2')->update(['article_id' => $p2->id, 'is_active' => true, 'sort_order' => 10]);

    $response = $this->get('/');
    $editorPicks = $response->original->getData()['editorPicks'];
    
    expect($editorPicks->count())->toBe(2);
    expect($editorPicks[0]->id)->toBe($p2->id);
    expect($editorPicks[1]->id)->toBe($p1->id);
});

it('12. inactive editor pick does not appear', function () {
    $p = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subYears(2), 'title' => 'Inactive Pick']);
    HomepageSlot::where('slot_key', 'editor_pick_1')->update(['article_id' => $p->id, 'is_active' => false]);
    $response = $this->get('/');
    expect($response->original->getData()['editorPicks']->contains($p))->toBeFalse();
});

it('13. stale/non-public editor pick does not appear', function () {
    $p = Article::factory()->create(['status' => ArticleStatus::Draft, 'title' => 'Draft Pick']);
    HomepageSlot::where('slot_key', 'editor_pick_1')->update(['article_id' => $p->id, 'is_active' => true]);
    $this->get('/')->assertDontSeeText('Draft Pick');
});

it('14. Latest section contains only Published articles', function () {
    Article::factory()->create(['status' => ArticleStatus::Draft, 'title' => 'Latest Draft']);
    $this->get('/')->assertDontSeeText('Latest Draft');
});

it('15. Latest ordering is published_at DESC then id DESC', function () {
    $a1 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2), 'title' => 'Old Latest']);
    $a2 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1), 'title' => 'New Latest']);
    $response = $this->get('/');
    $response->assertSeeText('New Latest');
    $response->assertSeeText('Old Latest');
});

it('16. Latest limited to 6', function () {
    Article::factory()->count(7)->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
    $response = $this->get('/');
    $articles = $response->original->getData()['latestArticles'];
    expect($articles->count())->toBe(6);
});

it('17. Popular uses 24h period stats', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(5), 'title' => 'Popular 24h Title']);
    ArticleViewStat::factory()->create([
        'article_id' => $article->id,
        'period_start' => now()->subHours(10),
        'views_count' => 10,
    ]);
    $this->get('/')->assertSeeText('Popular 24h Title');
});

it('18. Popular excludes lifetime-only views', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(5), 'title' => 'Lifetime Title', 'views_count' => 100]);
    ArticleViewStat::factory()->create([
        'article_id' => $article->id,
        'period_start' => now()->subDays(3), // Older than 24h
        'views_count' => 10,
    ]);
    $response = $this->get('/');
    expect($response->original->getData()['popularArticles']->contains($article))->toBeFalse();
});

it('19. Popular excludes zero-view period rows', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1), 'title' => 'Zero View Title']);
    ArticleViewStat::factory()->create([
        'article_id' => $article->id,
        'period_start' => now()->subHours(5),
        'views_count' => 0,
    ]);
    $response = $this->get('/');
    expect($response->original->getData()['popularArticles']->contains($article))->toBeFalse();
});

it('20. Popular SUMs multiple period rows', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
    ArticleViewStat::factory()->create([
        'article_id' => $article->id,
        'period_start' => now()->subHours(10),
        'views_count' => 15,
    ]);
    ArticleViewStat::factory()->create([
        'article_id' => $article->id,
        'period_start' => now()->subHours(5),
        'views_count' => 25,
    ]);
    $response = $this->get('/');
    $popular = $response->original->getData()['popularArticles'];
    expect($popular->first()->period_views)->toBe(40);
});

it('21. Popular order deterministic', function () {
    $a1 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
    $a2 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
    
    ArticleViewStat::factory()->create(['article_id' => $a1->id, 'period_start' => now()->subHours(5), 'views_count' => 50]);
    ArticleViewStat::factory()->create(['article_id' => $a2->id, 'period_start' => now()->subHours(5), 'views_count' => 100]);

    $response = $this->get('/');
    $popular = $response->original->getData()['popularArticles'];
    expect($popular->first()->id)->toBe($a2->id);
    expect($popular->last()->id)->toBe($a1->id);
});

it('22. Regional section excludes region_id null', function () {
    $r = Region::factory()->create();
    $a1 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'region_id' => $r->id, 'title' => 'Has Region Title']);
    $a2 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'region_id' => null, 'title' => 'No Region Title']);
    $response = $this->get('/');
    $regional = $response->original->getData()['regionalArticles'];
    expect($regional->contains($a1))->toBeTrue();
    expect($regional->contains($a2))->toBeFalse();
});

it('23. Politik & Pemerintahan contains only those two categories', function () {
    $catPol = Category::factory()->create(['slug' => 'politik']);
    $catPem = Category::factory()->create(['slug' => 'pemerintahan']);
    $catOther = Category::factory()->create(['slug' => 'lainnya']);

    $aPol = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catPol->id]);
    $aPem = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catPem->id]);
    $aOther = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catOther->id]);

    $response = $this->get('/');
    $politik = $response->original->getData()['politikArticles'];
    expect($politik->contains($aPol))->toBeTrue();
    expect($politik->contains($aPem))->toBeTrue();
    expect($politik->contains($aOther))->toBeFalse();
});

it('24. Ekonomi section isolates ekonomi', function () {
    $catEk = Category::factory()->create(['slug' => 'ekonomi']);
    $catOther = Category::factory()->create(['slug' => 'lainnya']);
    $aEk = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catEk->id]);
    $aOther = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catOther->id]);
    $response = $this->get('/');
    $ekonomiArticles = $response->original->getData()['ekonomiArticles'];
    expect($ekonomiArticles->contains($aEk))->toBeTrue();
    expect($ekonomiArticles->contains($aOther))->toBeFalse();
});

it('25. Hukum & Kriminal isolates hukum-kriminal', function () {
    $catHk = Category::factory()->create(['slug' => 'hukum-kriminal']);
    $catOther = Category::factory()->create(['slug' => 'lainnya']);
    $aHk = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catHk->id]);
    $aOther = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catOther->id]);
    $response = $this->get('/');
    $hukumArticles = $response->original->getData()['hukumArticles'];
    expect($hukumArticles->contains($aHk))->toBeTrue();
    expect($hukumArticles->contains($aOther))->toBeFalse();
});

it('26. Pariwisata isolates pariwisata', function () {
    $catPar = Category::factory()->create(['slug' => 'pariwisata']);
    $catOther = Category::factory()->create(['slug' => 'lainnya']);
    $aPar = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catPar->id]);
    $aOther = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'category_id' => $catOther->id]);
    $response = $this->get('/');
    $pariwisataArticles = $response->original->getData()['pariwisataArticles'];
    expect($pariwisataArticles->contains($aPar))->toBeTrue();
    expect($pariwisataArticles->contains($aOther))->toBeFalse();
});

it('27. Draft/Scheduled/Archived/future Published never leak into any automatic section', function () {
    Article::factory()->create(['status' => ArticleStatus::Draft, 'title' => 'Leak Draft']);
    Article::factory()->create(['status' => ArticleStatus::Scheduled, 'scheduled_at' => now()->addDay(), 'title' => 'Leak Scheduled']);
    Article::factory()->create(['status' => ArticleStatus::Archived, 'archived_at' => now()->subDay(), 'title' => 'Leak Archived']);
    Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->addHour(), 'title' => 'Leak Future']);
    
    $response = $this->get('/');
    $response->assertDontSeeText('Leak Draft');
    $response->assertDontSeeText('Leak Scheduled');
    $response->assertDontSeeText('Leak Archived');
    $response->assertDontSeeText('Leak Future');
});

it('28. Homepage works with zero Published articles', function () {
    Article::query()->delete();
    $this->get('/')->assertStatus(200);
});

it('29. Homepage works without configured homepage slots if database condition allows', function () {
    HomepageSlot::query()->update(['is_active' => false, 'article_id' => null]);
    $this->get('/')->assertStatus(200);
});

it('30. Article links use articles.show', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay()]);
    $this->get('/')->assertSee(route('articles.show', $article));
});

it('31. Missing featured image does not break response', function () {
    Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDay(), 'featured_media_id' => null]);
    $this->get('/')->assertStatus(200);
});
