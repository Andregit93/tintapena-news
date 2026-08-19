<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('returns a custom 404 page that is public and safe', function () {
    Config::set('app.debug', false);

    $response = $this->get('/this-slug-definitely-does-not-exist-12345');

    $response->assertStatus(404);

    // Assert custom 404 markers are present
    $response->assertSee('404');
    $response->assertSee('Halaman tidak ditemukan');
    $response->assertSee('Kembali ke Beranda');

    // Assert sensitive information is not exposed
    $response->assertDontSee('Exception');
    $response->assertDontSee('Stack trace');
    $response->assertDontSee('vendor/');
    $response->assertDontSee('storage/framework');
    $response->assertDontSee('SQLSTATE');
});

it('returns a custom 500 page that is failure-safe and does not leak info', function () {
    Config::set('app.debug', false);

    Route::get('/test-500/endpoint', function () {
        throw new RuntimeException('SYSTEM005_SECRET_EXCEPTION_MARKER');
    });

    $response = $this->get('/test-500/endpoint');

    $response->assertStatus(500);

    // Assert custom 500 markers are present
    $response->assertSee('500');
    $response->assertSee('Kesalahan Sistem');

    // Assert sensitive information is absent
    $response->assertDontSee('SYSTEM005_SECRET_EXCEPTION_MARKER');
    $response->assertDontSee('RuntimeException');
    $response->assertDontSee('Stack trace');
    $response->assertDontSee('vendor/');
    $response->assertDontSee('ErrorHandlingTest.php');
});

it('preserves json behavior for api-like requests on 404', function () {
    $response = $this->getJson('/this-slug-definitely-does-not-exist-12345');

    $response->assertStatus(404);
    $response->assertJsonStructure(['message']);
});
