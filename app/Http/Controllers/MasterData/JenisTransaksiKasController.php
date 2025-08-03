<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\JenisTransaksiKas;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class JenisTransaksiKasController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = JenisTransaksiKas::with('jenisBiaya');
        $query = $this->applyFilter($query, $request, ['keterangan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_biaya_id' => 'required|exists:ref_jenis_biaya,id',
            'keterangan' => 'nullable|string',
            'jumlah' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        
        $data = JenisTransaksiKas::create($request->only(['jenis_biaya_id', 'keterangan', 'jumlah']));
        $data->load('jenisBiaya');
        
        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = JenisTransaksiKas::with('jenisBiaya')->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = JenisTransaksiKas::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        $validator = Validator::make($request->all(), [
            'jenis_biaya_id' => 'required|exists:ref_jenis_biaya,id',
            'keterangan' => 'nullable|string',
            'jumlah' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        
        $data->update($request->only(['jenis_biaya_id', 'keterangan', 'jumlah']));
        $data->load('jenisBiaya');
        
        return $this->successResponse($data, 'Data berhasil diupdate');
    }

    public function softDelete($id)
    {
        $data = JenisTransaksiKas::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil di-soft delete');
    }

    public function restore($id)
    {
        $data = JenisTransaksiKas::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak soft deleted', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil di-restore');
    }

    public function forceDelete($id)
    {
        $data = JenisTransaksiKas::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil di-force delete');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = JenisTransaksiKas::withTrashed()->with('jenisBiaya');
        $query = $this->applyFilter($query, $request, ['keterangan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = JenisTransaksiKas::onlyTrashed()->with('jenisBiaya');
        $query = $this->applyFilter($query, $request, ['keterangan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
}
