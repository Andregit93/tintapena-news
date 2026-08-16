<?php

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\RegionResource;
use App\Filament\Resources\TagResource;
use App\Models\Article;
use App\Models\Category;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->guest = User::factory()->create();
    // make admin panel access work if it's based on some condition, but in step 2c we made it return true if panel id is 'admin'.
    // Actually, any User model returns true for 'admin' panel right now since we didn't add roles yet.
    // Wait, the prompt says "guest cannot access category admin". This means unauthenticated user.
});

// CATEGORY
it('guest cannot access category admin', function () {
    get(CategoryResource::getUrl('index'))->assertRedirectContains('/admin/login');
});

it('authenticated admin can access category list', function () {
    actingAs($this->admin)->get(CategoryResource::getUrl('index'))->assertSuccessful();
});

it('admin can create category', function () {
    actingAs($this->admin);
    
    Livewire::test(CategoryResource\Pages\ManageCategories::class)
        ->callAction('create', data: [
            'name' => 'Nasional',
            'slug' => 'nasional',
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();
        
    $this->assertDatabaseHas(Category::class, [
        'name' => 'Nasional',
        'slug' => 'nasional',
    ]);
});

it('category name is required', function () {
    actingAs($this->admin);

    Livewire::test(CategoryResource\Pages\ManageCategories::class)
        ->callAction('create', data: [
            'name' => null,
            'slug' => 'nasional',
            'is_active' => true,
        ])
        ->assertHasActionErrors(['name' => 'required']);
});

it('category slug is required and unique', function () {
    actingAs($this->admin);
    Category::factory()->create(['slug' => 'nasional']);

    Livewire::test(CategoryResource\Pages\ManageCategories::class)
        ->callAction('create', data: [
            'name' => 'Nasional',
            'slug' => null,
            'is_active' => true,
        ])
        ->assertHasActionErrors(['slug' => 'required']);

    Livewire::test(CategoryResource\Pages\ManageCategories::class)
        ->callAction('create', data: [
            'name' => 'Nasional 2',
            'slug' => 'nasional',
            'is_active' => true,
        ])
        ->assertHasActionErrors(['slug' => 'unique']);
        
    Livewire::test(CategoryResource\Pages\ManageCategories::class)
        ->callAction('create', data: [
            'name' => 'Invalid Slug',
            'slug' => 'invalid slug!',
            'is_active' => true,
        ])
        ->assertHasActionErrors(['slug' => 'regex']);
});

it('category can be edited and deactivated without deleting related articles', function () {
    actingAs($this->admin);
    $category = Category::factory()->create(['name' => 'Old', 'is_active' => true]);
    $article = Article::factory()->create(['category_id' => $category->id]);

    Livewire::test(CategoryResource\Pages\ManageCategories::class)
        ->callTableAction('edit', $category, data: [
            'name' => 'New Name',
            'slug' => $category->slug,
            'is_active' => false,
        ])
        ->assertHasNoTableActionErrors();

    expect($category->fresh()->name)->toBe('New Name')
        ->and($category->fresh()->is_active)->toBeFalse();
        
    $this->assertDatabaseHas(Article::class, [
        'id' => $article->id,
        'category_id' => $category->id,
    ]);
});


it('category slug gets generated from name when empty, follows name change, and preserves manual edits', function () {
    $this->actingAs($this->admin);

    Livewire::test(CategoryResource\Pages\ManageCategories::class)
        ->mountAction('create')
        ->setActionData(['name' => 'Berita Ekonomi'])
        ->assertActionDataSet(['slug' => 'berita-ekonomi']) // 1. empty slug gets generated
        ->setActionData(['name' => 'Ekonomi Babel'])
        ->assertActionDataSet(['slug' => 'ekonomi-babel'])  // 2. auto-generated slug follows name change
        ->setActionData(['slug' => 'ekonomi-khusus'])
        ->setActionData(['name' => 'Ekonomi Baru'])
        ->assertActionDataSet(['slug' => 'ekonomi-khusus']); // 3. manually edited slug survives
});

// REGION
it('guest cannot access region admin', function () {
    get(RegionResource::getUrl('index'))->assertRedirectContains('/admin/login');
});

it('authenticated admin can access region list', function () {
    actingAs($this->admin)->get(RegionResource::getUrl('index'))->assertSuccessful();
});

it('admin can create region', function () {
    actingAs($this->admin);

    Livewire::test(RegionResource\Pages\ManageRegions::class)
        ->callAction('create', data: [
            'name' => 'Jakarta',
            'slug' => 'jakarta',
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas(Region::class, [
        'name' => 'Jakarta',
        'slug' => 'jakarta',
    ]);
});

it('region name is required and slug unique', function () {
    actingAs($this->admin);
    Region::factory()->create(['slug' => 'jakarta']);

    Livewire::test(RegionResource\Pages\ManageRegions::class)
        ->callAction('create', data: [
            'name' => null,
            'slug' => 'jabar',
        ])
        ->assertHasActionErrors(['name' => 'required']);
        
    Livewire::test(RegionResource\Pages\ManageRegions::class)
        ->callAction('create', data: [
            'name' => 'Jakarta 2',
            'slug' => 'jakarta',
        ])
        ->assertHasActionErrors(['slug' => 'unique']);
});

it('region can be edited and existing article relationship survives', function () {
    actingAs($this->admin);
    $region = Region::factory()->create(['name' => 'Old']);
    $article = Article::factory()->create(['region_id' => $region->id]);

    Livewire::test(RegionResource\Pages\ManageRegions::class)
        ->callTableAction('edit', $region, data: [
            'name' => 'New',
            'slug' => $region->slug,
            'is_active' => true,
        ])
        ->assertHasNoTableActionErrors();

    expect($region->fresh()->name)->toBe('New');

    $this->assertDatabaseHas(Article::class, [
        'id' => $article->id,
        'region_id' => $region->id,
    ]);
});

it('region slug gets generated from name when empty, follows name change, and preserves manual edits', function () {
    $this->actingAs($this->admin);

    Livewire::test(RegionResource\Pages\ManageRegions::class)
        ->mountAction('create')
        ->setActionData(['name' => 'Bangka Belitung'])
        ->assertActionDataSet(['slug' => 'bangka-belitung'])
        ->setActionData(['name' => 'Bangka Selatan'])
        ->assertActionDataSet(['slug' => 'bangka-selatan'])
        ->setActionData(['slug' => 'basel'])
        ->setActionData(['name' => 'Basel Baru'])
        ->assertActionDataSet(['slug' => 'basel']);
});

// TAG
it('guest cannot access tag admin', function () {
    get(TagResource::getUrl('index'))->assertRedirectContains('/admin/login');
});

it('authenticated admin can access tag list', function () {
    actingAs($this->admin)->get(TagResource::getUrl('index'))->assertSuccessful();
});

it('admin can create tag with required name and unique slug', function () {
    actingAs($this->admin);
    Tag::factory()->create(['slug' => 'existing']);

    Livewire::test(TagResource\Pages\ManageTags::class)
        ->callAction('create', data: [
            'name' => null,
            'slug' => 'new',
        ])
        ->assertHasActionErrors(['name' => 'required']);

    Livewire::test(TagResource\Pages\ManageTags::class)
        ->callAction('create', data: [
            'name' => 'Existing',
            'slug' => 'existing',
        ])
        ->assertHasActionErrors(['slug' => 'unique']);

    Livewire::test(TagResource\Pages\ManageTags::class)
        ->callAction('create', data: [
            'name' => 'New Tag',
            'slug' => 'new-tag',
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas(Tag::class, ['slug' => 'new-tag']);
});

it('tag can be edited', function () {
    actingAs($this->admin);
    $tag = Tag::factory()->create(['name' => 'Old']);

    Livewire::test(TagResource\Pages\ManageTags::class)
        ->callTableAction('edit', $tag, data: [
            'name' => 'New Tag',
            'slug' => $tag->slug,
        ])
        ->assertHasNoTableActionErrors();

    expect($tag->fresh()->name)->toBe('New Tag');
});

it('unused tag can be deleted', function () {
    actingAs($this->admin);
    $tag = Tag::factory()->create();

    Livewire::test(TagResource\Pages\ManageTags::class)
        ->callTableAction('delete', $tag)
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseMissing(Tag::class, ['id' => $tag->id]);
});

it('tag attached to an article cannot be deleted', function () {
    actingAs($this->admin);
    $tag = Tag::factory()->create();
    $article = Article::factory()->create();
    $article->tags()->attach($tag->id);

    Livewire::test(TagResource\Pages\ManageTags::class)
        ->callTableAction('delete', $tag);
        // The action is cancelled inside before(), so it shouldn't actually delete it.

    $this->assertDatabaseHas(Tag::class, ['id' => $tag->id]);
});

it('tag slug gets generated from name when empty, follows name change, and preserves manual edits', function () {
    $this->actingAs($this->admin);

    Livewire::test(TagResource\Pages\ManageTags::class)
        ->mountAction('create')
        ->setActionData(['name' => 'Trending Topic'])
        ->assertActionDataSet(['slug' => 'trending-topic'])
        ->setActionData(['name' => 'Viral Hari Ini'])
        ->assertActionDataSet(['slug' => 'viral-hari-ini'])
        ->setActionData(['slug' => 'viral-banget'])
        ->setActionData(['name' => 'Sangat Viral'])
        ->assertActionDataSet(['slug' => 'viral-banget']);
});
