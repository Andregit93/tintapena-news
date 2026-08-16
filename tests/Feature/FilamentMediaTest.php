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
    expect($media->uploaded_by)->toBe($this->admin->id);
});

it('unsupported MIME rejected', function () {
    actingAs($this->admin);

    // SVG not allowed
    $file = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

    Livewire::test(ManageMedia::class)
        ->callAction('create', data: [
            'path' => [$file],
        ])
        ->assertHasActionErrors(['path']);
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
