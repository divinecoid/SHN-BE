<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;
use App\Http\Controllers\MasterData\DocumentSequenceController;

class ItemBarangController extends Controller
{
    use ApiFilterTrait;
    protected $documentSequenceController;

    public function __construct(DocumentSequenceController $documentSequenceController)
    {
        $this->documentSequenceController = $documentSequenceController;
    }

    public function index(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Filter berdasarkan gudang_id jika ada
        if ($request->has('gudang_id') && $request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        }

        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);
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
            'panjang' => 'nullable|numeric|min:0',
            'lebar' => 'nullable|numeric|min:0',
            'tebal' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'quantity_tebal_sama' => 'nullable|numeric|min:0',
            'jenis_potongan' => 'nullable|string',
            'is_edit' => 'nullable|boolean',
            'is_onprogress_po' => 'nullable|boolean',
            'user_id' => 'nullable|exists:users,id',
            'gudang_id' => 'nullable|exists:ref_gudang,id',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // ambil data relasi
        $jenis_barang = JenisBarang::find($request->jenis_barang_id);
        $bentuk_barang = BentukBarang::find($request->bentuk_barang_id);
        $grade_barang = GradeBarang::find($request->grade_barang_id);
        if (!$jenis_barang || !$bentuk_barang || !$grade_barang) {
            return $this->errorResponse('Data relasi jenis/bentuk/grade barang tidak ditemukan', 422);
        }

        $dimensi = $bentuk_barang->dimensi;
        if ($dimensi == '1D') {
            $nama_item_barang = $bentuk_barang->kode . '-' . $jenis_barang->kode . '-' . $grade_barang->kode . '-' . $request->panjang . 'x' . $request->tebal;
        } else {
            $nama_item_barang = $bentuk_barang->kode . '-' . $jenis_barang->kode . '-' . $grade_barang->kode . '-' . $request->panjang . 'x' . $request->lebar . 'x' . $request->tebal;
        }
        if ($dimensi == '1D') {
            $sisa_luas = $request->panjang * $request->tebal;
        } else {
            $sisa_luas = $request->panjang * $request->lebar * $request->tebal;
        }
        // Generate nomor sequence barang
        $sequenceBarangResponse = $this->documentSequenceController->generateDocumentSequence('barang');
        if (method_exists($sequenceBarangResponse, 'getStatusCode') && $sequenceBarangResponse->getStatusCode() !== 200) {
            return $this->errorResponse('Gagal generate nomor barang', 500);
        }
        $sequenceBarangData = $sequenceBarangResponse->getData();
        $sequenceBarangNomor = isset($sequenceBarangData->data) ? $sequenceBarangData->data : null;
        if (!$sequenceBarangNomor) {
            return $this->errorResponse('Gagal mendapatkan nomor urut barang', 500);
        }

        $kode_barang = $nama_item_barang . '-' . $sequenceBarangNomor;

        // Rakitan data untuk store, tambahkan kode_barang secara _eksplisit_ pada array yang diinsert
        $input = $request->only([
            'jenis_barang_id', 'bentuk_barang_id', 'grade_barang_id',
            'nama_item_barang', 'sisa_luas', 'panjang', 'lebar', 'tebal',
            'quantity', 'jenis_potongan',
            'is_edit', 'is_onprogress_po', 'user_id', 'gudang_id'
        ]);
        $input['kode_barang'] = $kode_barang;
        $input['nama_item_barang'] = $nama_item_barang;
        $input['sisa_luas'] = $sisa_luas;

        $data = ItemBarang::create($input);

        $data->load(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Tambah urutan sequence barang (counter) supaya tidak double
        $this->documentSequenceController->increaseSequence('barang');

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
            'kode_barang' => 'required|string|unique:ref_item_barang,kode_barang,' . $data->id,
            'jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'nama_item_barang' => 'required|string',
            'sisa_luas' => 'nullable|numeric|min:0',
            'panjang' => 'nullable|numeric|min:0',
            'lebar' => 'nullable|numeric|min:0',
            'tebal' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'quantity_tebal_sama' => 'nullable|numeric|min:0',
            'jenis_potongan' => 'nullable|string',
            'is_edit' => 'nullable|boolean',
            'user_id' => 'nullable|exists:users,id',
            'gudang_id' => 'nullable|exists:ref_gudang,id',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        // Hanya update field yang dikirim dalam request
        $updateData = array_filter($request->only([
            'kode_barang',
            'jenis_barang_id',
            'bentuk_barang_id',
            'grade_barang_id',
            'nama_item_barang',
            'sisa_luas',
            'panjang',
            'lebar',
            'tebal',
            'quantity',
            'quantity_tebal_sama',
            'jenis_potongan',
            'is_edit',
            'user_id',
            'gudang_id'
        ]), function ($value) {
            return $value !== null && $value !== '';
        });
        $data->update($updateData);
        $data->load(['jenisBarang', 'bentukBarang', 'gradeBarang']);
        return $this->successResponse($data, 'Data berhasil diperbarui');
    }

    public function softDelete($id)
    {
        $data = ItemBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil dihapus');
    }

    public function restore($id)
    {
        $data = ItemBarang::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak dihapus', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil dipulihkan');
    }

    public function forceDelete($id)
    {
        $data = ItemBarang::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil dihapus permanen');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::withTrashed()->with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Filter berdasarkan gudang_id jika ada
        if ($request->has('gudang_id') && $request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        }

        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::onlyTrashed()->with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Filter berdasarkan gudang_id jika ada
        if ($request->has('gudang_id') && $request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        }

        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
    public function similarType(Request $request, $id)
    {
        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        $query->where([
            ['jenis_barang_id', '=', $data->jenis_barang_id],
            ['bentuk_barang_id', '=', $data->bentuk_barang_id],
            ['grade_barang_id', '=', $data->grade_barang_id],
            ['tebal', '=', $data->tebal],
            ['jenis_potongan', '=', 'utuh'],
        ]);

        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

        public function bulk(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Filter berdasarkan gudang_id jika ada
        if ($request->has('gudang_id') && $request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        }

        $query->where('quantity', '>', 1);
        $query->where('jenis_potongan', 'utuh');

        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
}