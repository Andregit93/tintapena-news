<?php

use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Pages\ManageMedia;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->guest = User::factory()->create();
    Storage::fake('public');
});

it('guest cannot access media admin', function () {
    get(MediaResource::getUrl('index'))->assertRedirectContains('/admin/login');
});

it('authenticated admin can access media library', function () {
    actingAs($this->admin)->get(MediaResource::getUrl('index'))->assertSuccessful();
});

it('jpeg upload succeeds with correct metadata extraction', function () {
    actingAs($this->admin);

    // Create a fake image
    $file = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(100);

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: [
            'path' => [$file], // Filament FileUpload returns array of files when multiple, but single is also array in internal state
        ])
        ->assertHasNoActionErrors();

    // The file is stored by Livewire/Filament, then moved. 
    $media = Media::first();
    expect($media)->not->toBeNull();
    expect($media->disk)->toBe('public');
    expect($media->original_filename)->toBe('photo.jpg');
    expect($media->extension)->toBe('jpg');
    expect($media->mime_type)->toBe('image/jpeg');
    expect($media->uploaded_by)->toBe($this->admin->id);
    expect($media->size)->toBeGreaterThan(0);
    expect($media->width)->toBe(800);
    expect($media->height)->toBe(600);
    
    // File exists on storage
    Storage::disk('public')->assertExists($media->path);
    
    // Stored filename differs from original filename and uses UUID
    $storedFilename = basename($media->path);
    expect($storedFilename)->not->toBe('photo.jpg');
    // UUID regex check
    expect($storedFilename)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.jpg$/i');
});

it('png upload succeeds', function () {
    actingAs($this->admin);

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    $file = UploadedFile::fake()->createWithContent('image.png', $png);

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: ['path' => [$file]])
        ->assertHasNoActionErrors();

    $media = Media::latest()->first();
    expect($media->extension)->toBe('png');
    expect($media->mime_type)->toBe('image/png');
    Storage::disk('public')->assertExists($media->path);
});

it('webp upload succeeds', function () {
    if (!function_exists('imagecreatefromwebp')) {
        $this->markTestSkipped('WebP not supported in this PHP environment.');
    }

    actingAs($this->admin);

    $webp = base64_decode('UklGRhIAAABXRUJQVlA4TBEAAAAvAAAAAAfQ//73v/+BiOh/AAA=');
    $file = UploadedFile::fake()->createWithContent('image.webp', $webp);

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: ['path' => [$file]])
        ->assertHasNoActionErrors();

    $media = Media::latest()->first();
    expect($media->extension)->toBe('webp');
});

it('unsupported MIME rejected (svg)', function () {
    actingAs($this->admin);

    // SVG not allowed
    $file = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: [
            'path' => [$file],
        ])
        ->assertHasActionErrors(['path']);
});

it('non-image rejected', function () {
    actingAs($this->admin);

    // PDF not allowed
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: [
            'path' => [$file],
        ])
        ->assertHasActionErrors(['path']);
});

it('security regression: client-controlled dangerous filename extension never becomes final extension', function () {
    actingAs($this->admin);

    // User uploads a valid JPEG image, but uses an unexpected extension (e.g., .JPEG instead of .jpg)
    // The MIME type will be image/jpeg. Our code must force it to .jpg, ignoring the client extension.
    $file = UploadedFile::fake()->image('exploit.JPEG');

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: [
            'path' => [$file],
        ])
        ->assertHasNoActionErrors();

    $media = Media::latest()->first();
    
    // The final storage extension MUST be jpg, derived from image/jpeg MIME
    // It MUST NOT be JPEG (the client extension)
    expect($media->extension)->toBe('jpg');
    $storedFilename = basename($media->path);
    expect($storedFilename)->toEndWith('.jpg');
    expect($storedFilename)->not->toEndWith('.JPEG');
});

it('file over 5 MB rejected', function () {
    actingAs($this->admin);

    // 6MB file
    $file = UploadedFile::fake()->image('huge.jpg')->size(6000);

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: [
            'path' => [$file],
        ])
        ->assertHasActionErrors(['path']);
});

it('client cannot override uploaded_by', function () {
    actingAs($this->admin);

    $file = UploadedFile::fake()->image('photo.jpg');
    $otherUser = User::factory()->create();

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: [
            'path' => [$file],
            'uploaded_by' => $otherUser->id,
        ])
        ->assertHasNoActionErrors();

    $media = Media::first();
    expect($media->uploaded_by)->toBe($this->admin->id);
    expect($media->uploaded_by)->not->toBe($otherUser->id);
});

it('metadata can be edited without changing file path and properties', function () {
    actingAs($this->admin);
    
    $media = Media::factory()->create([
        'uploaded_by' => $this->admin->id,
        'path' => 'media/test.jpg',
        'alt_text' => 'Old Alt',
    ]);

    Livewire::test(ManageMedia::class)
        ->callTableAction('edit', $media, data: [
            'alt_text' => 'New Alt Text',
            'caption' => 'A Caption',
            'photo_credit' => 'John Doe',
        ])
        ->assertHasNoTableActionErrors();

    $media->refresh();
    expect($media->alt_text)->toBe('New Alt Text');
    expect($media->caption)->toBe('A Caption');
    expect($media->photo_credit)->toBe('John Doe');
    expect($media->path)->toBe('media/test.jpg');
    expect($media->uploaded_by)->toBe($this->admin->id);
});

it('does not expose normal delete bulk action', function () {
    actingAs($this->admin);

    Livewire::test(ManageMedia::class)
        ->assertTableBulkActionDoesNotExist('delete');
});
