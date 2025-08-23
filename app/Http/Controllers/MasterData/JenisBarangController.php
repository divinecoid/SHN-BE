<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\JenisBarang;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class JenisBarangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = JenisBarang::query();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_jenis']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_jenis_barang,kode',
            'nama_jenis' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data = JenisBarang::create($request->only(['kode', 'nama_jenis']));
        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = JenisBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = JenisBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_jenis_barang,kode,' . $data->id,
            'nama_jenis' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data->update($request->only(['kode', 'nama_jenis']));
        return $this->successResponse($data, 'Data berhasil diupdate');
    }

    public function softDelete($id)
    {
        $data = JenisBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil di-soft delete');
    }

    public function restore($id)
    {
        $data = JenisBarang::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak soft deleted', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil di-restore');
    }

    public function forceDelete($id)
    {
        $data = JenisBarang::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil di-force delete');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = JenisBarang::withTrashed();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_jenis']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = JenisBarang::onlyTrashed();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_jenis']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
}
