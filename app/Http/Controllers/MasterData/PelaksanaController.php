<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\Pelaksana;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class PelaksanaController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Pelaksana::query();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_pelaksana', 'level']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_pelaksana,kode',
            'nama_pelaksana' => 'required|string',
            'level' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data = Pelaksana::create($request->only(['kode', 'nama_pelaksana', 'level']));
        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = Pelaksana::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = Pelaksana::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_pelaksana,kode,' . $data->id,
            'nama_pelaksana' => 'required|string',
            'level' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data->update($request->only(['kode', 'nama_pelaksana', 'level']));
        return $this->successResponse($data, 'Data berhasil diupdate');
    }

    public function softDelete($id)
    {
        $data = Pelaksana::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil di-soft delete');
    }

    public function restore($id)
    {
        $data = Pelaksana::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak soft deleted', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil di-restore');
    }

    public function forceDelete($id)
    {
        $data = Pelaksana::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil di-force delete');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Pelaksana::withTrashed();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_pelaksana', 'level']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Pelaksana::onlyTrashed();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_pelaksana', 'level']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
} 