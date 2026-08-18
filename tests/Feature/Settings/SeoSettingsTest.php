<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\ManageSeoSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

class SeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);
    }

    public function test_guest_cannot_access_seo_settings_admin_page()
    {
        $response = $this->get('/admin/settings/seo');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_access_seo_settings_admin_page()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/seo');
        $response->assertStatus(200);
    }

    public function test_page_loads_when_no_seo_settings_records_exist()
    {
        $this->assertEquals(0, Setting::count());
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/seo');
        $response->assertStatus(200);
    }

    public function test_opening_page_does_not_create_settings_rows()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin/settings/seo');
        $this->assertEquals(0, Setting::count());
    }

    public function test_admin_can_save_default_seo_title()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_title' => 'Custom Global SEO Title',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $titleSetting = Setting::where('setting_key', 'seo.default_title')->first();
        $this->assertNotNull($titleSetting);
        $this->assertEquals('Custom Global SEO Title', $titleSetting->value);
        $this->assertEquals('seo', $titleSetting->group_name);
        $this->assertEquals('string', $titleSetting->value_type);
    }

    public function test_admin_can_save_default_seo_description()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_description' => 'Custom Global SEO Description that is very interesting.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $descSetting = Setting::where('setting_key', 'seo.default_description')->first();
        $this->assertNotNull($descSetting);
        $this->assertEquals('Custom Global SEO Description that is very interesting.', $descSetting->value);
        $this->assertEquals('seo', $descSetting->group_name);
        $this->assertEquals('string', $descSetting->value_type);
    }

    public function test_saving_title_again_updates_existing_record()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_title' => 'First Title',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'seo.default_title')->count());

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_title' => 'Second Title',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'seo.default_title')->count());
        $this->assertEquals('Second Title', Setting::where('setting_key', 'seo.default_title')->first()->value);
    }

    public function test_saving_description_again_updates_existing_record()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_description' => 'First Description',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'seo.default_description')->count());

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_description' => 'Second Description',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'seo.default_description')->count());
        $this->assertEquals('Second Description', Setting::where('setting_key', 'seo.default_description')->first()->value);
    }

    public function test_oversized_title_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_title' => str_repeat('a', 256),
            ])
            ->call('save')
            ->assertHasFormErrors(['default_title' => 'max']);
    }

    public function test_oversized_description_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_description' => str_repeat('a', 321),
            ])
            ->call('save')
            ->assertHasFormErrors(['default_description' => 'max']);
    }

    public function test_blank_title_allowed()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_title' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_blank_description_allowed()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_description' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_missing_default_title_falls_back_to_sitename_and_tagline()
    {
        $this->assertEquals('TINTAPENA - Menulis Berdasarkan Fakta', SiteSettings::defaultSeoTitle());
    }

    public function test_missing_default_description_falls_back_to_site_name_based_description()
    {
        $this->assertEquals('TINTAPENA adalah portal berita independen yang menyajikan informasi terkini dan terpercaya.', SiteSettings::defaultSeoDescription());
    }

    public function test_configured_general_settings_affect_seo_fallback_when_seo_values_blank()
    {
        Setting::factory()->create(['setting_key' => 'general.site_name', 'group_name' => 'general', 'value' => 'Portal Babel']);
        Setting::factory()->create(['setting_key' => 'general.tagline', 'group_name' => 'general', 'value' => 'Berita Terpercaya']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $this->assertEquals('Portal Babel - Berita Terpercaya', SiteSettings::defaultSeoTitle());
        $this->assertEquals('Portal Babel adalah portal berita independen yang menyajikan informasi terkini dan terpercaya.', SiteSettings::defaultSeoDescription());
    }

    public function test_global_public_layout_uses_configured_default_seo_description_when_page_has_no_specific_meta_description()
    {
        Setting::factory()->create(['setting_key' => 'seo.default_description', 'group_name' => 'seo', 'value' => 'Global SEO Description Override']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertSee('<meta name="description" content="Global SEO Description Override">', false);
    }

    public function test_script_like_default_title_is_escaped_and_not_executable()
    {
        Setting::factory()->create(['setting_key' => 'seo.default_title', 'group_name' => 'seo', 'value' => '</title><script>alert("seo")</script>']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $rendered = Blade::render('@extends("layouts.public")');
        $this->assertStringNotContainsString('</title><script>alert("seo")</script>', $rendered);
        $this->assertStringContainsString('&lt;/title&gt;&lt;script&gt;alert(&quot;seo&quot;)&lt;/script&gt;', $rendered);
    }

    public function test_attribute_script_like_default_description_is_escaped_and_cannot_break_the_meta_element()
    {
        Setting::factory()->create(['setting_key' => 'seo.default_description', 'group_name' => 'seo', 'value' => '"><script>alert("seo")</script>']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $rendered = Blade::render('@extends("layouts.public")');
        $this->assertStringNotContainsString('"><script>alert("seo")</script>', $rendered);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;alert(&quot;seo&quot;)&lt;/script&gt;', $rendered);
    }

    public function test_arbitrary_key_injection_cannot_create_seo_robots()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'seo.robots' => 'noindex, nofollow',
            ])
            ->call('save');

        $this->assertEquals(0, Setting::where('setting_key', 'seo.robots')->count());
    }

    public function test_arbitrary_key_injection_cannot_create_secret_key()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'secret.key' => 'hacked',
            ])
            ->call('save');

        $this->assertEquals(0, Setting::where('setting_key', 'secret.key')->count());
    }

    public function test_no_credential_secret_fields_exist_on_seo_settings_form()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/seo');

        $response->assertDontSee('APP_KEY');
        $response->assertDontSee('DB_PASSWORD');
    }

    public function test_general_settings_remain_unaffected()
    {
        Setting::factory()->create([
            'group_name' => 'general',
            'setting_key' => 'general.site_name',
            'value' => 'Old Site Name',
        ]);

        $admin = User::factory()->create();
        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_title' => 'New Title',
            ])
            ->call('save');

        $this->assertEquals('Old Site Name', Setting::where('setting_key', 'general.site_name')->first()->value);
    }

    public function test_contact_settings_remain_unaffected()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.email',
            'value' => 'old@test.com',
        ]);

        $admin = User::factory()->create();
        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_title' => 'New Title',
            ])
            ->call('save');

        $this->assertEquals('old@test.com', Setting::where('setting_key', 'contact.email')->first()->value);
    }

    public function test_social_settings_remain_unaffected()
    {
        Setting::factory()->create([
            'group_name' => 'social',
            'setting_key' => 'social.instagram',
            'value' => 'https://instagram.com/old',
        ]);

        $admin = User::factory()->create();
        Livewire::actingAs($admin)
            ->test(ManageSeoSettings::class)
            ->fillForm([
                'default_title' => 'New Title',
            ])
            ->call('save');

        $this->assertEquals('https://instagram.com/old', Setting::where('setting_key', 'social.instagram')->first()->value);
    }

    public function test_public_layout_works_when_seo_settings_absent()
    {
        $rendered = Blade::render('@extends("layouts.public")');
        $this->assertStringContainsString('<title>TINTAPENA - Menulis Berdasarkan Fakta</title>', $rendered);
        $this->assertStringContainsString('<meta name="description" content="TINTAPENA adalah portal berita independen yang menyajikan informasi terkini dan terpercaya.">', $rendered);
    }

    public function test_homepage_page_specific_title_overrides_global_default_title()
    {
        Setting::factory()->create(['setting_key' => 'seo.default_title', 'group_name' => 'seo', 'value' => 'GLOBAL TITLE']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Homepage may or may not define a custom @section('title').
        // Let's check what homepage does!
        // Wait, TINTAPENA homepage seems to fall back to the layout title or uses 'TINTAPENA - Berita Terkini' if overridden.
        // I will assert it doesn't see "GLOBAL TITLE" if it overrides it. If it doesn't, it will see "GLOBAL TITLE".
        // The instructions said: "Homepage currently has a page-specific title. That page-specific title should continue to override global default title."

        $response->assertDontSee('<title>GLOBAL TITLE</title>', false);
    }
}
