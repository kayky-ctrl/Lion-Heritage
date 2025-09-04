<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HeritageController;

Route::prefix('01_module_c')->group(function () {
    // Página inicial
    Route::get('/', function () {
        return view('heritages.home');
    })->name('heritages.home');

    // Lista todas as tags
    Route::get('/tags', [HeritageController::class, 'tags'])->name('heritages.tags');

    // Busca
    Route::get('/search', [HeritageController::class, 'search'])->name('heritage.search');

    // Posts por tag
    Route::get('/tags/{tag}', [HeritageController::class, 'byTag'])->name('heritages.byTag');

    // Navegação de pastas e páginas (deve ficar por último)
    Route::get('/heritages/{path?}', [HeritageController::class, 'browse'])
        ->where('path', '.*')
        ->name('heritages.browse');
});