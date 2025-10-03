<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Transactions\StockMutation;
use Auth;
use Exception;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Log;

class StockMutationController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = StockMutation::with(['stockMutationItems', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }


        if ($request->filled('startDate') || $request->filled('endDate')) {
            $from = $request->input('startDate');
            $to = $request->input('endDate');

            if ($from && $to) {
                $query->whereBetween('created_at', [$from, $to]);
            } elseif ($from) {
                $query->whereDate('created_at', '>=', $from);
            } elseif ($to) {
                $query->whereDate('created_at', '<=', $to);
            }
        }
        if ($request->filled('requestor')) {
            $query->whereHas('requestor', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('requestor') . '%');
            });
        }
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
    public function store(Request $request)
    {

        $requestor_id = Auth::id();

        $validator = Validator::make($request->all(), [
            'gudang_tujuan_id' => 'required|exists:ref_gudang,id',
            'gudang_asal_id' => 'required|exists:ref_gudang,id',
            'stock_mutation' => 'required|array|min:1',
            'stock_mutation.*.item_barang_id' => 'required|exists:ref_item_barang,id',
            'stock_mutation.*.unit' => ['required', Rule::in(['single', 'bulk'])],
            'stock_mutation.*.quantity' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $stockMutation = StockMutation::create(array_merge(
                $request->only([
                    'gudang_tujuan_id',
                    'gudang_asal_id',
                    'recipient_id'
                ]),
                [
                    'requestor_id' => $requestor_id,
                    'status' => 'requested'
                ]
            ));


            foreach ($request->input('stock_mutation') as $item) {
                $stockMutation->stockMutationItems()->create([
                    'item_barang_id' => $item['item_barang_id'],
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity']
                ]);
            }

            DB::commit();

            $stockMutation->load(['stockMutationItems', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient']);

            return $this->successResponse($stockMutation, 'Stock Mutation berhasil ditambahkan');


        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal menyimpan Mutasi Stock' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $data = StockMutation::with(['stockMutationItems', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {

    }

    public function destroy($id)
    {
        $data = StockMutation::with(['stockMutationItems', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            // Hapus data (soft delete)
            $data->delete();
            return $this->successResponse(null, 'Stock Mutation berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus Stock Mutation: ' . $e->getMessage(), 500);
        }
    }

    public function restore($id)
    {
        $data = StockMutation::with(['stockMutationItems', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient'])->onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $data->restore();
            return $this->successResponse($data, 'Stock Mutation berhasil direstore');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal restore Stock Mutation: ' . $e->getMessage(), 500);
        }
    }

    public function forceDelete($id)
    {
        $data = StockMutation::with(['stockMutationItems', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient'])->withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $data->forceDelete();
            return $this->successResponse(null, 'Stock Mutation berhasil dihapus permanen');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus permanen Stock Mutation: ' . $e->getMessage(), 500);
        }
    }

    public function softDelete($id)
    {
        $data = StockMutation::with(['stockMutationItems', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $data->delete();
            return $this->successResponse(null, 'Stock Mutation berhasil dihapus (soft delete)');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal soft delete Stock Mutation: ' . $e->getMessage(), 500);
        }
    }

}