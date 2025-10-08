<?php

use App\Http\Controllers\Transactions\KonversiBarangController;
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
use App\Http\Controllers\MasterData\PenerimaanBarangController;
use App\Http\Controllers\MasterData\SalesOrderController;
use App\Http\Controllers\MasterData\MenuController;
use App\Http\Controllers\SysSettingController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleMenuPermissionController;
use App\Http\Controllers\StaticDataController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Transactions\WorkOrderPlanningController;
use App\Http\Controllers\Transactions\WorkOrderActualController;
use App\Http\Controllers\Transactions\DashboardController;
use App\Http\Controllers\Transactions\PurchaseOrderController;
use App\Http\Controllers\Output\InvoicePodController;
use App\Http\Controllers\Transactions\StockMutationController;
use App\Http\Controllers\MasterData\DocumentSequenceController;


Route::get('/user', function (Request $request) {
    return $request->user()->load('roles');
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
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::patch('users/{id}', [UserController::class, 'update']);
    Route::patch('users/{id}/restore', [UserController::class, 'restore']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::delete('users/{id}/soft', [UserController::class, 'softDelete']);
    Route::delete('users/{id}/force', [UserController::class, 'forceDelete']);
    Route::post('/register', RegisterController::class);
    
});

// Auth routes
Route::post('/auth/login', [LoginController::class, 'login']);
Route::post('/auth/refresh', [LoginController::class, 'refresh']);
Route::post('/auth/logout', [LoginController::class, 'logout']);

// Static Data routes (no authentication required)
Route::get('/static/tipe-gudang', [StaticDataController::class, 'getTipeGudang']);
Route::get('/static/status-order', [StaticDataController::class, 'getStatusOrder']);
Route::get('/static/satuan', [StaticDataController::class, 'getSatuan']);
Route::get('/static/term-of-payment', [StaticDataController::class, 'getTermOfPayment']);
// Roles routes
Route::middleware('checkrole')->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
});

Route::middleware('checkrole:admin')->group(function () {
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
});

// Menu with permissions for role mapping
Route::middleware('checkrole')->group(function () {
    Route::get('/menu-with-permissions', [MenuController::class, 'getMenuWithPermissions']);
});

// Permissions route
Route::middleware('checkrole')->group(function () {
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::get('/permissions/{id}', [PermissionController::class, 'show']);
});

// Menu routes
Route::prefix('menu')->middleware('checkrole')->group(function () {
    Route::get('/', [MenuController::class, 'index']);
    Route::get('{id}', [MenuController::class, 'show']);
    Route::post('/', [MenuController::class, 'store']);
    Route::put('{id}', [MenuController::class, 'update']);
    Route::patch('{id}', [MenuController::class, 'update']);
});
Route::prefix('menu')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [MenuController::class, 'softDelete']);
    Route::patch('{id}/restore', [MenuController::class, 'restore']);
    Route::delete('{id}/force', [MenuController::class, 'forceDelete']);
    Route::get('with-trashed/all', [MenuController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [MenuController::class, 'indexTrashed']);
});

// Role Menu Permission routes
Route::prefix('role-menu-permission')->middleware('checkrole')->group(function () {
    Route::get('/', [RoleMenuPermissionController::class, 'index']);
    Route::get('{id}', [RoleMenuPermissionController::class, 'show']);
    Route::get('by-role/{roleId}', [RoleMenuPermissionController::class, 'getByRole']);
    Route::get('by-menu/{menuId}', [RoleMenuPermissionController::class, 'getByMenu']);
});

Route::prefix('role-menu-permission')->middleware('checkrole:admin')->group(function () {
    Route::post('/', [RoleMenuPermissionController::class, 'store']);
    Route::put('{id}', [RoleMenuPermissionController::class, 'update']);
    Route::patch('{id}', [RoleMenuPermissionController::class, 'update']);
    Route::delete('{id}', [RoleMenuPermissionController::class, 'destroy']);
    Route::post('bulk', [RoleMenuPermissionController::class, 'bulkStore']);
    Route::delete('by-role/{roleId}', [RoleMenuPermissionController::class, 'deleteByRole']);
    Route::delete('by-menu/{menuId}', [RoleMenuPermissionController::class, 'deleteByMenu']);
});

