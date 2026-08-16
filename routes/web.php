<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

use App\Http\Controllers\ArticleController;
Route::get('/berita/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
