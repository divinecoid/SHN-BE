<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MasterData\JenisBarangController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\MasterData\BentukBarangController;
use App\Http\Controllers\MasterData\GradeBarangController;
use App\Http\Controllers\MasterData\ItemBarangController;
use App\Http\Controllers\MasterData\JenisTransaksiKasController;
Route::get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test', function (Request $request) {
    // return $request->user();
    return 'ok';
});


// User routes - Read operations (all roles)
Route::middleware('checkrole')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    // Soft delete read operations (all roles)

});

// User routes - Delete operations (admin only)
Route::middleware('checkrole:admin')->group(function () {
    Route::get('users-with-trashed/all', [UserController::class, 'indexWithTrashed']);
    Route::get('users-with-trashed/trashed', [UserController::class, 'indexTrashed']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::patch('users/{id}', [UserController::class, 'update']);
    Route::patch('users/{id}/restore', [UserController::class, 'restore']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::delete('users/{id}/soft', [UserController::class, 'softDelete']);
    Route::delete('users/{id}/force', [UserController::class, 'forceDelete']);
    Route::post('/register', RegisterController::class);
    
});

// Login route
Route::post('/login', [LoginController::class, 'login']);
// Roles route
Route::get('/roles', [RoleController::class, 'index']);

// JenisBarang routes
Route::prefix('jenis-barang')->middleware('checkrole')->group(function () {
    Route::get('/', [JenisBarangController::class, 'index']);
    Route::get('{id}', [JenisBarangController::class, 'show']);
    Route::post('/', [JenisBarangController::class, 'store']);
    Route::put('{id}', [JenisBarangController::class, 'update']);
    Route::patch('{id}', [JenisBarangController::class, 'update']);
});
Route::prefix('jenis-barang')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}', [JenisBarangController::class, 'destroy']);
    Route::delete('{id}/soft', [JenisBarangController::class, 'softDelete']);
    Route::patch('{id}/restore', [JenisBarangController::class, 'restore']);
    Route::delete('{id}/force', [JenisBarangController::class, 'forceDelete']);
    Route::get('with-trashed/all', [JenisBarangController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [JenisBarangController::class, 'indexTrashed']);
});

// BentukBarang routes
Route::prefix('bentuk-barang')->middleware('checkrole')->group(function () {
    Route::get('/', [BentukBarangController::class, 'index']);
    Route::get('{id}', [BentukBarangController::class, 'show']);
    Route::post('/', [BentukBarangController::class, 'store']);
    Route::put('{id}', [BentukBarangController::class, 'update']);
    Route::patch('{id}', [BentukBarangController::class, 'update']);
});
Route::prefix('bentuk-barang')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}', [BentukBarangController::class, 'destroy']);
    Route::delete('{id}/soft', [BentukBarangController::class, 'softDelete']);
    Route::patch('{id}/restore', [BentukBarangController::class, 'restore']);
    Route::delete('{id}/force', [BentukBarangController::class, 'forceDelete']);
    Route::get('with-trashed/all', [BentukBarangController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [BentukBarangController::class, 'indexTrashed']);
});

// GradeBarang routes
Route::prefix('grade-barang')->middleware('checkrole')->group(function () {
    Route::get('/', [GradeBarangController::class, 'index']);
    Route::get('{id}', [GradeBarangController::class, 'show']);
    Route::post('/', [GradeBarangController::class, 'store']);
    Route::put('{id}', [GradeBarangController::class, 'update']);
    Route::patch('{id}', [GradeBarangController::class, 'update']);
});
Route::prefix('grade-barang')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}', [GradeBarangController::class, 'destroy']);
    Route::delete('{id}/soft', [GradeBarangController::class, 'softDelete']);
    Route::patch('{id}/restore', [GradeBarangController::class, 'restore']);
    Route::delete('{id}/force', [GradeBarangController::class, 'forceDelete']);
    Route::get('with-trashed/all', [GradeBarangController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [GradeBarangController::class, 'indexTrashed']);
});

// ItemBarang routes
Route::prefix('item-barang')->middleware('checkrole')->group(function () {
    Route::get('/', [ItemBarangController::class, 'index']);
    Route::get('{id}', [ItemBarangController::class, 'show']);
    Route::post('/', [ItemBarangController::class, 'store']);
    Route::put('{id}', [ItemBarangController::class, 'update']);
    Route::patch('{id}', [ItemBarangController::class, 'update']);
});
Route::prefix('item-barang')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [ItemBarangController::class, 'softDelete']);
    Route::patch('{id}/restore', [ItemBarangController::class, 'restore']);
    Route::delete('{id}/force', [ItemBarangController::class, 'forceDelete']);
    Route::get('with-trashed/all', [ItemBarangController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [ItemBarangController::class, 'indexTrashed']);
});

// JenisTransaksiKas routes
Route::prefix('jenis-transaksi-kas')->middleware('checkrole')->group(function () {
    Route::get('/', [JenisTransaksiKasController::class, 'index']);
    Route::get('{id}', [JenisTransaksiKasController::class, 'show']);
    Route::post('/', [JenisTransaksiKasController::class, 'store']);
    Route::put('{id}', [JenisTransaksiKasController::class, 'update']);
    Route::patch('{id}', [JenisTransaksiKasController::class, 'update']);
});
Route::prefix('jenis-transaksi-kas')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [JenisTransaksiKasController::class, 'softDelete']);
    Route::patch('{id}/restore', [JenisTransaksiKasController::class, 'restore']);
    Route::delete('{id}/force', [JenisTransaksiKasController::class, 'forceDelete']);
    Route::get('with-trashed/all', [JenisTransaksiKasController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [JenisTransaksiKasController::class, 'indexTrashed']);
});


