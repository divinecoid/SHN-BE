<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\SalesOrder;
use App\Models\MasterData\SalesOrderItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class SalesOrderController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = SalesOrder::with(['salesOrderItems']);
        $query = $this->applyFilter($query, $request, ['nomor_so', 'nama_pelanggan', 'syarat_pembayaran']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Header validation
            'nomor_so' => 'required|string|unique:trx_sales_order,nomor_so',
            'tanggal_so' => 'required|date',
            'tanggal_pengiriman' => 'required|date',
            'syarat_pembayaran' => 'required|string',
            'asal_gudang' => 'required|string',
            'nama_pelanggan' => 'required|string',
            'telepon' => 'required|string',
            'email' => 'required|email',
            'alamat' => 'required|string',
            
            // Items validation
            'items' => 'required|array|min:1',
            'items.*.panjang' => 'required|numeric|min:0',
            'items.*.lebar' => 'required|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.jenis_barang' => 'required|string',
            'items.*.bentuk_barang' => 'required|string',
            'items.*.grade_barang' => 'required|string',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.satuan' => 'required|string',
            'items.*.diskon' => 'nullable|numeric|min:0|max:100',
            'items.*.catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            // Create sales order header
            $salesOrder = SalesOrder::create($request->only([
                'nomor_so',
                'tanggal_so',
                'tanggal_pengiriman',
                'syarat_pembayaran',
                'asal_gudang',
                'nama_pelanggan',
                'telepon',
                'email',
                'alamat'
            ]));

            // Create sales order items
            foreach ($request->items as $item) {
                $salesOrder->salesOrderItems()->create([
                    'panjang' => $item['panjang'],
                    'lebar' => $item['lebar'],
                    'qty' => $item['qty'],
                    'jenis_barang' => $item['jenis_barang'],
                    'bentuk_barang' => $item['bentuk_barang'],
                    'grade_barang' => $item['grade_barang'],
                    'harga' => $item['harga'],
                    'satuan' => $item['satuan'],
                    'diskon' => $item['diskon'] ?? 0,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            DB::commit();

            // Load the created data with relationships
            $salesOrder->load('salesOrderItems');

            return $this->successResponse($salesOrder, 'Sales Order berhasil ditambahkan');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menyimpan Sales Order: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $data = SalesOrder::with(['salesOrderItems'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $salesOrder = SalesOrder::find($id);
        if (!$salesOrder) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            // Header validation
            'nomor_so' => 'required|string|unique:trx_sales_order,nomor_so,' . $id,
            'tanggal_so' => 'required|date',
            'tanggal_pengiriman' => 'required|date',
            'syarat_pembayaran' => 'required|string',
            'asal_gudang' => 'required|string',
            'nama_pelanggan' => 'required|string',
            'telepon' => 'required|string',
            'email' => 'required|email',
            'alamat' => 'required|string',
            
            // Items validation
            'items' => 'required|array|min:1',
            'items.*.panjang' => 'required|numeric|min:0',
            'items.*.lebar' => 'required|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.jenis_barang' => 'required|string',
            'items.*.bentuk_barang' => 'required|string',
            'items.*.grade_barang' => 'required|string',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.satuan' => 'required|string',
            'items.*.diskon' => 'nullable|numeric|min:0|max:100',
            'items.*.catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            // Update sales order header
            $salesOrder->update($request->only([
                'nomor_so',
                'tanggal_so',
                'tanggal_pengiriman',
                'syarat_pembayaran',
                'asal_gudang',
                'nama_pelanggan',
                'telepon',
                'email',
                'alamat'
            ]));

            // Delete existing items and create new ones
            $salesOrder->salesOrderItems()->delete();
            
            foreach ($request->items as $item) {
                $salesOrder->salesOrderItems()->create([
                    'panjang' => $item['panjang'],
                    'lebar' => $item['lebar'],
                    'qty' => $item['qty'],
                    'jenis_barang' => $item['jenis_barang'],
                    'bentuk_barang' => $item['bentuk_barang'],
                    'grade_barang' => $item['grade_barang'],
                    'harga' => $item['harga'],
                    'satuan' => $item['satuan'],
                    'diskon' => $item['diskon'] ?? 0,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            DB::commit();

            // Load the updated data with relationships
            $salesOrder->load('salesOrderItems');

            return $this->successResponse($salesOrder, 'Sales Order berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal memperbarui Sales Order: ' . $e->getMessage(), 500);
        }
    }

    public function softDelete($id)
    {
        $data = SalesOrder::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil dihapus');
    }

    public function restore($id)
    {
        $data = SalesOrder::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak dihapus', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil dipulihkan');
    }

    public function forceDelete($id)
    {
        $data = SalesOrder::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil dihapus permanen');
    }
}