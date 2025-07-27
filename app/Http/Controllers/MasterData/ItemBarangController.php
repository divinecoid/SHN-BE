<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\ItemBarang;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class ItemBarangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang']);
        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_barang' => 'required|string|unique:item_barang,kode_barang',
            'jenis_barang_id' => 'required|exists:jenis_barang,id',
            'bentuk_barang_id' => 'required|exists:bentuk_barang,id',
            'grade_barang_id' => 'required|exists:grade_barang,id',
            'nama_item_barang' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data = ItemBarang::create($request->only(['kode_barang', 'jenis_barang_id', 'bentuk_barang_id', 'grade_barang_id', 'nama_item_barang']));
        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = ItemBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $validator = Validator::make($request->all(), [
            'kode_barang' => 'required|string|unique:item_barang,kode_barang,' . $data->id,
            'jenis_barang_id' => 'required|exists:jenis_barang,id',
            'bentuk_barang_id' => 'required|exists:bentuk_barang,id',
            'grade_barang_id' => 'required|exists:grade_barang,id',
            'nama_item_barang' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data->update($request->only(['kode_barang', 'jenis_barang_id', 'bentuk_barang_id', 'grade_barang_id', 'nama_item_barang']));
        return $this->successResponse($data, 'Data berhasil diupdate');
    }

    public function softDelete($id)
    {
        $data = ItemBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil di-soft delete');
    }

    public function restore($id)
    {
        $data = ItemBarang::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak soft deleted', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil di-restore');
    }

    public function forceDelete($id)
    {
        $data = ItemBarang::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil di-force delete');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::withTrashed()->with(['jenisBarang', 'bentukBarang', 'gradeBarang']);
        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::onlyTrashed()->with(['jenisBarang', 'bentukBarang', 'gradeBarang']);
        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
} 