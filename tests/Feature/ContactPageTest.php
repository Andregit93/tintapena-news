<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_returns_200()
    {
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('Kontak');
    }

    public function test_contact_show_route_name_works()
    {
        $this->assertEquals(url('/kontak'), route('contact.show'));

        $response = $this->get(route('contact.show'));
        $response->assertStatus(200);
    }

    public function test_kontak_resolves_contact_controller_not_page_controller()
    {
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertViewIs('contact.show');
    }

    public function test_published_static_page_with_slug_kontak_cannot_hijack_route()
    {
        Page::factory()->create([
            'slug' => 'kontak',
            'status' => PageStatus::Published->value,
            'title' => 'Fake Kontak Page',
            'content' => 'This should not be seen.',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertDontSee('Fake Kontak Page');
        $response->assertViewIs('contact.show');
    }

    public function test_page_works_when_settings_table_contains_no_contact_records()
    {
        Setting::query()->delete();

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('Informasi kontak belum tersedia saat ini.');
    }

    public function test_contact_email_is_displayed_when_configured()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.email',
            'value' => 'redaksi@tintapena.test',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('redaksi@tintapena.test');
        $response->assertSee('mailto:redaksi@tintapena.test');
    }

    public function test_contact_whatsapp_is_displayed_when_configured()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.whatsapp',
            'value' => '0812-3456-7890',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('0812-3456-7890');
        $response->assertSee('https://wa.me/6281234567890');
    }

    public function test_contact_address_is_displayed_when_configured()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.address',
            'value' => 'Jl. Jenderal Sudirman No. 123, Pangkalpinang',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('Jl. Jenderal Sudirman No. 123, Pangkalpinang');
    }

    public function test_unrelated_settings_are_not_rendered()
    {
        Setting::factory()->create([
            'group_name' => 'general',
            'setting_key' => 'site.name',
            'value' => 'Secret Site Name',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertDontSee('Secret Site Name');
    }

    public function test_html_script_stored_in_contact_settings_is_escaped()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.address',
            'value' => '<script>alert("xss")</script>',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }

    public function test_canonical_points_to_contact_show()
    {
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('<link rel="canonical" href="'.route('contact.show').'">', false);
    }

    public function test_static_page_catch_all_continues_to_work_for_a_normal_published_page()
    {
        $page = Page::factory()->create([
            'slug' => 'tentang-kami',
            'status' => PageStatus::Published->value,
        ]);

        $response = $this->get('/tentang-kami');
        $response->assertStatus(200);
        $response->assertViewIs('pages.show');
        $response->assertSee($page->title);
    }

    public function test_specific_routes_like_terbaru_and_cari_still_win_over_catch_all()
    {
        Page::factory()->create([
            'slug' => 'terbaru',
            'status' => PageStatus::Published->value,
        ]);

        $response = $this->get('/terbaru');
        $response->assertStatus(200);
        $response->assertViewIs('articles.latest');

        Page::factory()->create([
            'slug' => 'cari',
            'status' => PageStatus::Published->value,
        ]);

        $response = $this->get('/cari');
        $response->assertStatus(200);
        $response->assertViewIs('search.index');
    }

    public function test_malformed_email_is_not_turned_into_mailto_link()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.email',
            'value' => 'not-an-email',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('not-an-email');
        $response->assertDontSee('mailto:not-an-email');
    }

    public function test_script_like_email_is_escaped_and_does_not_create_mailto_link()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.email',
            'value' => '<script>alert("xss")</script>',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
        $response->assertDontSee('mailto:');
    }

    public function test_malformed_too_short_whatsapp_does_not_produce_wa_me_link()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.whatsapp',
            'value' => '123',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('123');
        $response->assertDontSee('wa.me');
    }

    public function test_script_like_whatsapp_value_with_digits_does_not_produce_wa_me_link()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.whatsapp',
            'value' => '<script>6281234567890</script>',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertDontSee('<script>6281234567890</script>', false);
        $response->assertSee('&lt;script&gt;6281234567890&lt;/script&gt;', false);
        $response->assertDontSee('wa.me');
    }

    public function test_alphabetic_whatsapp_value_does_not_produce_wa_me_link()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.whatsapp',
            'value' => '62812abc34567890',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('62812abc34567890');
        $response->assertDontSee('wa.me');
    }

    public function test_valid_indonesia_whatsapp_with_spaces_produces_wa_me_link()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.whatsapp',
            'value' => '+62 812 3456 7890',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('+62 812 3456 7890');
        $response->assertSee('https://wa.me/6281234567890');
    }

    public function test_valid_indonesia_whatsapp_with_hyphens_produces_wa_me_link()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.whatsapp',
            'value' => '0812-3456-7890',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('0812-3456-7890');
        $response->assertSee('https://wa.me/6281234567890');
    }
}
