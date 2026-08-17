<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Models\Article;
use App\Models\Category;
use App\Enums\AdvertisementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisementPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_top_advertisement_resolves_in_homepage_top_slot()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'type' => AdvertisementType::Script,
            'content' => '<!-- top ad -->',
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('data-ad-slot="homepage_top"', false);
        $response->assertSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_homepage_top_does_not_resolve_in_homepage_middle()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'type' => AdvertisementType::Script,
            'content' => '<!-- top ad -->',
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertDontSee('data-ad-slot="homepage_middle"', false);
    }

    public function test_homepage_middle_does_not_resolve_in_homepage_top()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_middle',
            'type' => AdvertisementType::Script,
            'content' => '<!-- middle ad -->',
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
        $response->assertSee('data-ad-slot="homepage_middle"', false);
    }

    public function test_article_inline_does_not_resolve_in_homepage_placement()
    {
        Advertisement::factory()->create([
            'placement_key' => 'article_inline',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="article_inline"', false);
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
    }

    public function test_article_sidebar_does_not_resolve_in_category_sidebar()
    {
        Advertisement::factory()->create([
            'placement_key' => 'article_sidebar',
        ]);

        $category = Category::factory()->create();

        $response = $this->get(route('categories.show', $category));
        $response->assertDontSee('data-ad-slot="article_sidebar"', false);
        $response->assertDontSee('data-ad-slot="category_sidebar"', false);
    }

    public function test_category_sidebar_does_not_leak_into_article_page()
    {
        Advertisement::factory()->create([
            'placement_key' => 'category_sidebar',
        ]);

        $article = Article::factory()->published()->create();

        $response = $this->get(route('articles.show', $article));
        $response->assertDontSee('data-ad-slot="category_sidebar"', false);
    }

    public function test_unsupported_placement_does_not_expose_advertisements()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'unsupported_key',
        ]);

        // Manually use the action to verify it returns empty
        $action = new \App\Actions\Advertisements\GetAdvertisementsForPlacement();
        $ads = $action->execute('unsupported_key');

        $this->assertEmpty($ads);
    }

    public function test_placement_resolver_preserves_ordering()
    {
        $ad1 = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'sort_order' => 10,
            'created_at' => now()->subDays(1),
        ]);
        $ad2 = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'sort_order' => 1,
            'created_at' => now()->subDays(2),
        ]);
        $ad3 = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'sort_order' => 1,
            'created_at' => now(),
        ]);

        $action = new \App\Actions\Advertisements\GetAdvertisementsForPlacement();
        $ads = $action->execute('homepage_top');

        // Order should be: sort_order asc, created_at desc, id desc
        // ad3: sort_order=1, created_at=now
        // ad2: sort_order=1, created_at=subDays(2)
        // ad1: sort_order=10, created_at=subDays(1)

        $this->assertEquals($ad3->id, $ads[0]->id);
        $this->assertEquals($ad2->id, $ads[1]->id);
        $this->assertEquals($ad1->id, $ads[2]->id);
    }

    public function test_empty_placement_renders_without_layout_placeholder_pollution()
    {
        // No ads in db
        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
        $response->assertDontSee('data-ad-slot="homepage_middle"', false);
    }

    public function test_script_advertisement_content_is_not_emitted_raw_by_slot_component()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'type' => AdvertisementType::Script,
            'content' => '<script>alert("ads-placement-xss")</script>',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-slot="homepage_top"', false);
        $response->assertDontSee('<script>alert("ads-placement-xss")</script>', false);
    }

    public function test_homepage_contains_only_required_homepage_slot_markers()
    {
        Advertisement::factory()->create(['placement_key' => 'homepage_top']);
        Advertisement::factory()->create(['placement_key' => 'homepage_middle']);
        Advertisement::factory()->create(['placement_key' => 'article_inline']);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-slot="homepage_top"', false);
        $response->assertSee('data-ad-slot="homepage_middle"', false);
        $response->assertDontSee('data-ad-slot="article_inline"', false);
    }

    public function test_article_detail_contains_only_article_placement_markers()
    {
        Advertisement::factory()->create(['placement_key' => 'article_inline']);
        Advertisement::factory()->create(['placement_key' => 'article_sidebar']);
        Advertisement::factory()->create(['placement_key' => 'homepage_top']);

        $article = Article::factory()->published()->create();

        $response = $this->get(route('articles.show', $article));
        $response->assertSee('data-ad-slot="article_inline"', false);
        $response->assertSee('data-ad-slot="article_sidebar"', false);
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
    }

    public function test_category_page_contains_only_category_sidebar_marker()
    {
        Advertisement::factory()->create(['placement_key' => 'category_sidebar']);
        Advertisement::factory()->create(['placement_key' => 'article_inline']);

        $category = Category::factory()->create();

        $response = $this->get(route('categories.show', $category));
        $response->assertSee('data-ad-slot="category_sidebar"', false);
        $response->assertDontSee('data-ad-slot="article_inline"', false);
    }
}
