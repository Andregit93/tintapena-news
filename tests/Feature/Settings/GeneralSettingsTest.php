<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\ManageGeneralSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_general_settings_admin_page()
    {
        $response = $this->get('/admin/settings/general');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_access_it()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/general');
        $response->assertStatus(200);
    }

    public function test_page_loads_when_no_general_settings_records_exist()
    {
        $this->assertEquals(0, Setting::count());
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/general');
        $response->assertStatus(200);
    }

    public function test_opening_page_does_not_create_settings_rows()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin/settings/general');
        $this->assertEquals(0, Setting::count());
    }

    public function test_admin_can_save_site_name_and_tagline()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageGeneralSettings::class)
            ->fillForm([
                'site_name' => 'My Cool Site',
                'tagline' => 'The best site ever',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $siteNameSetting = Setting::where('setting_key', 'general.site_name')->first();
        $this->assertNotNull($siteNameSetting);
        $this->assertEquals('My Cool Site', $siteNameSetting->value);
        $this->assertEquals('general', $siteNameSetting->group_name);
        $this->assertEquals('string', $siteNameSetting->value_type);

        $taglineSetting = Setting::where('setting_key', 'general.tagline')->first();
        $this->assertNotNull($taglineSetting);
        $this->assertEquals('The best site ever', $taglineSetting->value);
        $this->assertEquals('general', $taglineSetting->group_name);
        $this->assertEquals('string', $taglineSetting->value_type);
    }

    public function test_saving_again_updates_existing_record_instead_of_creating_duplicate()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageGeneralSettings::class)
            ->fillForm([
                'site_name' => 'First Name',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'general.site_name')->count());

        Livewire::actingAs($admin)
            ->test(ManageGeneralSettings::class)
            ->fillForm([
                'site_name' => 'Second Name',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'general.site_name')->count());
        $this->assertEquals('Second Name', Setting::where('setting_key', 'general.site_name')->first()->value);
    }

    public function test_site_name_is_required()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageGeneralSettings::class)
            ->fillForm([
                'site_name' => '',
            ])
            ->call('save')
            ->assertHasFormErrors(['site_name' => 'required']);
    }

    public function test_oversized_site_name_is_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageGeneralSettings::class)
            ->fillForm([
                'site_name' => str_repeat('a', 151),
            ])
            ->call('save')
            ->assertHasFormErrors(['site_name' => 'max']);
    }

    public function test_oversized_tagline_is_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageGeneralSettings::class)
            ->fillForm([
                'site_name' => 'Site',
                'tagline' => str_repeat('a', 256),
            ])
            ->call('save')
            ->assertHasFormErrors(['tagline' => 'max']);
    }

    public function test_public_layout_uses_configured_site_name_and_tagline()
    {
        // Force the SiteSettings to reload by clearing its static state
        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        Setting::factory()->create([
            'group_name' => 'general',
            'setting_key' => 'general.site_name',
            'value' => 'Custom Site Name',
        ]);

        Setting::factory()->create([
            'group_name' => 'general',
            'setting_key' => 'general.tagline',
            'value' => 'Custom Tagline',
        ]);

        // Clear again to ensure it picks up the DB rows we just created
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Custom Site Name');
        $response->assertSee('Custom Tagline');
    }

    public function test_missing_settings_fall_back_to_defaults()
    {
        // Force reload
        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('TINTAPENA');
        $response->assertSee('Menulis Berdasarkan Fakta');
    }

    public function test_blank_tagline_fallback_behaves_according_to_rule()
    {
        Setting::factory()->create([
            'group_name' => 'general',
            'setting_key' => 'general.tagline',
            'value' => '', // blank
        ]);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertStatus(200);
        // Fallback rule states: "Menulis Berdasarkan Fakta" if absent/blank
        $response->assertSee('Menulis Berdasarkan Fakta');
    }

    public function test_script_like_site_name_and_tagline_are_escaped_on_public_website()
    {
        Setting::factory()->create([
            'group_name' => 'general',
            'setting_key' => 'general.site_name',
            'value' => '<script>alert("site")</script>',
        ]);

        Setting::factory()->create([
            'group_name' => 'general',
            'setting_key' => 'general.tagline',
            'value' => '<script>alert("tagline")</script>',
        ]);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertDontSee('<script>alert("site")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;site&quot;)&lt;/script&gt;', false);

        $response->assertDontSee('<script>alert("tagline")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;tagline&quot;)&lt;/script&gt;', false);
    }

    public function test_unrelated_settings_are_not_accidentally_rendered()
    {
        Setting::factory()->create([
            'group_name' => 'secret',
            'setting_key' => 'secret.key',
            'value' => 'SUPER_SECRET_123',
        ]);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertDontSee('SUPER_SECRET_123');
    }

    public function test_contact_settings_remain_unaffected()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.email',
            'value' => 'test@tintapena.test',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('test@tintapena.test');
    }

    public function test_no_secret_credential_fields_exist_on_general_settings_form()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/general');

        $response->assertDontSee('APP_KEY');
        $response->assertDontSee('DB_PASSWORD');
        $response->assertDontSee('API_SECRET');
    }

    public function test_no_arbitrary_setting_key_creation_through_general_settings_flow()
    {
        $admin = User::factory()->create();

        // Try to inject an arbitrary setting through the form data
        Livewire::actingAs($admin)
            ->test(ManageGeneralSettings::class)
            ->fillForm([
                'site_name' => 'Site',
                'secret.key' => 'hacked',
            ])
            ->call('save');

        // Should ignore 'secret.key'
        $this->assertEquals(0, Setting::where('setting_key', 'secret.key')->count());
    }
}
