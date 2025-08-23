<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Swagger UI Route
// Route::get('/swagger', function () {
//     return view('swagger');
// });

// Serve YAML specs from storage/api-docs for Swagger $ref resolution
Route::get('/api-docs/{filename}', function ($filename) {
    $path = storage_path('api-docs/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->make(file_get_contents($path), 200, [
        'Content-Type' => 'application/yaml'
    ]);
});
