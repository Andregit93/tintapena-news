<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

use App\Http\Controllers\ArticleController;
Route::get('/berita/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');

use App\Http\Controllers\LatestNewsController;
Route::get('/terbaru', [LatestNewsController::class, 'index'])->name('articles.latest');

use App\Http\Controllers\PopularNewsController;
Route::get('/terpopuler', [PopularNewsController::class, 'index'])->name('articles.popular');

use App\Http\Controllers\CategoryController;
Route::get('/kategori/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

use App\Http\Controllers\RegionController;
Route::get('/wilayah/{region:slug}', [RegionController::class, 'show'])->name('regions.show');

