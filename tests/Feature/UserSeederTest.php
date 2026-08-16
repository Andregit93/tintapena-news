<?php

use App\Models\Category;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Reset configuration
    config()->set('tintapena.admin.name', 'Admin Test');
    config()->set('tintapena.admin.email', 'admin@example.com');
    config()->set('tintapena.admin.password', 'secret-password');
});

it('A. creates one admin when config credentials exist', function () {
    $this->seed(UserSeeder::class);

    $users = User::all();
    expect($users)->toHaveCount(1);

    $admin = $users->first();
    expect($admin->name)->toBe('Admin Test');
    expect($admin->email)->toBe('admin@example.com');
});

it('B. stored password is hashed and Hash::check() succeeds', function () {
    $this->seed(UserSeeder::class);

    $admin = User::first();

    // The password should be hashed, not raw
    expect($admin->password)->not->toBe('secret-password');
    expect(Hash::check('secret-password', $admin->password))->toBeTrue();
});

it('C. running UserSeeder twice does not duplicate user', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    $users = User::all();
    expect($users)->toHaveCount(1);
});

it('D. rerunning does not overwrite an existing user\'s password', function () {
    // Create the user initially
    $this->seed(UserSeeder::class);

    $admin = User::first();
    $originalPasswordHash = $admin->password;

    // Change the configured password
    config()->set('tintapena.admin.password', 'new-password');

    // Run seeder again
    $this->seed(UserSeeder::class);

    $admin->refresh();

    // The password should NOT have changed
    expect($admin->password)->toBe($originalPasswordHash);
    expect(Hash::check('secret-password', $admin->password))->toBeTrue();
    expect(Hash::check('new-password', $admin->password))->toBeFalse();
});

it('E. missing email or password does not create a user', function () {
    config()->set('tintapena.admin.email', null);
    $this->seed(UserSeeder::class);
    expect(User::count())->toBe(0);

    config()->set('tintapena.admin.email', 'admin@example.com');
    config()->set('tintapena.admin.password', '');
    $this->seed(UserSeeder::class);
    expect(User::count())->toBe(0);
});

it('F. DatabaseSeeder still seeds Category and Region normally', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(1);
    expect(Category::count())->toBeGreaterThan(0);
    expect(Region::count())->toBeGreaterThan(0);
});
