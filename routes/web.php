<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HeritageController;

Route::prefix('01_module_c')->group(function () {

    // A HOME precisa listar pastas e páginas da raiz
    Route::get('/', [HeritageController::class, 'browse'])
        ->name('heritages.index');

    // Lista TODAS as tags
    Route::get('/tags', [HeritageController::class, 'tags'])
        ->name('heritages.tags');

    // Páginas por tag
    Route::get('/tags/{tag}', [HeritageController::class, 'byTag'])
        ->name('heritages.byTag');

    // Busca (OR por palavras separadas por "/" ou espaços)
    Route::get('/search', [HeritageController::class, 'search'])
        ->name('heritages.search');

    // Navegação por pastas e páginas (deixa por último!)
    Route::get('/heritages/{path?}', [HeritageController::class, 'browse'])
        ->where('path', '.*')
        ->name('heritages.browse');
});
