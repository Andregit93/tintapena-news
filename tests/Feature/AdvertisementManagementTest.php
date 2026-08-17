<?php

use App\Models\User;
use App\Models\Media;
use App\Models\Advertisement;
use App\Enums\AdvertisementType;
use App\Filament\Resources\Advertisements\AdvertisementResource;
use App\Filament\Resources\Advertisements\Pages\CreateAdvertisement;
use App\Filament\Resources\Advertisements\Pages\EditAdvertisement;
use App\Filament\Resources\Advertisements\Pages\ListAdvertisements;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('A. guest cannot access /admin/iklan', function () {
    $this->get('/admin/iklan')->assertRedirect('/admin/login');
});

it('B. authenticated admin can access advertisement management', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/iklan')->assertStatus(200);
});

it('C. admin can create valid IMAGE advertisement', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Valid Image Ad',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'content' => 'some junk that should be cleared',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('advertisements', [
        'name' => 'Valid Image Ad',
        'type' => AdvertisementType::Image->value,
        'placement_key' => 'homepage_top',
        'media_id' => $media->id,
        'content' => null,
    ]);
});

it('D. image advertisement without media is rejected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'No Media Image Ad',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['media_id' => 'required']);
});

it('E. admin can create valid SCRIPT advertisement', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Valid Script Ad',
            'type' => AdvertisementType::Script->value,
            'placement_key' => 'homepage_middle',
            'content' => '<script>console.log("ad")</script>',
            'media_id' => 999, // should be cleared
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('advertisements', [
        'name' => 'Valid Script Ad',
        'type' => AdvertisementType::Script->value,
        'placement_key' => 'homepage_middle',
        'content' => '<script>console.log("ad")</script>',
        'media_id' => null,
    ]);
});

it('F. script advertisement without content is rejected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'No Content Script Ad',
            'type' => AdvertisementType::Script->value,
            'placement_key' => 'article_inline',
            'content' => '   ',
        ])
        ->call('create')
        ->assertHasFormErrors(['content' => 'required']);
});

it('G. invalid type rejected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Invalid Type',
            'type' => 'video',
            'placement_key' => 'article_sidebar',
        ])
        ->call('create')
        ->assertHasFormErrors(['type']);
});

it('H. invalid placement_key rejected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Invalid Placement',
            'type' => AdvertisementType::Script->value,
            'placement_key' => 'invalid_placement',
            'content' => 'ad',
        ])
        ->call('create')
        ->assertHasFormErrors(['placement_key']);
});

it('I. valid HTTP target URL accepted', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'HTTP URL',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'target_url' => 'http://example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('J. valid HTTPS target URL accepted', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'HTTPS URL',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'target_url' => 'https://example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('K. javascript: URL rejected', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'JS URL',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'target_url' => 'javascript:alert(1)',
        ])
        ->call('create')
        ->assertHasFormErrors(['target_url']);
});

it('L. data: URL rejected', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Data URL',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'target_url' => 'data:text/html,<script>alert(1)</script>',
        ])
        ->call('create')
        ->assertHasFormErrors(['target_url']);
});

it('M. image -> script edit clears media_id', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    $ad = Advertisement::forceCreate([
        'name' => 'Initial Image',
        'type' => AdvertisementType::Image->value,
        'placement_key' => 'homepage_top',
        'media_id' => $media->id,
        'content' => null,
    ]);

    Livewire::actingAs($user)
        ->test(EditAdvertisement::class, ['record' => $ad->id])
        ->fillForm([
            'type' => AdvertisementType::Script->value,
            'content' => '<script>changed</script>',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('advertisements', [
        'id' => $ad->id,
        'type' => AdvertisementType::Script->value,
        'content' => '<script>changed</script>',
        'media_id' => null,
    ]);
});

it('N. script -> image edit clears content', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    $ad = Advertisement::forceCreate([
        'name' => 'Initial Script',
        'type' => AdvertisementType::Script->value,
        'placement_key' => 'homepage_top',
        'media_id' => null,
        'content' => '<script>old</script>',
    ]);

    Livewire::actingAs($user)
        ->test(EditAdvertisement::class, ['record' => $ad->id])
        ->fillForm([
            'type' => AdvertisementType::Image->value,
            'media_id' => $media->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('advertisements', [
        'id' => $ad->id,
        'type' => AdvertisementType::Image->value,
        'content' => null,
        'media_id' => $media->id,
    ]);
});

it('O. ends_at <= starts_at rejected when both are supplied', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Invalid Dates',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ])
        ->call('create')
        ->assertHasFormErrors(['ends_at']);
});

it('P. nullable starts_at accepted', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Nullable starts_at',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'starts_at' => null,
            'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('Q. nullable ends_at accepted', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Nullable ends_at',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('R. sort_order cannot be negative', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Negative Sort Order',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'sort_order' => -1,
        ])
        ->call('create')
        ->assertHasFormErrors(['sort_order']);
});

it('S. script payload is NOT executed/rendered raw in Newsroom output', function () {
    $user = User::factory()->create();

    $ad = Advertisement::forceCreate([
        'name' => 'Malicious Script',
        'type' => AdvertisementType::Script->value,
        'placement_key' => 'homepage_top',
        'media_id' => null,
        'content' => '<script>alert("xss")</script>',
    ]);

    $response = $this->actingAs($user)->get('/admin/iklan/' . $ad->id . '/edit');
    $response->assertStatus(200);

    // It should not render the raw script tags
    $response->assertDontSee('<script>alert("xss")</script>', false);
});

it('T. sort_order rejects decimal 1.5', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Decimal Sort Order',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'sort_order' => 1.5,
        ])
        ->call('create')
        ->assertHasFormErrors(['sort_order']);
});

it('U. image advertisement with nonexistent media_id fails validation', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Invalid Media',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => 999999, // Non-existent
        ])
        ->call('create')
        ->assertHasFormErrors(['media_id']);
});

it('V. blank target_url is stored as NULL', function () {
    $user = User::factory()->create();
    $media = Media::factory()->create();

    Livewire::actingAs($user)
        ->test(CreateAdvertisement::class)
        ->fillForm([
            'name' => 'Blank URL',
            'type' => AdvertisementType::Image->value,
            'placement_key' => 'homepage_top',
            'media_id' => $media->id,
            'target_url' => '   ', // Blank spaces
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('advertisements', [
        'name' => 'Blank URL',
        'target_url' => null,
    ]);
});
