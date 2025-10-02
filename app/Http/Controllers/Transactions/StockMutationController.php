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

class StockMutationController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = StockMutation::with(['stockMutationItems', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient']);
        $query = $this->applyFilter($query, $request, [
            'status',
            'created_at'
        ]);
        if ($request->filled('approval_date_from') || $request->filled('approval_date_to')) {
            $from = $request->input('approval_date_from');
            $to = $request->input('approval_date_to');

            if ($from && $to) {
                $query->whereBetween('approval_date', [$from, $to]);
            } elseif ($from) {
                $query->whereDate('approval_date', '>=', $from);
            } elseif ($to) {
                $query->whereDate('approval_date', '<=', $to);
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

}