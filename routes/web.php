<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LatestNewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PopularNewsController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/berita/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/terbaru', [LatestNewsController::class, 'index'])->name('articles.latest');
Route::get('/terpopuler', [PopularNewsController::class, 'index'])->name('articles.popular');
Route::get('/kategori/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/wilayah/{region:slug}', [RegionController::class, 'show'])->name('regions.show');
Route::get('/topik/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
Route::get('/cari', [SearchController::class, 'index'])->name('search');
Route::get('/kontak', [ContactController::class, 'show'])->name('contact.show');
Route::post('/kontak', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
// Catch-all static page route MUST be last
Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