// JenisBarang routes
Route::prefix('jenis-barang')->middleware('checkrole')->group(function () {
    Route::get('/', [JenisBarangController::class, 'index']);
    Route::get('{id}', [JenisBarangController::class, 'show']);
});
Route::prefix('jenis-barang')->middleware('checkrole:admin,manager')->group(function () {
    Route::post('/', [JenisBarangController::class, 'store']);
    Route::put('{id}', [JenisBarangController::class, 'update']);
    Route::patch('{id}', [JenisBarangController::class, 'update']);
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
    Route::get('{itemBarangId}/canvas', [WorkOrderPlanningController::class, 'getCanvasByItemId']);
    Route::get('{itemBarangId}/canvas-image', [WorkOrderPlanningController::class, 'getCanvasImageByItemId']);
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

    Route::get('hierarchy', [GudangController::class, 'getHierarchy']);
    Route::get('{id}', [GudangController::class, 'show']);
    Route::get('{id}/parent', [GudangController::class, 'getParent']);
    Route::get('{id}/children', [GudangController::class, 'getChildren']);
    Route::get('{id}/descendants', [GudangController::class, 'getDescendants']);
    Route::get('{id}/ancestors', [GudangController::class, 'getAncestors']);
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
    Route::post('/without-validation', [PelangganController::class, 'storeWithoutValidation']);
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

// PenerimaanBarang routes
Route::prefix('penerimaan-barang')->middleware('checkrole')->group(function () {
    Route::get('/', [PenerimaanBarangController::class, 'index']);
    Route::get('{id}', [PenerimaanBarangController::class, 'show']);
    Route::post('/', [PenerimaanBarangController::class, 'store']);
    Route::put('{id}', [PenerimaanBarangController::class, 'update']);
    Route::patch('{id}', [PenerimaanBarangController::class, 'update']);
    Route::get('by-item-barang/{idItemBarang}', [PenerimaanBarangController::class, 'getByItemBarang']);
    Route::get('by-gudang/{idGudang}', [PenerimaanBarangController::class, 'getByGudang']);
    Route::get('by-rak/{idRak}', [PenerimaanBarangController::class, 'getByRak']);
});
Route::prefix('penerimaan-barang')->middleware('checkrole:admin')->group(function () {
    Route::delete('{id}/soft', [PenerimaanBarangController::class, 'softDelete']);
    Route::patch('{id}/restore', [PenerimaanBarangController::class, 'restore']);
    Route::delete('{id}/force', [PenerimaanBarangController::class, 'forceDelete']);
    Route::get('with-trashed/all', [PenerimaanBarangController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [PenerimaanBarangController::class, 'indexTrashed']);
});

// SysSetting routes
Route::prefix('sys-setting')->middleware('checkrole')->group(function () {
    Route::get('/', [SysSettingController::class, 'index']);
    Route::get('{id}', [SysSettingController::class, 'show']);
    Route::post('/', [SysSettingController::class, 'store']);
    Route::put('{id}', [SysSettingController::class, 'update']);
    Route::patch('{id}', [SysSettingController::class, 'update']);
    Route::delete('{id}', [SysSettingController::class, 'destroy']);
    
    // Get value by key (with cache)
    Route::get('value/{key}', [SysSettingController::class, 'getValueByKey']);
});

Route::prefix('sales-order')->middleware('checkrole:admin')->group(function () {
    // Specific routes must come before parameterized routes
    Route::get('pending-delete-requests', [SalesOrderController::class, 'getPendingDeleteRequests']);
    Route::delete('{id}/soft', [SalesOrderController::class, 'softDelete']);
    Route::patch('{id}/restore', [SalesOrderController::class, 'restore']);
    Route::delete('{id}/force', [SalesOrderController::class, 'forceDelete']);
    Route::patch('{id}/approve-delete', [SalesOrderController::class, 'approveDelete']);
    Route::patch('{id}/reject-delete', [SalesOrderController::class, 'rejectDelete']);
});
// SalesOrder routes
Route::prefix('sales-order')->middleware('checkrole')->group(function () {
    Route::get('/', [SalesOrderController::class, 'index']);
    Route::post('/', [SalesOrderController::class, 'store']);
    
    // Sales Order Header routes (header attributes only)
    Route::get('header', [SalesOrderController::class, 'getSalesOrderHeader']);
    Route::get('header/{id}', [SalesOrderController::class, 'getSalesOrderHeaderById']);
    
    // Delete request routes (user)
    Route::post('{id}/request-delete', [SalesOrderController::class, 'requestDelete']);
    Route::patch('{id}/cancel-delete-request', [SalesOrderController::class, 'cancelDeleteRequest']);
    
    // Specific routes must come before parameterized routes
    Route::get('{id}', [SalesOrderController::class, 'show']);
    Route::put('{id}', [SalesOrderController::class, 'update']);
    Route::patch('{id}', [SalesOrderController::class, 'update']);
});


// Purchase Order routes
Route::prefix('purchase-order')->middleware('checkrole')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'index']);
    Route::get('{id}', [PurchaseOrderController::class, 'show']);
    Route::post('/', [PurchaseOrderController::class, 'store']);
    Route::put('{id}', [PurchaseOrderController::class, 'update']);
    Route::patch('{id}', [PurchaseOrderController::class, 'update']);
    Route::delete('{id}', [PurchaseOrderController::class, 'destroy']);
    Route::delete('{id}/soft', [PurchaseOrderController::class, 'softDelete']);
    Route::patch('{id}/restore', [PurchaseOrderController::class, 'restore']);
    Route::delete('{id}/force', [PurchaseOrderController::class, 'forceDelete']);
    Route::get('with-trashed/all', [PurchaseOrderController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [PurchaseOrderController::class, 'indexTrashed']);
});


