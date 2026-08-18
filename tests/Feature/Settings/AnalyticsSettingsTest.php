<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\ManageAnalyticsSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $property = new \ReflectionProperty(SiteSettings::class, 'settings');
        $property->setAccessible(true);
        $property->setValue(null);
    }

    public function test_guest_cannot_access_analytics_settings()
    {
        $response = $this->get('/admin/settings/analytics');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_access()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/analytics');
        $response->assertStatus(200);
    }

    public function test_page_works_with_zero_analytics_rows()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/analytics');
        $response->assertStatus(200);
        $this->assertDatabaseMissing('settings', ['group_name' => 'analytics']);
    }

    public function test_opening_page_does_not_create_settings_row()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin/settings/analytics');
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_admin_can_save_valid_measurement_id()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'G-PSW1MY7HB4',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', [
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'G-PSW1MY7HB4',
        ]);
    }

    public function test_repeated_save_updates_existing_row()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'G-PSW1MY7HB4',
            ])
            ->call('save');

        $this->assertDatabaseCount('settings', 1);

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'G-ABC123',
            ])
            ->call('save');

        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseHas('settings', [
            'setting_key' => 'analytics.google_measurement_id',
            'value' => 'G-ABC123',
        ]);
    }

    public function test_blank_value_allowed()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('settings', [
            'setting_key' => 'analytics.google_measurement_id',
            'value' => null,
        ]);
    }

    public function test_rejects_ua_id()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'UA-12345678-1',
            ])
            ->call('save')
            ->assertHasFormErrors(['google_measurement_id']);
    }

    public function test_rejects_aw_id()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'AW-123456',
            ])
            ->call('save')
            ->assertHasFormErrors(['google_measurement_id']);
    }

    public function test_rejects_gtm_id()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'GTM-ABC123',
            ])
            ->call('save')
            ->assertHasFormErrors(['google_measurement_id']);
    }

    public function test_rejects_bare_g()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'G-',
            ])
            ->call('save')
            ->assertHasFormErrors(['google_measurement_id']);
    }

    public function test_rejects_lowercase_g()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'g-abc123',
            ])
            ->call('save')
            ->assertHasFormErrors(['google_measurement_id']);
    }

    public function test_rejects_script_like_input()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => '<script>alert(1)</script>',
            ])
            ->call('save')
            ->assertHasFormErrors(['google_measurement_id']);
    }

    public function test_rejects_oversized_input()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => 'G-'.str_repeat('A', 50),
            ])
            ->call('save')
            ->assertHasFormErrors(['google_measurement_id']);
    }

    public function test_site_settings_google_measurement_id_returns_configured_valid_value()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'G-PSW1MY7HB4',
        ]);

        $this->assertEquals('G-PSW1MY7HB4', SiteSettings::googleMeasurementId());
    }

    public function test_missing_id_returns_null()
    {
        $this->assertNull(SiteSettings::googleMeasurementId());
    }

    public function test_blank_id_returns_null()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => '',
        ]);

        $this->assertNull(SiteSettings::googleMeasurementId());
    }

    public function test_invalid_value_inserted_directly_into_db_returns_null()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'UA-12345',
        ]);

        $this->assertNull(SiteSettings::googleMeasurementId());
    }

    public function test_malicious_db_value_returns_null()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'G-ABC"><script>alert("analytics")</script>',
        ]);

        $this->assertNull(SiteSettings::googleMeasurementId());
    }

    public function test_valid_id_renders_external_gtag_js_script_publicly()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'G-PSW1MY7HB4',
        ]);

        $rendered = Blade::render('@extends("layouts.public")');

        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-PSW1MY7HB4', $rendered);
        $this->assertStringContainsString('gtag(\'config\', \'G-PSW1MY7HB4\');', $rendered);
    }

    public function test_no_setting_row_means_analytics_script_is_absent()
    {
        $rendered = Blade::render('@extends("layouts.public")');

        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $rendered);
        $this->assertStringNotContainsString('gtag(\'config\'', $rendered);
    }

    public function test_blank_id_means_analytics_script_is_absent()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => '',
        ]);

        $rendered = Blade::render('@extends("layouts.public")');

        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $rendered);
    }

    public function test_invalid_direct_db_id_means_analytics_script_is_absent()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'UA-12345',
        ]);

        $rendered = Blade::render('@extends("layouts.public")');

        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $rendered);
    }

    public function test_malicious_direct_db_id_does_not_appear_in_rendered_script()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'G-ABC"><script>alert("analytics")</script>',
        ]);

        $rendered = Blade::render('@extends("layouts.public")');

        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $rendered);
        $this->assertStringNotContainsString('<script>alert("analytics")</script>', $rendered);
    }

    public function test_clearing_configured_id_removes_analytics_script()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'G-OLD123',
        ]);

        $rendered = Blade::render('@extends("layouts.public")');
        $this->assertStringContainsString('G-OLD123', $rendered);

        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->fillForm([
                'google_measurement_id' => '',
            ])
            ->call('save');

        $property = new \ReflectionProperty(SiteSettings::class, 'settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $renderedAfter = Blade::render('@extends("layouts.public")');
        $this->assertStringNotContainsString('G-OLD123', $renderedAfter);
        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $renderedAfter);
    }

    public function test_arbitrary_key_injection_cannot_create_analytics_api_secret()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageAnalyticsSettings::class)
            ->set('data.api_secret', 'secret-value')
            ->call('save');

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'analytics.api_secret',
        ]);
    }

    public function test_admin_page_does_not_render_public_google_tag_snippet()
    {
        Setting::factory()->create([
            'group_name' => 'analytics',
            'setting_key' => 'analytics.google_measurement_id',
            'value_type' => 'string',
            'value' => 'G-PSW1MY7HB4',
        ]);

        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/analytics');
        $response->assertDontSee('https://www.googletagmanager.com/gtag/js?id=G-PSW1MY7HB4');
    }
}
