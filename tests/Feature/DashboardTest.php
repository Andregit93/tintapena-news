<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Widgets\ArticleStatusOverview;
use App\Filament\Widgets\LatestArticles;
use App\Filament\Widgets\PopularArticles;
use App\Models\Article;
use App\Models\ArticleViewStat;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard()
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertSeeLivewire(ArticleStatusOverview::class);
    }

    public function test_widget_renders_correctly_with_empty_database()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ArticleStatusOverview::class)
            ->assertSee('Diterbitkan')
            ->assertSee('Draft')
            ->assertSee('Terjadwal');

        $component = new ArticleStatusOverview;
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($component);

        $this->assertEquals(0, $stats[0]->getValue());
        $this->assertEquals(0, $stats[1]->getValue());
        $this->assertEquals(0, $stats[2]->getValue());
    }

    public function test_widget_shows_correct_database_backed_counts()
    {
        // 2 Published, 3 Draft, 4 Scheduled, 5 Archived
        Article::factory()->count(2)->create(['status' => ArticleStatus::Published]);
        Article::factory()->count(3)->create(['status' => ArticleStatus::Draft]);
        Article::factory()->count(4)->create(['status' => ArticleStatus::Scheduled]);
        Article::factory()->count(5)->create(['status' => ArticleStatus::Archived]);

        $component = new ArticleStatusOverview;
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($component);

        $this->assertCount(3, $stats);

        $this->assertEquals('Diterbitkan', $stats[0]->getLabel());
        $this->assertEquals(2, $stats[0]->getValue());

        $this->assertEquals('Draft', $stats[1]->getLabel());
        $this->assertEquals(3, $stats[1]->getValue());

        $this->assertEquals('Terjadwal', $stats[2]->getLabel());
        $this->assertEquals(4, $stats[2]->getValue());
    }

    public function test_counts_are_not_hardcoded()
    {
        Article::factory()->count(2)->create(['status' => ArticleStatus::Published]);

        $component = new ArticleStatusOverview;
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);

        $this->assertEquals(2, $method->invoke($component)[0]->getValue());

        Article::factory()->create(['status' => ArticleStatus::Published]);

        $this->assertEquals(3, $method->invoke($component)[0]->getValue());
    }

    public function test_published_status_includes_future_dated_articles()
    {
        Article::factory()->create([
            'status' => ArticleStatus::Published,
            'published_at' => now()->addDays(5),
        ]);

        $component = new ArticleStatusOverview;
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);

        $this->assertEquals(1, $method->invoke($component)[0]->getValue());
    }

    public function test_dashboard_uses_custom_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $this->assertTrue(in_array(Dashboard::class, Filament::getPages()));
    }

    public function test_latest_articles_only_shows_publicly_published()
    {
        $published = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subHour()]);
        $draft = Article::factory()->create(['status' => ArticleStatus::Draft, 'published_at' => now()->subHour()]);
        $scheduled = Article::factory()->create(['status' => ArticleStatus::Scheduled, 'published_at' => now()->addHour()]);
        $archived = Article::factory()->create(['status' => ArticleStatus::Archived, 'published_at' => now()->subHour()]);
        $future = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->addHour()]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LatestArticles::class)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft, $scheduled, $archived, $future]);
    }

    public function test_latest_articles_ordering()
    {
        $old = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subHours(3)]);
        $middle = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subHours(2)]);
        $newest = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subHours(1)]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(LatestArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertEquals($newest->id, $records[0]->id);
        $this->assertEquals($middle->id, $records[1]->id);
        $this->assertEquals($old->id, $records[2]->id);
    }

    public function test_latest_articles_id_tie_break()
    {
        $time = now()->subHour();
        $article1 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => $time]);
        $article2 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => $time]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(LatestArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertEquals($article2->id, $records[0]->id);
        $this->assertEquals($article1->id, $records[1]->id);
    }

    public function test_latest_articles_five_record_limit()
    {
        Article::factory()->count(7)->create(['status' => ArticleStatus::Published, 'published_at' => now()->subHour()]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(LatestArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertCount(5, $records);
    }

    public function test_latest_articles_empty_state()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LatestArticles::class)
            ->assertCountTableRecords(0)
            ->assertSee('Belum ada berita diterbitkan');
    }

    public function test_latest_articles_database_backed()
    {
        $articleA = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subHours(2)]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(LatestArticles::class);

        $this->assertEquals($articleA->id, $component->instance()->getTable()->getRecords()[0]->id);

        $articleB = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subHour()]);

        $component = Livewire::actingAs($user)
            ->test(LatestArticles::class);

        $this->assertEquals($articleB->id, $component->instance()->getTable()->getRecords()[0]->id);
    }

    public function test_phase_b_semantic_difference()
    {
        $future = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->addDays(1)]);

        $user = User::factory()->create();

        // Should be counted in Status Overview
        $overview = new ArticleStatusOverview;
        $reflection = new \ReflectionClass($overview);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $this->assertEquals(1, $method->invoke($overview)[0]->getValue());

        // Should NOT be seen in Latest Articles
        Livewire::actingAs($user)
            ->test(LatestArticles::class)
            ->assertCanNotSeeTableRecords([$future]);
    }

    public function test_popular_articles_basic_ranking()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $articleA = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->subHours(5), 'views_count' => 15]);

        $articleB = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleB->id, 'period_start' => now()->subHours(5), 'views_count' => 30]);

        $articleC = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleC->id, 'period_start' => now()->subHours(5), 'views_count' => 10]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertEquals($articleB->id, $records[0]->id);
        $this->assertEquals($articleA->id, $records[1]->id);
        $this->assertEquals($articleC->id, $records[2]->id);
    }

    public function test_popular_articles_multiple_period_stats_sum()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $articleA = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->subHours(5), 'views_count' => 10]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->subHours(4), 'views_count' => 15]);

        $articleB = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleB->id, 'period_start' => now()->subHours(5), 'views_count' => 20]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertEquals($articleA->id, $records[0]->id);
        $this->assertEquals(25, $records[0]->period_views);
        $this->assertEquals($articleB->id, $records[1]->id);
    }

    public function test_popular_articles_old_stats_excluded()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $articleA = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->subHours(25), 'views_count' => 1000]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->subHours(2), 'views_count' => 5]);

        $articleB = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleB->id, 'period_start' => now()->subHours(2), 'views_count' => 10]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        // B has 10, A has 5 (the 1000 is ignored)
        $this->assertEquals($articleB->id, $records[0]->id);
        $this->assertEquals($articleA->id, $records[1]->id);
        $this->assertEquals(5, $records[1]->period_views);
    }

    public function test_popular_articles_future_stats_excluded()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $articleA = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->addHour(), 'views_count' => 1000]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->subHour(), 'views_count' => 5]);

        $articleB = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create(['article_id' => $articleB->id, 'period_start' => now()->subHour(), 'views_count' => 10]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertEquals($articleB->id, $records[0]->id);
        $this->assertEquals($articleA->id, $records[1]->id);
        $this->assertEquals(5, $records[1]->period_views);
    }

    public function test_popular_articles_public_status_filter()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $published = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $published->id, 'period_start' => now()->subHour(), 'views_count' => 10]);

        $draft = Article::factory()->create(['status' => ArticleStatus::Draft, 'published_at' => now()->subDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $draft->id, 'period_start' => now()->subHour(), 'views_count' => 100]);

        $scheduled = Article::factory()->create(['status' => ArticleStatus::Scheduled, 'published_at' => now()->addDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $scheduled->id, 'period_start' => now()->subHour(), 'views_count' => 100]);

        $archived = Article::factory()->create(['status' => ArticleStatus::Archived, 'published_at' => now()->subDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $archived->id, 'period_start' => now()->subHour(), 'views_count' => 100]);

        $futurePublished = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->addDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $futurePublished->id, 'period_start' => now()->subHour(), 'views_count' => 100]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft, $scheduled, $archived, $futurePublished]);
    }

    public function test_popular_articles_zero_or_no_period_views()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $noStat = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);

        $zeroStat = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $zeroStat->id, 'period_start' => now()->subHour(), 'views_count' => 0]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->assertCanNotSeeTableRecords([$noStat, $zeroStat]);
    }

    public function test_popular_articles_legacy_views_count_ignored()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $articleA = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1), 'views_count' => 999999]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->subHour(), 'views_count' => 2]);

        $articleB = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1), 'views_count' => 1]);
        ArticleViewStat::factory()->create(['article_id' => $articleB->id, 'period_start' => now()->subHour(), 'views_count' => 20]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertEquals($articleB->id, $records[0]->id);
        $this->assertEquals($articleA->id, $records[1]->id);
    }

    public function test_popular_articles_published_at_tie_break()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $articleOld = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(3)]);
        ArticleViewStat::factory()->create(['article_id' => $articleOld->id, 'period_start' => now()->subHour(), 'views_count' => 10]);

        $articleNew = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $articleNew->id, 'period_start' => now()->subHour(), 'views_count' => 10]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertEquals($articleNew->id, $records[0]->id);
        $this->assertEquals($articleOld->id, $records[1]->id);
    }

    public function test_popular_articles_id_tie_break()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $publishedTime = now()->subDays(1);

        $article1 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => clone $publishedTime]);
        ArticleViewStat::factory()->create(['article_id' => $article1->id, 'period_start' => now()->subHour(), 'views_count' => 10]);

        $article2 = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => clone $publishedTime]);
        ArticleViewStat::factory()->create(['article_id' => $article2->id, 'period_start' => now()->subHour(), 'views_count' => 10]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        // article2 has higher ID
        $this->assertEquals($article2->id, $records[0]->id);
        $this->assertEquals($article1->id, $records[1]->id);
    }

    public function test_popular_articles_five_record_limit()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        for ($i = 1; $i <= 7; $i++) {
            $article = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
            ArticleViewStat::factory()->create(['article_id' => $article->id, 'period_start' => now()->subHour(), 'views_count' => $i]);
        }

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertCount(5, $records);
        // The one with 7 views should be first
        $this->assertEquals(7, $records[0]->period_views);
        // The one with 3 views should be last
        $this->assertEquals(3, $records[4]->period_views);
    }

    public function test_popular_articles_empty_state()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->assertCountTableRecords(0)
            ->assertSee('Belum ada data populer 24 jam terakhir');
    }

    public function test_popular_articles_database_backed_reranking()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $articleA = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $articleA->id, 'period_start' => now()->subHour(), 'views_count' => 10]);

        $articleB = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(1)]);
        ArticleViewStat::factory()->create(['article_id' => $articleB->id, 'period_start' => now()->subHour(), 'views_count' => 5]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(PopularArticles::class);

        $this->assertEquals($articleA->id, $component->instance()->getTable()->getRecords()[0]->id);

        // Add 20 views to B
        ArticleViewStat::factory()->create(['article_id' => $articleB->id, 'period_start' => now()->subMinutes(30), 'views_count' => 20]);

        $component = Livewire::actingAs($user)
            ->test(PopularArticles::class);

        $this->assertEquals($articleB->id, $component->instance()->getTable()->getRecords()[0]->id);
    }

    public function test_dashboard_has_create_article_action()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertActionExists('createArticle');
    }

    public function test_dashboard_create_article_action_has_correct_url()
    {
        $user = User::factory()->create();

        $expectedUrl = ArticleResource::getUrl('create');

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertActionHasUrl('createArticle', $expectedUrl);
    }

    public function test_create_article_shortcut_does_not_create_article_on_render()
    {
        $user = User::factory()->create();

        $initialCount = Article::count();

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);

        $this->assertEquals($initialCount, Article::count());
    }

    public function test_create_article_page_is_accessible_from_url()
    {
        $user = User::factory()->create();
        $url = ArticleResource::getUrl('create');

        $response = $this->actingAs($user)->get($url);
        $response->assertStatus(200);
    }

    public function test_popular_articles_exact_24h_boundary_included()
    {
        $this->travelTo(now()->setTime(12, 0, 0));

        $articleIncluded = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create([
            'article_id' => $articleIncluded->id,
            'period_start' => now()->subHours(24),
            'views_count' => 50,
        ]);

        $articleExcluded = Article::factory()->create(['status' => ArticleStatus::Published, 'published_at' => now()->subDays(2)]);
        ArticleViewStat::factory()->create([
            'article_id' => $articleExcluded->id,
            'period_start' => now()->subHours(24)->subSecond(),
            'views_count' => 100,
        ]);

        $user = User::factory()->create();

        $records = Livewire::actingAs($user)
            ->test(PopularArticles::class)
            ->instance()->getTable()->getRecords();

        $this->assertCount(1, $records);
        $this->assertEquals($articleIncluded->id, $records[0]->id);
        $this->assertEquals(50, $records[0]->period_views);
    }
}
