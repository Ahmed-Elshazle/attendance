<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/{any}', function () {
//     return view('react');
// })->where('any', '.*');

// Route::get('/bruh', [VideoController::class, 'show']);

// Route::get('/.well-known/pki-validation/{filename}', function ($filename) {
//     $path = public_path(".well-known/pki-validation/{$filename}");
//     if (file_exists($path)) {
//         return response()->file($path);
//     }
//     abort(404);
// });