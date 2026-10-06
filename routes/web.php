<?php

use Illuminate\Support\Facades\Route;

Route::view('/login', 'app')->name('login');

// Catch-all de la SPA. Excluye api/: una ruta de API inexistente debe responder
// 404 en JSON, no el HTML de la aplicación con 200.
Route::view('/{any?}', 'app')->where('any', '^(?!api(/|$)).*');
