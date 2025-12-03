<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\BeratJenis;
use App\Models\MasterData\BentukBarang;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class BeratJenisController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = BeratJenis::with(['jenisBarang', 'bentukBarang', 'gradeBarang']);

        // Filter berdasarkan jenis_barang_id jika ada
        if ($request->filled('jenis_barang_id')) {
            $query->where('jenis_barang_id', $request->jenis_barang_id);
        }

        // Filter berdasarkan bentuk_barang_id jika ada
        if ($request->filled('bentuk_barang_id')) {
            $query->where('bentuk_barang_id', $request->bentuk_barang_id);
        }

        // Filter berdasarkan grade_barang_id jika ada
        if ($request->filled('grade_barang_id')) {
            $query->where('grade_barang_id', $request->grade_barang_id);
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'berat_per_cm' => 'nullable|numeric|min:0',
            'berat_per_luas' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Cek apakah kombinasi sudah ada
        $existing = BeratJenis::where('jenis_barang_id', $request->jenis_barang_id)
            ->where('bentuk_barang_id', $request->bentuk_barang_id)
            ->where('grade_barang_id', $request->grade_barang_id)
            ->first();

        if ($existing) {
            return $this->errorResponse('Berat jenis untuk kombinasi tersebut sudah ada', 422);
        }

        // Validasi berdasarkan dimensi bentuk barang
        $bentukBarang = BentukBarang::find($request->bentuk_barang_id);
        if (!$bentukBarang) {
            return $this->errorResponse('Bentuk barang tidak ditemukan', 422);
        }

        $dimensi = $bentukBarang->dimensi;
        
        // Validasi: untuk 1D harus ada berat_per_cm, untuk 2D harus ada berat_per_luas
        if ($dimensi == '1D') {
            if (!$request->filled('berat_per_cm') || $request->berat_per_cm == null) {
                return $this->errorResponse('Berat per cm wajib diisi untuk barang 1D', 422);
            }
        } else {
            if (!$request->filled('berat_per_luas') || $request->berat_per_luas == null) {
                return $this->errorResponse('Berat per luas wajib diisi untuk plat (2D)', 422);
            }
        }

        $data = BeratJenis::create($request->only([
            'jenis_barang_id',
            'bentuk_barang_id',
            'grade_barang_id',
            'berat_per_cm',
            'berat_per_luas'
        ]));

        $data->load(['jenisBarang', 'bentukBarang', 'gradeBarang']);

        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = BeratJenis::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = BeratJenis::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'jenis_barang_id' => 'sometimes|required|exists:ref_jenis_barang,id',
            'bentuk_barang_id' => 'sometimes|required|exists:ref_bentuk_barang,id',
            'grade_barang_id' => 'sometimes|required|exists:ref_grade_barang,id',
            'berat_per_cm' => 'nullable|numeric|min:0',
            'berat_per_luas' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Jika kombinasi berubah, cek apakah kombinasi baru sudah ada
        $jenisBarangId = $request->filled('jenis_barang_id') ? $request->jenis_barang_id : $data->jenis_barang_id;
        $bentukBarangId = $request->filled('bentuk_barang_id') ? $request->bentuk_barang_id : $data->bentuk_barang_id;
        $gradeBarangId = $request->filled('grade_barang_id') ? $request->grade_barang_id : $data->grade_barang_id;

        if ($jenisBarangId != $data->jenis_barang_id || 
            $bentukBarangId != $data->bentuk_barang_id || 
            $gradeBarangId != $data->grade_barang_id) {
            
            $existing = BeratJenis::where('jenis_barang_id', $jenisBarangId)
                ->where('bentuk_barang_id', $bentukBarangId)
                ->where('grade_barang_id', $gradeBarangId)
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return $this->errorResponse('Berat jenis untuk kombinasi tersebut sudah ada', 422);
            }
        }

        // Validasi berdasarkan dimensi bentuk barang
        $bentukBarang = BentukBarang::find($bentukBarangId);
        if (!$bentukBarang) {
            return $this->errorResponse('Bentuk barang tidak ditemukan', 422);
        }

        $dimensi = $bentukBarang->dimensi;
        $beratPerCm = $request->filled('berat_per_cm') ? $request->berat_per_cm : $data->berat_per_cm;
        $beratPerLuas = $request->filled('berat_per_luas') ? $request->berat_per_luas : $data->berat_per_luas;

        // Validasi: untuk 1D harus ada berat_per_cm, untuk 2D harus ada berat_per_luas
        if ($dimensi == '1D') {
            if ($beratPerCm == null) {
                return $this->errorResponse('Berat per cm wajib diisi untuk barang 1D', 422);
            }
        } else {
            if ($beratPerLuas == null) {
                return $this->errorResponse('Berat per luas wajib diisi untuk plat (2D)', 422);
            }
        }

        $updateData = array_filter($request->only([
            'jenis_barang_id',
            'bentuk_barang_id',
            'grade_barang_id',
            'berat_per_cm',
            'berat_per_luas'
        ]), function ($value) {
            return $value !== null && $value !== '';
        });

        $data->update($updateData);
        $data->load(['jenisBarang', 'bentukBarang', 'gradeBarang']);

        return $this->successResponse($data, 'Data berhasil diperbarui');
    }

    public function softDelete($id)
    {
        $data = BeratJenis::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil dihapus');
    }

    public function restore($id)
    {
        $data = BeratJenis::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak dihapus', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil dipulihkan');
    }

    public function forceDelete($id)
    {
        $data = BeratJenis::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil dihapus permanen');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = BeratJenis::withTrashed()->with(['jenisBarang', 'bentukBarang', 'gradeBarang']);

        // Filter berdasarkan jenis_barang_id jika ada
        if ($request->filled('jenis_barang_id')) {
            $query->where('jenis_barang_id', $request->jenis_barang_id);
        }

        // Filter berdasarkan bentuk_barang_id jika ada
        if ($request->filled('bentuk_barang_id')) {
            $query->where('bentuk_barang_id', $request->bentuk_barang_id);
        }

        // Filter berdasarkan grade_barang_id jika ada
        if ($request->filled('grade_barang_id')) {
            $query->where('grade_barang_id', $request->grade_barang_id);
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = BeratJenis::onlyTrashed()->with(['jenisBarang', 'bentukBarang', 'gradeBarang']);

        // Filter berdasarkan jenis_barang_id jika ada
        if ($request->filled('jenis_barang_id')) {
            $query->where('jenis_barang_id', $request->jenis_barang_id);
        }

        // Filter berdasarkan bentuk_barang_id jika ada
        if ($request->filled('bentuk_barang_id')) {
            $query->where('bentuk_barang_id', $request->bentuk_barang_id);
        }

        // Filter berdasarkan grade_barang_id jika ada
        if ($request->filled('grade_barang_id')) {
            $query->where('grade_barang_id', $request->grade_barang_id);
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
}
