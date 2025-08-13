<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\Gudang;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class GudangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Gudang::query();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_gudang', 'tipe_gudang', 'telepon_hp']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_gudang,kode',
            'nama_gudang' => 'required|string',
            'tipe_gudang' => 'nullable|string',
            'parent_id' => 'nullable|exists:ref_gudang,id',
            'telepon_hp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data = Gudang::create($request->only(['kode', 'nama_gudang', 'tipe_gudang', 'parent_id', 'telepon_hp']));
        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = Gudang::with(['parent', 'children'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = Gudang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|unique:ref_gudang,kode,' . $data->id,
            'nama_gudang' => 'required|string',
            'tipe_gudang' => 'nullable|string',
            'parent_id' => [
                'nullable',
                'exists:ref_gudang,id',
                function ($attribute, $value, $fail) use ($id) {
                    if ($value == $id) {
                        $fail('Gudang tidak bisa menjadi parent dari dirinya sendiri.');
                    }
                }
            ],
            'telepon_hp' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data->update($request->only(['kode', 'nama_gudang', 'tipe_gudang', 'parent_id', 'telepon_hp']));
        return $this->successResponse($data, 'Data berhasil diupdate');
    }

    public function softDelete($id)
    {
        $data = Gudang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil di-soft delete');
    }

    public function restore($id)
    {
        $data = Gudang::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak soft deleted', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil di-restore');
    }

    public function forceDelete($id)
    {
        $data = Gudang::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil di-force delete');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Gudang::withTrashed();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_gudang', 'tipe_gudang', 'telepon_hp']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Gudang::onlyTrashed();
        $query = $this->applyFilter($query, $request, ['kode', 'nama_gudang', 'tipe_gudang', 'telepon_hp']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get parent gudang
     */
    public function getParent($id)
    {
        $data = Gudang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        $parent = $data->parent;
        if (!$parent) {
            return $this->errorResponse('Gudang ini tidak memiliki parent', 404);
        }
        
        return $this->successResponse($parent, 'Data parent gudang berhasil diambil');
    }

    /**
     * Get children gudang
     */
    public function getChildren($id)
    {
        $data = Gudang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        $children = $data->children;
        return $this->successResponse($children, 'Data children gudang berhasil diambil');
    }

    /**
     * Get all descendants recursively
     */
    public function getDescendants($id)
    {
        $data = Gudang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        $descendants = $data->descendants;
        return $this->successResponse($descendants, 'Data descendants gudang berhasil diambil');
    }

    /**
     * Get all ancestors recursively
     */
    public function getAncestors($id)
    {
        $data = Gudang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        $ancestors = $data->ancestors;
        if (!$ancestors) {
            return $this->errorResponse('Gudang ini tidak memiliki ancestors', 404);
        }
        
        return $this->successResponse($ancestors, 'Data ancestors gudang berhasil diambil');
    }

    /**
     * Get hierarchical structure (tree view)
     */
    public function getHierarchy()
    {
        $rootGudang = Gudang::whereNull('parent_id')->with('descendants')->get();
        return $this->successResponse($rootGudang, 'Data hierarki gudang berhasil diambil');
    }

    public function getTipeGudang()
    {
        $tipeGudang = [
            [
                'id' => 1,
                'kode' => 'Gudang',
                'nama' => 'Gudang',
                'deskripsi' => 'Gudang utama XXX'
            ],
            [
                'id' => 2,
                'kode' => 'Rack',
                'nama' => 'Rack',
                'deskripsi' => 'Gudang untuk XXX'
            ],
            [
                'id' => 3,
                'kode' => 'BIN',
                'nama' => 'BIN',
                'deskripsi' => 'Gudang untuk XXX'
            ]
        ];

        return $this->successResponse($tipeGudang, 'Data tipe gudang berhasil diambil');
    }
}