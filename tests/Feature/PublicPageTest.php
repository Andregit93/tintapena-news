<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_returns_200()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'about-us',
        ]);

        $response = $this->get('/about-us');

        $response->assertStatus(200);
    }

    public function test_published_page_displays_correct_title()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'about-us',
            'title' => 'Tinta Pena Story',
        ]);

        $response = $this->get('/about-us');

        $response->assertSee('Tinta Pena Story');
    }

    public function test_published_page_displays_safe_rich_content()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'about-us',
            'content' => '<p><strong>Our story</strong> is amazing.</p>',
        ]);

        $response = $this->get('/about-us');

        $response->assertSee('<p><strong>Our story</strong> is amazing.</p>', false);
    }

    public function test_draft_page_returns_404()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Draft->value,
            'slug' => 'draft-page',
        ]);

        $response = $this->get('/draft-page');

        $response->assertStatus(404);
    }

    public function test_unknown_slug_returns_404()
    {
        $response = $this->get('/this-does-not-exist-at-all');

        $response->assertStatus(404);
    }

    public function test_seo_title_is_used_when_present()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'seo-test',
            'title' => 'Normal Title',
            'seo_title' => 'SEO Title Optimized',
        ]);

        $response = $this->get('/seo-test');

        $response->assertSee('<title>SEO Title Optimized', false);
    }

    public function test_page_title_is_seo_title_fallback_when_seo_title_blank()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'no-seo-test',
            'title' => 'Normal Title Fallback',
            'seo_title' => null,
        ]);

        $response = $this->get('/no-seo-test');

        $response->assertSee('<title>Normal Title Fallback', false);
    }

    public function test_meta_description_used_when_present()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'meta-test',
            'meta_description' => 'This is a custom meta description for testing.',
        ]);

        $response = $this->get('/meta-test');

        $response->assertSee('This is a custom meta description for testing.', false);
    }

    public function test_meta_description_fallback_derives_from_content_when_blank()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'meta-fallback-test',
            'content' => '<p>This is the <strong>content</strong> of the page that should be used as fallback meta.</p>',
            'meta_description' => null,
        ]);

        $response = $this->get('/meta-fallback-test');

        $response->assertSee('This is the content of the page that should be used as fallback meta.', false);
    }

    public function test_canonical_url_points_to_current_page_route()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'canonical-test',
        ]);

        $response = $this->get('/canonical-test');
        $canonical = route('pages.show', ['slug' => 'canonical-test']);

        $response->assertSee('<link rel="canonical" href="'.$canonical.'"', false);
    }

    public function test_malicious_script_in_page_content_is_removed()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'script-test',
            'content' => '<p>Hello <script>alert("xss")</script>World</p>',
        ]);

        $response = $this->get('/script-test');

        $response->assertDontSee('<script>', false);
        $response->assertDontSee('alert("xss")', false);
        $response->assertSee('Hello World', false);
    }

    public function test_event_handler_markup_such_as_onerror_is_removed()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'event-test',
            'content' => '<p>Hello <img src="x" onerror="alert(1)">World</p>',
        ]);

        $response = $this->get('/event-test');

        $response->assertDontSee('onerror', false);
        $response->assertSee('<img src="x" />', false);
    }

    public function test_javascript_url_is_removed_or_sanitized()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'js-url-test',
            'content' => '<p>Hello <a href="javascript:alert(1)">World</a></p>',
        ]);

        $response = $this->get('/js-url-test');

        $response->assertDontSee('javascript:alert(1)', false);
    }

    public function test_safe_rich_text_remains_visible()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'safe-rich-test',
            'content' => '<p><strong>Bold</strong> and <em>Italic</em> and <a href="https://google.com">Link</a></p>',
        ]);

        $response = $this->get('/safe-rich-test');

        $response->assertSee('<strong>Bold</strong>', false);
        $response->assertSee('<em>Italic</em>', false);
        $response->assertSee('<a href="https://google.com">Link</a>', false);
    }

    public function test_terbaru_still_resolves_latest_route_behavior()
    {
        $response = $this->get('/terbaru');
        // Check it does not hit PageController by asserting it hits articles.latest view
        $response->assertStatus(200);
        $response->assertViewIs('articles.latest');
    }

    public function test_terpopuler_still_resolves_popular_route_behavior()
    {
        $response = $this->get('/terpopuler');
        $response->assertStatus(200);
        $response->assertViewIs('articles.popular');
    }

    public function test_cari_still_resolves_search_route_behavior()
    {
        $response = $this->get('/cari');
        $response->assertStatus(200);
        $response->assertViewIs('search.index'); // assuming /cari uses search.index
    }

    public function test_a_database_page_with_slug_terbaru_cannot_hijack_terbaru()
    {
        Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'terbaru',
            'title' => 'Malicious Hijack',
        ]);

        $response = $this->get('/terbaru');
        $response->assertStatus(200);
        $response->assertDontSee('Malicious Hijack');
    }

    public function test_a_database_page_with_slug_cari_cannot_hijack_cari()
    {
        Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'cari',
            'title' => 'Malicious Hijack',
        ]);

        $response = $this->get('/cari');
        $response->assertStatus(200);
        $response->assertDontSee('Malicious Hijack');
    }

    public function test_reserved_future_kontak_does_not_resolve_through_pagecontroller()
    {
        Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'kontak',
            'title' => 'Static Kontak Hijack',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertViewIs('contact.show');
        $response->assertDontSee('Static Kontak Hijack');
    }

    public function test_catch_all_page_route_is_effectively_after_specific_public_routes()
    {
        $page = Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'normal-page',
        ]);

        $response = $this->get('/normal-page');
        $response->assertStatus(200);
    }

    public function test_malicious_seo_title_cannot_inject_script_into_head()
    {
        Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'seo-xss-test',
            'seo_title' => 'Bad </title><script>alert("seo-xss")</script>',
        ]);

        $response = $this->get('/seo-xss-test');

        $response->assertDontSee('<script>alert("seo-xss")</script>', false);
        $response->assertSee(e(e('Bad </title><script>alert("seo-xss")</script>')), false);
    }

    public function test_malicious_meta_description_cannot_break_attribute_or_inject_script()
    {
        Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'meta-xss-test',
            'meta_description' => '"><script>alert("meta-xss")</script>',
        ]);

        $response = $this->get('/meta-xss-test');

        $response->assertDontSee('"><script>alert("meta-xss")</script>', false);
        $response->assertSee(e(e('"><script>alert("meta-xss")</script>')), false);
    }

    public function test_meta_description_fallback_from_content_is_safely_stripped_of_scripts()
    {
        Page::factory()->create([
            'status' => PageStatus::Published->value,
            'slug' => 'meta-fallback-script-test',
            'content' => '<p>Hello<script>alert("meta-xss")</script>World</p>',
            'meta_description' => null,
        ]);

        $response = $this->get('/meta-fallback-script-test');

        // Body sanitization removes the script entirely, so strip_tags only sees "HelloWorld"
        $response->assertDontSee('alert("meta-xss")', false);
        $response->assertDontSee('<script>', false);

        // Ensure the meta description fallback safely contains just the text
        $response->assertSee('HelloWorld', false);
    }
}
