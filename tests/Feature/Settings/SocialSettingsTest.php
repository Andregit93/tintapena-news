<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\ManageSocialSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialSettingsTest extends TestCase
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

    public function test_guest_cannot_access_social_settings_admin_page()
    {
        $response = $this->get('/admin/settings/social');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_access_social_settings_admin_page()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/social');
        $response->assertStatus(200);
    }

    public function test_page_loads_when_no_social_settings_records_exist()
    {
        $this->assertEquals(0, Setting::count());
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/social');
        $response->assertStatus(200);
    }

    public function test_opening_page_does_not_create_settings_rows()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin/settings/social');
        $this->assertEquals(0, Setting::count());
    }

    public function test_admin_can_save_social_instagram()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'https://instagram.com/test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $instagramSetting = Setting::where('setting_key', 'social.instagram')->first();
        $this->assertNotNull($instagramSetting);
        $this->assertEquals('https://instagram.com/test', $instagramSetting->value);
        $this->assertEquals('social', $instagramSetting->group_name);
        $this->assertEquals('string', $instagramSetting->value_type);
    }

    public function test_admin_can_save_social_facebook()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'facebook' => 'https://facebook.com/test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $facebookSetting = Setting::where('setting_key', 'social.facebook')->first();
        $this->assertNotNull($facebookSetting);
        $this->assertEquals('https://facebook.com/test', $facebookSetting->value);
        $this->assertEquals('social', $facebookSetting->group_name);
        $this->assertEquals('string', $facebookSetting->value_type);
    }

    public function test_saving_instagram_again_updates_existing_record()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'https://instagram.com/first',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'social.instagram')->count());

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'https://instagram.com/second',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'social.instagram')->count());
        $this->assertEquals('https://instagram.com/second', Setting::where('setting_key', 'social.instagram')->first()->value);
    }

    public function test_saving_facebook_again_updates_existing_record()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'facebook' => 'https://facebook.com/first',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'social.facebook')->count());

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'facebook' => 'https://facebook.com/second',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'social.facebook')->count());
        $this->assertEquals('https://facebook.com/second', Setting::where('setting_key', 'social.facebook')->first()->value);
    }

    public function test_valid_https_instagram_url_accepted()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'https://www.instagram.com/valid',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_valid_https_facebook_url_accepted()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'facebook' => 'https://www.facebook.com/valid',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_valid_http_url_behavior_follows_chosen_rule()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'http://instagram.com/valid',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // The stored value is HTTP. The public layout SiteSettings validation handles HTTP/HTTPS.
        $this->assertEquals('http://instagram.com/valid', Setting::where('setting_key', 'social.instagram')->first()->value);
    }

    public function test_instagram_rejects_ftp_and_does_not_persist()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'ftp://example.com/profile',
            ])
            ->call('save')
            ->assertHasFormErrors(['instagram' => 'url']);

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'social.instagram',
            'value' => 'ftp://example.com/profile',
        ]);
    }

    public function test_facebook_rejects_ftp_and_does_not_persist()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'facebook' => 'ftp://example.com/profile',
            ])
            ->call('save')
            ->assertHasFormErrors(['facebook' => 'url']);

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'social.facebook',
            'value' => 'ftp://example.com/profile',
        ]);
    }

    public function test_malformed_url_rejected_and_does_not_persist()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'not-a-url',
            ])
            ->call('save')
            ->assertHasFormErrors(['instagram' => 'url']);

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'social.instagram',
            'value' => 'not-a-url',
        ]);
    }

    public function test_javascript_url_rejected_and_does_not_persist()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'javascript:alert(1)',
            ])
            ->call('save')
            ->assertHasFormErrors(['instagram' => 'url']);

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'social.instagram',
            'value' => 'javascript:alert(1)',
        ]);
    }

    public function test_data_url_rejected_and_does_not_persist()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'data:text/html,<script>alert(1)</script>',
            ])
            ->call('save')
            ->assertHasFormErrors(['instagram' => 'url']);

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'social.instagram',
            'value' => 'data:text/html,<script>alert(1)</script>',
        ]);
    }

    public function test_file_url_rejected_and_does_not_persist()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'file:///tmp/test',
            ])
            ->call('save')
            ->assertHasFormErrors(['instagram' => 'url']);

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'social.instagram',
            'value' => 'file:///tmp/test',
        ]);
    }

    public function test_mailto_url_rejected_and_does_not_persist()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'mailto:test@example.com',
            ])
            ->call('save')
            ->assertHasFormErrors(['instagram' => 'url']);

        $this->assertDatabaseMissing('settings', [
            'setting_key' => 'social.instagram',
            'value' => 'mailto:test@example.com',
        ]);
    }

    public function test_oversized_instagram_url_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'https://instagram.com/'.str_repeat('a', 256),
            ])
            ->call('save')
            ->assertHasFormErrors(['instagram' => 'max']);
    }

    public function test_oversized_facebook_url_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'facebook' => 'https://facebook.com/'.str_repeat('a', 256),
            ])
            ->call('save')
            ->assertHasFormErrors(['facebook' => 'max']);
    }

    public function test_blank_instagram_is_allowed()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_blank_facebook_is_allowed()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'facebook' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_clearing_instagram_removes_public_instagram_link_behavior()
    {
        Setting::factory()->create(['setting_key' => 'social.instagram', 'group_name' => 'social', 'value' => 'https://instagram.com/test']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertSee('https://instagram.com/test');

        $admin = User::factory()->create();
        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => '',
            ])
            ->call('save');

        $property->setValue(null);

        $response = $this->get('/');
        $response->assertDontSee('https://instagram.com/test');
    }

    public function test_clearing_facebook_removes_public_facebook_link_behavior()
    {
        Setting::factory()->create(['setting_key' => 'social.facebook', 'group_name' => 'social', 'value' => 'https://facebook.com/test']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertSee('https://facebook.com/test');

        $admin = User::factory()->create();
        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'facebook' => '',
            ])
            ->call('save');

        $property->setValue(null);

        $response = $this->get('/');
        $response->assertDontSee('https://facebook.com/test');
    }

    public function test_configured_instagram_url_is_used_by_public_website()
    {
        Setting::factory()->create(['setting_key' => 'social.instagram', 'group_name' => 'social', 'value' => 'https://instagram.com/real']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertSee('https://instagram.com/real');
    }

    public function test_configured_facebook_url_is_used_by_public_website()
    {
        Setting::factory()->create(['setting_key' => 'social.facebook', 'group_name' => 'social', 'value' => 'https://facebook.com/real']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertSee('https://facebook.com/real');
    }

    public function test_public_instagram_link_has_correct_href()
    {
        Setting::factory()->create(['setting_key' => 'social.instagram', 'group_name' => 'social', 'value' => 'https://instagram.com/test']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertSee('href="https://instagram.com/test"', false);
    }

    public function test_public_facebook_link_has_correct_href()
    {
        Setting::factory()->create(['setting_key' => 'social.facebook', 'group_name' => 'social', 'value' => 'https://facebook.com/test']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertSee('href="https://facebook.com/test"', false);
    }

    public function test_external_links_include_safe_target_rel_behavior()
    {
        Setting::factory()->create(['setting_key' => 'social.instagram', 'group_name' => 'social', 'value' => 'https://instagram.com/test']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_malformed_instagram_value_inserted_directly_does_not_produce_unsafe_public_href()
    {
        Setting::factory()->create(['setting_key' => 'social.instagram', 'group_name' => 'social', 'value' => 'javascript:alert(1)']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertDontSee('href="javascript:alert(1)"', false);
    }

    public function test_malformed_facebook_value_inserted_directly_does_not_produce_unsafe_public_href()
    {
        Setting::factory()->create(['setting_key' => 'social.facebook', 'group_name' => 'social', 'value' => 'javascript:alert(1)']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertDontSee('href="javascript:alert(1)"', false);
    }

    public function test_script_like_stored_social_value_is_not_rendered_as_executable_html()
    {
        Setting::factory()->create(['setting_key' => 'social.instagram', 'group_name' => 'social', 'value' => '<script>alert("xss")</script>']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_unrelated_settings_are_not_accidentally_rendered_as_social_urls()
    {
        Setting::factory()->create(['setting_key' => 'contact.email', 'group_name' => 'contact', 'value' => 'secret@test.com']);

        $reflection = new \ReflectionClass(SiteSettings::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);
        $property->setValue(null);

        $response = $this->get('/');
        // The homepage has no reason to display contact.email randomly as a social link
        // We'll just assert it's not present as a social link href.
        $response->assertDontSee('href="secret@test.com"', false);
    }

    public function test_arbitrary_key_injection_cannot_create_social_tiktok()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'social.tiktok' => 'https://tiktok.com',
            ])
            ->call('save');

        $this->assertEquals(0, Setting::where('setting_key', 'social.tiktok')->count());
    }

    public function test_arbitrary_key_injection_cannot_create_secret_key()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'secret.key' => 'hacked',
            ])
            ->call('save');

        $this->assertEquals(0, Setting::where('setting_key', 'secret.key')->count());
    }

    public function test_no_credential_secret_fields_exist_on_social_settings_form()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/social');

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
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'https://instagram.com/test',
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
            ->test(ManageSocialSettings::class)
            ->fillForm([
                'instagram' => 'https://instagram.com/test',
            ])
            ->call('save');

        $this->assertEquals('old@test.com', Setting::where('setting_key', 'contact.email')->first()->value);
    }

    public function test_public_homepage_remains_200()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_public_layout_works_when_both_social_values_are_absent()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        // The social link block should not be rendered, or rendered empty without crashing
        $response->assertDontSee('aria-label="Instagram"');
        $response->assertDontSee('aria-label="Facebook"');
    }

    public function test_public_layout_works_when_only_instagram_exists()
    {
        Setting::factory()->create(['setting_key' => 'social.instagram', 'group_name' => 'social', 'value' => 'https://instagram.com/test']);
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('aria-label="Instagram"', false);
        $response->assertDontSee('aria-label="Facebook"', false);
    }

    public function test_public_layout_works_when_only_facebook_exists()
    {
        Setting::factory()->create(['setting_key' => 'social.facebook', 'group_name' => 'social', 'value' => 'https://facebook.com/test']);
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertDontSee('aria-label="Instagram"', false);
        $response->assertSee('aria-label="Facebook"', false);
    }
}
