<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\PenerimaanBarang;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\Gudang;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class PenerimaanBarangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::with(['itemBarang', 'gudang', 'rak']);
        $query = $this->applyFilter($query, $request, ['jumlah_barang', 'catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_item_barang' => 'required|exists:ref_item_barang,id',
            'id_gudang' => 'required|exists:ref_gudang,id',
            'id_rak' => 'required|exists:ref_gudang,id',
            'jumlah_barang' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Validasi bahwa rak adalah child dari gudang
        // $gudang = Gudang::find($request->id_gudang);
        // $rak = Gudang::find($request->id_rak);
        
        // if (!$rak->ancestors->contains('id', $gudang->id)) {
        //     return $this->errorResponse('Rak harus berada di dalam gudang yang dipilih', 422);
        // }

        $data = PenerimaanBarang::create($request->only([
            'id_item_barang',
            'id_gudang', 
            'id_rak',
            'jumlah_barang',
            'catatan'
        ]));

        return $this->successResponse($data, 'Data penerimaan barang berhasil ditambahkan');
        // return $this->successResponse($data->load(['itemBarang', 'gudang', 'rak']), 'Data penerimaan barang berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = PenerimaanBarang::with(['itemBarang', 'gudang', 'rak'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = PenerimaanBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'id_item_barang' => 'required|exists:ref_item_barang,id',
            'id_gudang' => 'required|exists:ref_gudang,id',
            'id_rak' => 'required|exists:ref_gudang,id',
            'jumlah_barang' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Validasi bahwa rak adalah child dari gudang
        $gudang = Gudang::find($request->id_gudang);
        $rak = Gudang::find($request->id_rak);
        
        if (!$rak->ancestors->contains('id', $gudang->id)) {
            return $this->errorResponse('Rak harus berada di dalam gudang yang dipilih', 422);
        }

        $data->update($request->only([
            'id_item_barang',
            'id_gudang',
            'id_rak',
            'jumlah_barang',
            'catatan'
        ]));

        return $this->successResponse($data->load(['itemBarang', 'gudang', 'rak']), 'Data penerimaan barang berhasil diupdate');
    }

    public function softDelete($id)
    {
        $data = PenerimaanBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil di-soft delete');
    }

    public function restore($id)
    {
        $data = PenerimaanBarang::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak soft deleted', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil di-restore');
    }

    public function forceDelete($id)
    {
        $data = PenerimaanBarang::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil di-force delete');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::withTrashed()->with(['itemBarang', 'gudang', 'rak']);
        $query = $this->applyFilter($query, $request, ['jumlah_barang', 'catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::onlyTrashed()->with(['itemBarang', 'gudang', 'rak']);
        $query = $this->applyFilter($query, $request, ['jumlah_barang', 'catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get penerimaan barang by item barang
     */
    public function getByItemBarang($idItemBarang, Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::where('id_item_barang', $idItemBarang)
            ->with(['itemBarang', 'gudang', 'rak']);
        $query = $this->applyFilter($query, $request, ['jumlah_barang', 'catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get penerimaan barang by gudang
     */
    public function getByGudang($idGudang, Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::where('id_gudang', $idGudang)
            ->with(['itemBarang', 'gudang', 'rak']);
        $query = $this->applyFilter($query, $request, ['jumlah_barang', 'catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get penerimaan barang by rak
     */
    public function getByRak($idRak, Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::where('id_rak', $idRak)
            ->with(['itemBarang', 'gudang', 'rak']);
        $query = $this->applyFilter($query, $request, ['jumlah_barang', 'catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
}