// WorkOrderPlanning routes
Route::prefix('work-order-planning')->middleware('checkrole')->group(function () {
    Route::get('/', [WorkOrderPlanningController::class, 'index']);
    Route::get('{id}', [WorkOrderPlanningController::class, 'show']);
    Route::post('/', [WorkOrderPlanningController::class, 'store']);
    Route::put('{id}', [WorkOrderPlanningController::class, 'update']);
    Route::patch('{id}', [WorkOrderPlanningController::class, 'update']);
    Route::patch('{id}/status', [WorkOrderPlanningController::class, 'updateStatus']);
    Route::delete('{id}', [WorkOrderPlanningController::class, 'destroy']);
    Route::patch('{id}/restore', [WorkOrderPlanningController::class, 'restore']);
    Route::delete('{id}/force', [WorkOrderPlanningController::class, 'forceDelete']);

    // Item routes
    Route::get('item/{id}', [WorkOrderPlanningController::class, 'showItem']);
    Route::put('item/{id}', [WorkOrderPlanningController::class, 'updateItem']);
    Route::patch('item/{id}', [WorkOrderPlanningController::class, 'updateItem']);
    
    // Pelaksana routes untuk item
    Route::post('item/{itemId}/pelaksana', [WorkOrderPlanningController::class, 'addPelaksana']);
    Route::put('item/{itemId}/pelaksana/{pelaksanaId}', [WorkOrderPlanningController::class, 'updatePelaksana']);
    Route::patch('item/{itemId}/pelaksana/{pelaksanaId}', [WorkOrderPlanningController::class, 'updatePelaksana']);
    Route::delete('item/{itemId}/pelaksana/{pelaksanaId}', [WorkOrderPlanningController::class, 'removePelaksana']);
    
    // Utility routes
    Route::post('get-saran-plat-dasar', [WorkOrderPlanningController::class, 'getSaranPlatDasar']);
    Route::get('{id}/print-spk', [WorkOrderPlanningController::class, 'printSpkWorkOrder']);
    
    // Saran Plat/Shaft Dasar routes
    Route::post('saran-plat-dasar', [WorkOrderPlanningController::class, 'addSaranPlatDasar']);
    Route::patch('saran-plat-dasar/{saranId}', [WorkOrderPlanningController::class, 'updateSaranPlatDasar']);
    Route::delete('saran-plat-dasar/{saranId}', [WorkOrderPlanningController::class, 'removeSaranPlatDasar']);
  });


  // WorkOrderActual routes
  Route::prefix('work-order-actual')->middleware('checkrole')->group(function () {
    Route::post('/', [WorkOrderActualController::class, 'saveWorkOrderActual']);
  });


  // File operations routes (get, show, download only)
Route::middleware('checkrole')->group(function () {
    Route::get('files/info', [FileController::class, 'getFileInfo']);
    Route::get('files/download', [FileController::class, 'downloadFile']);
    Route::get('files/show', [FileController::class, 'showFile']);
    Route::get('files/folder', [FileController::class, 'getFilesInFolder']);
});


// Invoice Pod routes
Route::prefix('invoice-pod')->middleware('checkrole')->group(function () {
    Route::get('/eligible-for-invoice-pod', [InvoicePodController::class, 'eligibleForInvoicePod']);
    Route::post('/generate-invoice-pod', [InvoicePodController::class, 'generateInvoicePod']);
    Route::post('/view-invoice', [InvoicePodController::class, 'viewInvoice']);
    Route::post('/view-pod', [InvoicePodController::class, 'viewPod']);
    Route::get('/eligible-for-invoice-pod', [InvoicePodController::class, 'eligibleForInvoicePod']);
});


Route::prefix('dashboard')->middleware('checkrole')->group(function () {
    Route::get('/workshop', [DashboardController::class, 'workshop']);
});


//document sequence routes
Route::prefix('document-sequence')->middleware('checkrole')->group(function () {
    Route::get('/', [DocumentSequenceController::class, 'index']);
    Route::get('today', [DocumentSequenceController::class, 'getTodayDocumentSequence']);
    Route::get('generate-sequence/{type}', [DocumentSequenceController::class, 'generateDocumentSequence']);
    Route::post('increase-sequence/{type}', [DocumentSequenceController::class, 'increaseSequence']);
    
});
// Stock Mutation routes
Route::prefix('stock-mutation')->middleware('checkrole')->group(function () {
    Route::get('/', [StockMutationController::class, 'index']);
    Route::get('{id}', [StockMutationController::class, 'show']);
    Route::post('/', [StockMutationController::class, 'store']);
    Route::put('{id}', [StockMutationController::class, 'update']);
    Route::patch('{id}', [StockMutationController::class, 'update']);
    Route::delete('{id}', [StockMutationController::class, 'destroy']);
    Route::delete('{id}/soft', [StockMutationController::class, 'softDelete']);
    Route::patch('{id}/restore', [StockMutationController::class, 'restore']);
    Route::delete('{id}/force', [StockMutationController::class, 'forceDelete']);
    Route::get('with-trashed/all', [StockMutationController::class, 'indexWithTrashed']);
    Route::get('with-trashed/trashed', [StockMutationController::class, 'indexTrashed']);
});

Route::prefix('konversi-barang')->middleware('checkrole')->group(function () {
    Route::get('/', [KonversiBarangController::class, 'index']);
    Route::patch('{id}', [KonversiBarangController::class, 'update']);
});