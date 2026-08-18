<?php

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Enums\PageStatus;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_kontak_route_exists()
    {
        $response = $this->post('/kontak', []);
        // Should return 302 due to validation errors, not 404 or 405
        $response->assertStatus(302);
    }

    public function test_valid_submission_redirects_to_contact_show()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $response->assertRedirect(route('contact.show'));
    }

    public function test_valid_submission_creates_exactly_one_contact_messages_record()
    {
        $this->assertEquals(0, ContactMessage::count());

        $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $this->assertEquals(1, ContactMessage::count());
    }

    public function test_stored_values_are_correct()
    {
        $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $message = ContactMessage::first();

        $this->assertEquals('John Doe', $message->name);
        $this->assertEquals('john@example.com', $message->email);
        $this->assertEquals('Inquiry', $message->subject);
        $this->assertEquals('Hello there!', $message->message);
        $this->assertEquals(ContactStatus::Unread, $message->status);
    }

    public function test_request_cannot_override_status()
    {
        $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
            'status' => 'archived',
        ]);

        $message = ContactMessage::first();
        $this->assertEquals(ContactStatus::Unread, $message->status);
    }

    public function test_name_is_required()
    {
        $response = $this->post('/kontak', [
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, ContactMessage::count());
    }

    public function test_invalid_email_is_rejected()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertEquals(0, ContactMessage::count());
    }

    public function test_subject_is_required()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello there!',
        ]);

        $response->assertSessionHasErrors('subject');
        $this->assertEquals(0, ContactMessage::count());
    }

    public function test_message_is_required()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
        ]);

        $response->assertSessionHasErrors('message');
        $this->assertEquals(0, ContactMessage::count());
    }

    public function test_oversized_name_is_rejected()
    {
        $response = $this->post('/kontak', [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, ContactMessage::count());
    }

    public function test_oversized_email_is_rejected()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => str_repeat('a', 246).'@example.com', // length > 255
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertEquals(0, ContactMessage::count());
    }

    public function test_oversized_subject_is_rejected()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => str_repeat('a', 256),
            'message' => 'Hello there!',
        ]);

        $response->assertSessionHasErrors('subject');
        $this->assertEquals(0, ContactMessage::count());
    }

    public function test_oversized_message_is_rejected()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => str_repeat('a', 10001),
        ]);

        $response->assertSessionHasErrors('message');
        $this->assertEquals(0, ContactMessage::count());
    }

    public function test_success_flash_message_exists()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_successful_post_uses_redirect_not_direct_final_rendering()
    {
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $response->assertStatus(302);
        // Not a 200 response with HTML body
    }

    public function test_csrf_token_exists_in_get_contact_form_html()
    {
        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('_token');
        $response->assertSee('type="hidden" name="_token"', false);
    }

    public function test_post_route_is_protected_by_web_csrf_middleware_contract()
    {
        $routes = Route::getRoutes()->getRoutesByMethod()['POST'];
        $route = $routes['kontak'] ?? null;

        $this->assertNotNull($route);
        $this->assertTrue(in_array('web', $route->middleware()));
    }

    public function test_throttle_allows_normal_requests()
    {
        // Clear rate limiter for IP
        RateLimiter::clear(request()->ip());

        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $response->assertRedirect();
    }

    public function test_sixth_request_within_same_limiter_window_receives_429()
    {
        // Force the IP to a specific one for this test
        $server = ['REMOTE_ADDR' => '192.168.1.1'];

        RateLimiter::clear('192.168.1.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/kontak', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'subject' => 'Inquiry',
                'message' => 'Hello there!',
            ], $server);
        }

        // 6th request
        $response = $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ], $server);

        $response->assertStatus(429);
    }

    public function test_throttled_request_does_not_add_another_record()
    {
        $server = ['REMOTE_ADDR' => '192.168.1.2'];

        RateLimiter::clear('192.168.1.2');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/kontak', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'subject' => 'Inquiry',
                'message' => 'Hello there!',
            ], $server);
        }

        $countBeforeThrottled = ContactMessage::count();

        // 6th request (throttled)
        $this->post('/kontak', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ], $server);

        $this->assertEquals($countBeforeThrottled, ContactMessage::count());
    }

    public function test_get_kontak_remains_accessible_after_post_throttle_is_exceeded()
    {
        $server = ['REMOTE_ADDR' => '192.168.1.3'];

        RateLimiter::clear('192.168.1.3');

        for ($i = 0; $i < 6; $i++) {
            $this->post('/kontak', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'subject' => 'Inquiry',
                'message' => 'Hello there!',
            ], $server);
        }

        $response = $this->get('/kontak', [], $server);
        $response->assertStatus(200);
    }

    public function test_malicious_text_in_input_is_stored_as_data_only_and_not_executed_in_public_success_response()
    {
        $this->post('/kontak', [
            'name' => '<script>alert("xss")</script>',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'Hello there!',
        ]);

        $message = ContactMessage::first();
        $this->assertEquals('<script>alert("xss")</script>', $message->name);

        $response = $this->get('/kontak');
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_contact_001_settings_display_still_works()
    {
        Setting::factory()->create([
            'group_name' => 'contact',
            'setting_key' => 'contact.email',
            'value' => 'info@tintapena.test',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertSee('info@tintapena.test');
    }

    public function test_normal_published_static_page_still_works()
    {
        $page = Page::factory()->create([
            'slug' => 'tentang-kami',
            'status' => PageStatus::Published->value,
        ]);

        $response = $this->get('/tentang-kami');
        $response->assertStatus(200);
        $response->assertSee($page->title);
    }

    public function test_kontak_cannot_be_hijacked_by_static_page_slug()
    {
        Page::factory()->create([
            'slug' => 'kontak',
            'status' => PageStatus::Published->value,
            'title' => 'Hijacked Kontak',
        ]);

        $response = $this->get('/kontak');
        $response->assertStatus(200);
        $response->assertDontSee('Hijacked Kontak');
        $response->assertViewIs('contact.show');
    }
}
