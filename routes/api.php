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
use App\Http\Controllers\MasterData\GudangController;
use App\Http\Controllers\MasterData\JenisBiayaController;
use App\Http\Controllers\MasterData\JenisMutasiStockController;
use App\Http\Controllers\MasterData\PelaksanaController;
use App\Http\Controllers\MasterData\PelangganController;
use App\Http\Controllers\MasterData\SupplierController;
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

// Gudang routes
Route::prefix('gudang')->middleware('checkrole')->group(function () {
    Route::get('/', [GudangController::class, 'index']);
    Route::get('tipe', [GudangController::class, 'getTipeGudang']);
    Route::get('{id}', [GudangController::class, 'show']);
    Route::post('/', [GudangController::class, 'store']);
    Route::put('{id}', [GudangController::class, 'update']);
    Route::patch('{id}', [GudangController::class, 'update']);
});
Route::prefix('gudang')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [GudangController::class, 'softDelete']);
    Route::patch('{id}/restore', [GudangController::class, 'restore']);
    Route::delete('{id}/force', [GudangController::class, 'forceDelete']);
    Route::get('with-trashed/all', [GudangController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [GudangController::class, 'indexTrashed']);
});

// JenisBiaya routes
Route::prefix('jenis-biaya')->middleware('checkrole')->group(function () {
    Route::get('/', [JenisBiayaController::class, 'index']);
    Route::get('{id}', [JenisBiayaController::class, 'show']);
    Route::post('/', [JenisBiayaController::class, 'store']);
    Route::put('{id}', [JenisBiayaController::class, 'update']);
    Route::patch('{id}', [JenisBiayaController::class, 'update']);
});
Route::prefix('jenis-biaya')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [JenisBiayaController::class, 'softDelete']);
    Route::patch('{id}/restore', [JenisBiayaController::class, 'restore']);
    Route::delete('{id}/force', [JenisBiayaController::class, 'forceDelete']);
    Route::get('with-trashed/all', [JenisBiayaController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [JenisBiayaController::class, 'indexTrashed']);
});

// JenisMutasiStock routes
Route::prefix('jenis-mutasi-stock')->middleware('checkrole')->group(function () {
    Route::get('/', [JenisMutasiStockController::class, 'index']);
    Route::get('{id}', [JenisMutasiStockController::class, 'show']);
    Route::post('/', [JenisMutasiStockController::class, 'store']);
    Route::put('{id}', [JenisMutasiStockController::class, 'update']);
    Route::patch('{id}', [JenisMutasiStockController::class, 'update']);
});
Route::prefix('jenis-mutasi-stock')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [JenisMutasiStockController::class, 'softDelete']);
    Route::patch('{id}/restore', [JenisMutasiStockController::class, 'restore']);
    Route::delete('{id}/force', [JenisMutasiStockController::class, 'forceDelete']);
    Route::get('with-trashed/all', [JenisMutasiStockController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [JenisMutasiStockController::class, 'indexTrashed']);
});

// Pelaksana routes
Route::prefix('pelaksana')->middleware('checkrole')->group(function () {
    Route::get('/', [PelaksanaController::class, 'index']);
    Route::get('{id}', [PelaksanaController::class, 'show']);
    Route::post('/', [PelaksanaController::class, 'store']);
    Route::put('{id}', [PelaksanaController::class, 'update']);
    Route::patch('{id}', [PelaksanaController::class, 'update']);
});
Route::prefix('pelaksana')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [PelaksanaController::class, 'softDelete']);
    Route::patch('{id}/restore', [PelaksanaController::class, 'restore']);
    Route::delete('{id}/force', [PelaksanaController::class, 'forceDelete']);
    Route::get('with-trashed/all', [PelaksanaController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [PelaksanaController::class, 'indexTrashed']);
});

// Pelanggan routes
Route::prefix('pelanggan')->middleware('checkrole')->group(function () {
    Route::get('/', [PelangganController::class, 'index']);
    Route::get('{id}', [PelangganController::class, 'show']);
    Route::post('/', [PelangganController::class, 'store']);
    Route::put('{id}', [PelangganController::class, 'update']);
    Route::patch('{id}', [PelangganController::class, 'update']);
});
Route::prefix('pelanggan')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [PelangganController::class, 'softDelete']);
    Route::patch('{id}/restore', [PelangganController::class, 'restore']);
    Route::delete('{id}/force', [PelangganController::class, 'forceDelete']);
    Route::get('with-trashed/all', [PelangganController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [PelangganController::class, 'indexTrashed']);
});

// Supplier routes
Route::prefix('supplier')->middleware('checkrole')->group(function () {
    Route::get('/', [SupplierController::class, 'index']);
    Route::get('{id}', [SupplierController::class, 'show']);
    Route::post('/', [SupplierController::class, 'store']);
    Route::put('{id}', [SupplierController::class, 'update']);
    Route::patch('{id}', [SupplierController::class, 'update']);
});
Route::prefix('supplier')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [SupplierController::class, 'softDelete']);
    Route::patch('{id}/restore', [SupplierController::class, 'restore']);
    Route::delete('{id}/force', [SupplierController::class, 'forceDelete']);
    Route::get('with-trashed/all', [SupplierController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [SupplierController::class, 'indexTrashed']);
});


