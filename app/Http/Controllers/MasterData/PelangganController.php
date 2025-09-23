<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\Pelanggan;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class PelangganController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Pelanggan::query();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_pelanggan', 'kota', 'telepon_hp', 'contact_person']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_pelanggan,kode',
            'nama_pelanggan' => 'required|string',
            'kota' => 'required|string',
            'telepon_hp' => 'required|string',
            'contact_person' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data = Pelanggan::create($request->only(['kode', 'nama_pelanggan', 'kota', 'telepon_hp', 'contact_person']));
        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    public function storeWithoutValidation(Request $request)
    {
        //only validate kode and nama_pelanggan
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_pelanggan,kode',
            'nama_pelanggan' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data = Pelanggan::create($request->only(['kode', 'nama_pelanggan', 'kota', 'telepon_hp', 'contact_person']));
        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = Pelanggan::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = Pelanggan::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_pelanggan,kode,' . $data->id,
            'nama_pelanggan' => 'required|string',
            'kota' => 'required|string',
            'telepon_hp' => 'required|string',
            'contact_person' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data->update($request->only(['kode', 'nama_pelanggan', 'kota', 'telepon_hp', 'contact_person']));
        return $this->successResponse($data, 'Data berhasil diupdate');
    }

    public function softDelete($id)
    {
        $data = Pelanggan::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil di-soft delete');
    }

    public function restore($id)
    {
        $data = Pelanggan::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak soft deleted', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil di-restore');
    }

    public function forceDelete($id)
    {
        $data = Pelanggan::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil di-force delete');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Pelanggan::withTrashed();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_pelanggan', 'kota', 'telepon_hp', 'contact_person']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Pelanggan::onlyTrashed();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_pelanggan', 'kota', 'telepon_hp', 'contact_person']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
} 