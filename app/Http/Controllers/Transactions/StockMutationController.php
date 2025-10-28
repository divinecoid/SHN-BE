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
use App\Http\Controllers\MasterData\DocumentSequenceController;

class StockMutationController extends Controller
{
    use ApiFilterTrait;
    
    protected $documentSequenceController;
    
    public function __construct()
    {
        $this->documentSequenceController = new DocumentSequenceController();
    }

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
            // Generate nomor_mutasi menggunakan DocumentSequenceController
            $nomorMutasiResponse = $this->documentSequenceController->generateDocumentSequence('mutasi');
            if ($nomorMutasiResponse->getStatusCode() !== 200) {
                return $this->errorResponse('Gagal generate nomor mutasi', 500);
            }
            $nomorMutasi = $nomorMutasiResponse->getData()->data;
            
            $stockMutation = StockMutation::create(array_merge(
                $request->only([
                    'gudang_tujuan_id',
                    'gudang_asal_id',
                    'recipient_id'
                ]),
                [
                    'requestor_id' => $requestor_id,
                    'status' => 'requested',
                    'nomor_mutasi' => $nomorMutasi
                ]
            ));


            foreach ($request->input('stock_mutation') as $item) {
                $stockMutation->stockMutationItems()->create([
                    'item_barang_id' => $item['item_barang_id'],
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity']
                ]);
            }

            // Update sequence counter setelah berhasil create StockMutation
            $this->documentSequenceController->increaseSequence('mutasi');

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
        $data = StockMutation::with(['stockMutationItems.itemBarang', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function scanNomorMutasi($nomor_mutasi)
    {
        $data = StockMutation::with(['stockMutationItems.itemBarang', 'gudangTujuan', 'gudangAsal', 'requestor', 'recipient'])->where('nomor_mutasi', $nomor_mutasi)->first();
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        // Transform data ke struktur response yang diinginkan
        $transformedData = [
            'nomor_dokumen' => $data->nomor_mutasi,
            'tipe_dokumen' => 'mutasi',
            'status' => $data->status,
            'tanggal_dokumen' => $data->created_at,
            'tanggal_penerimaan' => null,
            'user_penerima' => $data->recipient ? $data->recipient->name : null,
            'gudang_asal' => $data->gudangAsal ? $data->gudangAsal->nama_gudang : null,
            'gudang_tujuan' => $data->gudangTujuan ? $data->gudangTujuan->nama_gudang : null,
            'supplier' => null,
            'catatan' => null,
            'items' => $data->stockMutationItems->map(function ($item) {
                $itemBarang = $item->itemBarang;
                return [
                    'id' => $item->id,
                    'item_barang_id' => $item->item_barang_id,
                    'kode_barang' => $itemBarang ? $itemBarang->kode_barang : null,
                    'unit' => $item->unit,
                    'status' => 'on_progress',
                    'quantity' => $item->quantity,
                    'panjang' => $itemBarang->panjang,
                    'lebar' => $itemBarang->lebar,
                    'tebal' => $itemBarang->tebal,
                    'qty' => $item->quantity,
                    'jenis_barang_id' => null,
                    'bentuk_barang_id' => null,
                    'grade_barang_id' => null,
                    'satuan' => null,
                    'catatan' => null
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data ditemukan',
            'data' => $transformedData
        ]);
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