<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_cannot_access_page_management()
    {
        $this->get(PageResource::getUrl('index'))->assertRedirectContains('/admin/login');
    }

    public function test_authenticated_admin_can_access_page_management()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)
            ->get(PageResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_admin_can_create_draft_page_with_correct_metadata()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'Test Draft Page',
                'slug' => 'test-draft-page',
                'content' => '<p>Draft content</p>',
                'status' => PageStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::first();

        $this->assertNotNull($page);
        $this->assertEquals('Test Draft Page', $page->title);
        $this->assertEquals(PageStatus::Draft, $page->status);
        $this->assertNull($page->published_at);
        $this->assertEquals($admin->id, $page->created_by);
        $this->assertEquals($admin->id, $page->updated_by);
    }

    public function test_title_and_content_and_slug_are_required()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => '',
                'slug' => '',
                'content' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['title', 'slug', 'content']);
    }

    public function test_create_title_auto_generates_slug_when_slug_is_blank()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'Initial Title',
            ])
            ->assertFormSet(['slug' => 'initial-title']);
    }

    public function test_manually_entered_slug_is_not_overwritten_by_later_title_change()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'slug' => 'custom-slug',
            ])
            ->fillForm([
                'title' => 'Some Title',
            ])
            ->assertFormSet(['slug' => 'custom-slug']);
    }

    public function test_editing_title_does_not_regenerate_existing_slug()
    {
        $admin = User::factory()->create();
        $page = Page::factory()->create(['title' => 'Old Title', 'slug' => 'old-slug']);

        Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $page->id])
            ->fillForm([
                'title' => 'New Title',
            ])
            ->assertFormSet(['slug' => 'old-slug']);
    }

    public function test_editing_a_page_while_keeping_its_own_current_slug_passes_unique_validation()
    {
        $admin = User::factory()->create();
        $page = Page::factory()->create(['slug' => 'my-unique-slug']);

        Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $page->id])
            ->fillForm([
                'title' => 'Updating Title Only',
            ])
            ->call('save')
            ->assertHasNoFormErrors(['slug' => 'unique']);
    }

    public function test_duplicate_slug_from_another_page_fails()
    {
        $admin = User::factory()->create();
        Page::factory()->create(['slug' => 'existing-slug']);

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'Another Page',
                'slug' => 'existing-slug',
                'content' => '<p>Content</p>',
                'status' => PageStatus::Draft->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_invalid_slug_formats_fail()
    {
        $admin = User::factory()->create();

        $invalidSlugs = [
            'hello_world',
            'Hello World',
            'hello/world',
            'hello?world',
            '-leading',
            'trailing-',
            'hello--world',
        ];

        foreach ($invalidSlugs as $invalidSlug) {
            Livewire::actingAs($admin)
                ->test(CreatePage::class)
                ->fillForm([
                    'title' => 'Valid Title',
                    'slug' => $invalidSlug,
                    'content' => '<p>Content</p>',
                    'status' => PageStatus::Draft->value,
                ])
                ->call('create')
                ->assertHasFormErrors(['slug' => 'regex']);
        }
    }

    public function test_admin_can_manually_set_valid_slug_and_edit_content()
    {
        $admin = User::factory()->create();
        $page = Page::factory()->create(['slug' => 'old-slug', 'created_by' => $admin->id]);
        $anotherAdmin = User::factory()->create();

        Livewire::actingAs($anotherAdmin)
            ->test(EditPage::class, ['record' => $page->id])
            ->fillForm([
                'title' => 'Updated Title',
                'slug' => 'new-manual-slug',
                'content' => '<p>Updated content</p>',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();

        $this->assertEquals('Updated Title', $page->title);
        $this->assertEquals('new-manual-slug', $page->slug);
        $this->assertEquals('<p>Updated content</p>', $page->content);
        $this->assertEquals($admin->id, $page->created_by); // Preserved
        $this->assertEquals($anotherAdmin->id, $page->updated_by); // Updated
    }

    public function test_admin_can_publish_draft_and_sets_published_at()
    {
        $admin = User::factory()->create();
        $page = Page::factory()->create(['status' => PageStatus::Draft, 'published_at' => null]);

        Carbon::setTestNow(now());

        Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $page->id])
            ->fillForm([
                'status' => PageStatus::Published->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();

        $this->assertEquals(PageStatus::Published, $page->status);
        $this->assertNotNull($page->published_at);
        $this->assertEquals(now()->toDateTimeString(), $page->published_at->toDateTimeString());
    }

    public function test_editing_already_published_page_does_not_reset_published_at()
    {
        $admin = User::factory()->create();
        $originalPublishedAt = now()->subDays(2);
        $page = Page::factory()->create([
            'status' => PageStatus::Published,
            'published_at' => $originalPublishedAt,
        ]);

        Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $page->id])
            ->fillForm([
                'title' => 'A trivial edit',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();

        $this->assertEquals(PageStatus::Published, $page->status);
        $this->assertEquals($originalPublishedAt->toDateTimeString(), $page->published_at->toDateTimeString());
    }

    public function test_admin_can_return_published_to_draft()
    {
        $admin = User::factory()->create();
        $originalPublishedAt = now()->subDays(2);
        $page = Page::factory()->create([
            'status' => PageStatus::Published,
            'published_at' => $originalPublishedAt,
        ]);

        Livewire::actingAs($admin)
            ->test(EditPage::class, ['record' => $page->id])
            ->fillForm([
                'status' => PageStatus::Draft->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();

        $this->assertEquals(PageStatus::Draft, $page->status);
        // We preserve published_at because it represents original publication timestamp
        $this->assertEquals($originalPublishedAt->toDateTimeString(), $page->published_at->toDateTimeString());
    }

    public function test_seo_title_and_meta_description_can_be_saved_with_max_lengths()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'SEO Page',
                'slug' => 'seo-page',
                'content' => '<p>Content</p>',
                'status' => PageStatus::Draft->value,
                'seo_title' => str_repeat('a', 256),
                'meta_description' => str_repeat('b', 321),
            ])
            ->call('create')
            ->assertHasFormErrors([
                'seo_title' => 'max',
                'meta_description' => 'max',
            ]);

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'SEO Page',
                'slug' => 'seo-page',
                'content' => '<p>Content</p>',
                'status' => PageStatus::Draft->value,
                'seo_title' => 'Valid SEO Title',
                'meta_description' => 'Valid Meta Description',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::where('slug', 'seo-page')->first();
        $this->assertEquals('Valid SEO Title', $page->seo_title);
        $this->assertEquals('Valid Meta Description', $page->meta_description);
    }

    public function test_status_rejects_unsupported_values()
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreatePage::class)
            ->fillForm([
                'title' => 'Invalid Status Page',
                'slug' => 'invalid-status',
                'content' => '<p>Content</p>',
                'status' => 'random',
            ])
            ->call('create')
            ->assertHasFormErrors(['status']); // just check it has error, the specific rule name might be Enum
    }

    public function test_page_content_containing_script_like_markup_does_not_become_raw_executable_content()
    {
        $admin = User::factory()->create();
        $page = Page::factory()->create([
            'content' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($admin)->get(PageResource::getUrl('edit', ['record' => $page->id]));
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }
}
