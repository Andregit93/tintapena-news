<?php

use App\Models\Category;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RegionSeeder;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('seeds exactly 9 categories with correct slugs and active status', function () {
    $this->seed(CategorySeeder::class);

    $categories = Category::all();
    expect($categories)->toHaveCount(9);

    $slugs = $categories->pluck('slug')->toArray();
    $expectedSlugs = [
        'politik',
        'pemerintahan',
        'ekonomi',
        'hukum-kriminal',
        'pendidikan',
        'kesehatan',
        'pariwisata',
        'olahraga',
        'opini'
    ];

    expect(array_diff($expectedSlugs, $slugs))->toBeEmpty();
    expect($categories->where('is_active', false))->toHaveCount(0);
});

it('CategorySeeder is idempotent', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe(9);
});

it('seeds exactly 7 regions with correct slugs and active status', function () {
    $this->seed(RegionSeeder::class);

    $regions = Region::all();
    expect($regions)->toHaveCount(7);

    $slugs = $regions->pluck('slug')->toArray();
    $expectedSlugs = [
        'pangkalpinang',
        'bangka',
        'bangka-barat',
        'bangka-tengah',
        'bangka-selatan',
        'belitung',
        'belitung-timur'
    ];

    expect(array_diff($expectedSlugs, $slugs))->toBeEmpty();
    expect($regions->where('is_active', false))->toHaveCount(0);
});

it('RegionSeeder is idempotent', function () {
    $this->seed(RegionSeeder::class);
    $this->seed(RegionSeeder::class);

    expect(Region::count())->toBe(7);
});

it('DatabaseSeeder calls Category and Region seeders', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Category::count())->toBe(9)
        ->and(Region::count())->toBe(7);
});

it('User implements FilamentUser', function () {
    $user = new User();
    
    expect($user instanceof FilamentUser)->toBeTrue();
});

it('allows access to admin panel and rejects other panels', function () {
    $user = new User();
    
    $adminPanel = new Panel();
    $adminPanel->id('admin');
    
    $otherPanel = new Panel();
    $otherPanel->id('other');

    expect($user->canAccessPanel($adminPanel))->toBeTrue()
        ->and($user->canAccessPanel($otherPanel))->toBeFalse();
});

it('does not have a public registration route', function () {
    $hasRegisterRoute = Route::has('register') || Route::has('filament.admin.auth.register');
    expect($hasRegisterRoute)->toBeFalse();
});
