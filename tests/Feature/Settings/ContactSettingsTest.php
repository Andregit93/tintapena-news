<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\ManageContactSettings;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_contact_settings_admin_page()
    {
        $response = $this->get('/admin/settings/contact');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_can_access_contact_settings_admin_page()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/contact');
        $response->assertStatus(200);
    }

    public function test_page_loads_when_no_contact_settings_records_exist()
    {
        $this->assertEquals(0, Setting::count());
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/contact');
        $response->assertStatus(200);
    }

    public function test_opening_page_does_not_create_settings_rows()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin/settings/contact');
        $this->assertEquals(0, Setting::count());
    }

    public function test_admin_can_save_contact_settings()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'redaksi@example.com',
                'whatsapp' => '+62 812 3456 7890',
                'address' => 'Jl. Kebon Jeruk No 1',
                'hours' => 'Senin–Jumat, 08.00–17.00 WIB',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Verify email
        $emailSetting = Setting::where('setting_key', 'contact.email')->first();
        $this->assertNotNull($emailSetting);
        $this->assertEquals('redaksi@example.com', $emailSetting->value);
        $this->assertEquals('contact', $emailSetting->group_name);
        $this->assertEquals('string', $emailSetting->value_type);

        // Verify whatsapp
        $waSetting = Setting::where('setting_key', 'contact.whatsapp')->first();
        $this->assertNotNull($waSetting);
        $this->assertEquals('+62 812 3456 7890', $waSetting->value);
        $this->assertEquals('contact', $waSetting->group_name);
        $this->assertEquals('string', $waSetting->value_type);

        // Verify address
        $addressSetting = Setting::where('setting_key', 'contact.address')->first();
        $this->assertNotNull($addressSetting);
        $this->assertEquals('Jl. Kebon Jeruk No 1', $addressSetting->value);
        $this->assertEquals('contact', $addressSetting->group_name);
        $this->assertEquals('string', $addressSetting->value_type);

        // Verify hours
        $hoursSetting = Setting::where('setting_key', 'contact.hours')->first();
        $this->assertNotNull($hoursSetting);
        $this->assertEquals('Senin–Jumat, 08.00–17.00 WIB', $hoursSetting->value);
        $this->assertEquals('contact', $hoursSetting->group_name);
        $this->assertEquals('string', $hoursSetting->value_type);
    }

    public function test_saving_again_updates_existing_contact_email_instead_of_creating_duplicate()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'first@example.com',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'contact.email')->count());

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'second@example.com',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'contact.email')->count());
        $this->assertEquals('second@example.com', Setting::where('setting_key', 'contact.email')->first()->value);
    }

    public function test_setting_key_uniqueness_remains_respected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'first@example.com',
                'whatsapp' => '0812',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'contact.email')->count());
        $this->assertEquals(1, Setting::where('setting_key', 'contact.whatsapp')->count());

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'second@example.com',
                'whatsapp' => '0813',
            ])
            ->call('save');

        $this->assertEquals(1, Setting::where('setting_key', 'contact.email')->count());
        $this->assertEquals(1, Setting::where('setting_key', 'contact.whatsapp')->count());
    }

    public function test_valid_email_is_accepted()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'valid@test.com',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_malformed_email_is_rejected_when_supplied()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'not-an-email',
            ])
            ->call('save')
            ->assertHasFormErrors(['email' => 'email']);
    }

    public function test_oversized_email_is_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => str_repeat('a', 256).'@test.com',
            ])
            ->call('save')
            ->assertHasFormErrors(['email' => 'max']);
    }

    public function test_oversized_whatsapp_is_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'whatsapp' => str_repeat('1', 256),
            ])
            ->call('save')
            ->assertHasFormErrors(['whatsapp' => 'max']);
    }

    public function test_oversized_address_is_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'address' => str_repeat('a', 1001),
            ])
            ->call('save')
            ->assertHasFormErrors(['address' => 'max']);
    }

    public function test_oversized_contact_hours_is_rejected()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'hours' => str_repeat('a', 256),
            ])
            ->call('save')
            ->assertHasFormErrors(['hours' => 'max']);
    }

    public function test_normal_indonesian_whatsapp_formatting_can_be_stored()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'whatsapp' => '+62 812-3456-7890',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('+62 812-3456-7890', Setting::where('setting_key', 'contact.whatsapp')->first()->value);
    }

    public function test_page_works_with_blank_optional_fields()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => '',
                'whatsapp' => '',
                'address' => '',
                'hours' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assuming blank becomes empty string because of the ?? '' fallback
        $this->assertEquals('', Setting::where('setting_key', 'contact.email')->first()->value);
    }

    public function test_public_kontak_displays_configured_contact_email()
    {
        Setting::factory()->create(['setting_key' => 'contact.email', 'group_name' => 'contact', 'value' => 'custom@redaksi.com']);
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('custom@redaksi.com');
    }

    public function test_public_kontak_displays_configured_contact_whatsapp()
    {
        Setting::factory()->create(['setting_key' => 'contact.whatsapp', 'group_name' => 'contact', 'value' => '+6289999999']);
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('+6289999999');
    }

    public function test_public_kontak_displays_configured_contact_address()
    {
        Setting::factory()->create(['setting_key' => 'contact.address', 'group_name' => 'contact', 'value' => 'Jl. Merdeka 123']);
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('Jl. Merdeka 123');
    }

    public function test_public_kontak_displays_configured_contact_hours()
    {
        Setting::factory()->create(['setting_key' => 'contact.hours', 'group_name' => 'contact', 'value' => 'Selalu Buka']);
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('Selalu Buka');
    }

    public function test_configured_valid_email_still_produces_safe_mailto_behavior()
    {
        Setting::factory()->create(['setting_key' => 'contact.email', 'group_name' => 'contact', 'value' => 'test@example.com']);
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('mailto:test@example.com');
    }

    public function test_configured_valid_whatsapp_still_produces_correct_safe_wa_me_normalization()
    {
        Setting::factory()->create(['setting_key' => 'contact.whatsapp', 'group_name' => 'contact', 'value' => '0812-3456-7890']);
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        // The public view already handles conversion to wa.me/6281234567890
        $response->assertSee('wa.me/6281234567890');
    }

    public function test_malformed_stored_whatsapp_does_not_create_unsafe_wa_me_link()
    {
        Setting::factory()->create(['setting_key' => 'contact.whatsapp', 'group_name' => 'contact', 'value' => 'javascript:alert(1)']);
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        // The view logic protects against invalid numbers
        $response->assertDontSee('wa.me/javascript');
    }

    public function test_script_like_contact_settings_remain_escaped_on_kontak()
    {
        Setting::factory()->create(['setting_key' => 'contact.address', 'group_name' => 'contact', 'value' => '<script>alert("xss")</script>']);
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }

    public function test_unrelated_settings_are_not_rendered_by_kontak()
    {
        Setting::factory()->create(['setting_key' => 'general.site_name', 'group_name' => 'general', 'value' => 'UNRELATED_SECRET']);
        $response = $this->get('/kontak');
        $response->assertDontSee('UNRELATED_SECRET');
    }

    public function test_no_secret_credential_fields_exist_on_contact_settings_form()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/admin/settings/contact');

        $response->assertDontSee('APP_KEY');
        $response->assertDontSee('DB_PASSWORD');
        $response->assertDontSee('API_SECRET');
    }

    public function test_arbitrary_setting_key_creation_through_contact_settings_flow_is_impossible()
    {
        $admin = User::factory()->create();

        // Try to inject an arbitrary setting through the form data
        Livewire::actingAs($admin)
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'valid@test.com',
                'secret.key' => 'hacked',
            ])
            ->call('save');

        // Should ignore 'secret.key'
        $this->assertEquals(0, Setting::where('setting_key', 'secret.key')->count());
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
            ->test(ManageContactSettings::class)
            ->fillForm([
                'email' => 'new@test.com',
            ])
            ->call('save');

        $this->assertEquals('Old Site Name', Setting::where('setting_key', 'general.site_name')->first()->value);
    }

    public function test_kontak_get_remains_200()
    {
        $response = $this->get('/kontak');
        $response->assertStatus(200);
    }

    public function test_existing_contact_form_post_behavior_remains_functional()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there',
        ]);

        // Valid form submission redirects somewhere (302) with success session message
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, ContactMessage::count());
    }
}
