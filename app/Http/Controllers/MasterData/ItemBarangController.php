<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\ItemBarangGroup;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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

        $jenis = $request->input('jenis_potongan', $request->input('tipe'));
        if ($jenis !== null && $jenis !== '') {
            $query->where('jenis_potongan', $jenis);
        }

        $jenisBarangId = $request->input('jenis_barang_id', $request->input('tipe_barang'));
        if ($jenisBarangId !== null && $jenisBarangId !== '') {
            $query->where('jenis_barang_id', $jenisBarangId);
        }

        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', $request->input('min_quantity'));
        }
        if ($request->filled('max_quantity')) {
            $query->where('quantity', '<=', $request->input('max_quantity'));
        }
        if ($request->filled('quantity')) {
            $query->where('quantity', $request->input('quantity'));
        }
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function checkStock(Request $request)
    {
        // Validasi parameter yang diperlukan
        $validator = Validator::make($request->all(), [
            'gudang_id' => 'nullable|exists:ref_gudang,id',
            'jenis_barang_id' => 'nullable|exists:ref_jenis_barang,id',
            'bentuk_barang_id' => 'nullable|exists:ref_bentuk_barang,id',
            'grade_barang_id' => 'nullable|exists:ref_grade_barang,id',
            'panjang' => 'nullable|numeric|min:0',
            'lebar' => 'nullable|numeric|min:0',
            'tebal' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Build query dengan relasi
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Filter opsional untuk gudang
        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        // Filter opsional untuk jenis barang
        if ($request->filled('jenis_barang_id')) {
            $query->where('jenis_barang_id', $request->jenis_barang_id);
        }

        // Filter opsional untuk bentuk barang
        if ($request->filled('bentuk_barang_id')) {
            $query->where('bentuk_barang_id', $request->bentuk_barang_id);
        }

        // Filter opsional untuk grade barang
        if ($request->filled('grade_barang_id')) {
            $query->where('grade_barang_id', $request->grade_barang_id);
        }

        // Filter opsional untuk dimensi
        if ($request->filled('panjang')) {
            $query->where('panjang', $request->panjang);
        }

        if ($request->filled('lebar')) {
            $query->where('lebar', $request->lebar);
        }

        if ($request->filled('tebal')) {
            $query->where('tebal', $request->tebal);
        }

        // Ambil semua data yang sesuai dengan filter
        $data = $query->get();

        if ($data->isEmpty()) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        return $this->successResponse($data);
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
            'berat' => 'nullable|numeric|min:0',
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
            'berat',
            'quantity',
            'jenis_potongan',
            'is_edit',
            'is_onprogress_po',
            'user_id',
            'gudang_id'
        ]);
        $input['kode_barang'] = $kode_barang;
        $input['nama_item_barang'] = $nama_item_barang;
        $input['sisa_luas'] = $sisa_luas;


        $data = ItemBarang::create($input);

        $data->load(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Tambah urutan sequence barang (counter) supaya tidak double
        $this->documentSequenceController->increaseSequence('barang');

        // Update atau create group berdasarkan kombinasi jenis, bentuk, grade, panjang, lebar, tebal
        $this->syncItemBarangGroup($data->jenis_barang_id, $data->bentuk_barang_id, $data->grade_barang_id, $data->panjang, $data->lebar, $data->tebal);

        return $this->successResponse($data, 'Data berhasil ditambahkan');
    }

    /**
     * Sync item barang group berdasarkan kombinasi jenis, bentuk, grade, panjang, lebar, tebal
     */
    private function syncItemBarangGroup($jenisBarangId, $bentukBarangId, $gradeBarangId, $panjang, $lebar, $tebal)
    {
        // Hitung quantity_utuh dan quantity_potongan dari semua item barang dengan kombinasi yang sama
        $quantityUtuh = ItemBarang::where('jenis_barang_id', $jenisBarangId)
            ->where('bentuk_barang_id', $bentukBarangId)
            ->where('grade_barang_id', $gradeBarangId)
            ->where('panjang', $panjang)
            ->where('tebal', $tebal)
            ->where(function($query) use ($lebar) {
                if ($lebar !== null) {
                    $query->where('lebar', $lebar);
                } else {
                    $query->whereNull('lebar');
                }
            })
            ->where('jenis_potongan', 'utuh')
            ->sum('quantity');

        $quantityPotongan = ItemBarang::where('jenis_barang_id', $jenisBarangId)
            ->where('bentuk_barang_id', $bentukBarangId)
            ->where('grade_barang_id', $gradeBarangId)
            ->where('panjang', $panjang)
            ->where('tebal', $tebal)
            ->where(function($query) use ($lebar) {
                if ($lebar !== null) {
                    $query->where('lebar', $lebar);
                } else {
                    $query->whereNull('lebar');
                }
            })
            ->where(function($query) {
                $query->where('jenis_potongan', '!=', 'utuh')
                    ->orWhereNull('jenis_potongan');
            })
            ->sum('quantity');

        // Cari atau buat group
        $query = ItemBarangGroup::where('jenis_barang_id', $jenisBarangId)
            ->where('bentuk_barang_id', $bentukBarangId)
            ->where('grade_barang_id', $gradeBarangId)
            ->where('panjang', (int) $panjang)
            ->where('tebal', (int) $tebal);

        if ($lebar !== null) {
            $query->where('lebar', (int) $lebar);
        } else {
            $query->whereNull('lebar');
        }

        $itemBarangGroup = $query->first();

        if (!$itemBarangGroup) {
            // Buat group baru jika belum ada
            $itemBarangGroup = new ItemBarangGroup();
            $itemBarangGroup->jenis_barang_id = $jenisBarangId;
            $itemBarangGroup->bentuk_barang_id = $bentukBarangId;
            $itemBarangGroup->grade_barang_id = $gradeBarangId;
            $itemBarangGroup->panjang = (int) $panjang;
            $itemBarangGroup->lebar = $lebar ? (int) $lebar : null;
            $itemBarangGroup->tebal = (int) $tebal;
        }

        $itemBarangGroup->quantity_utuh = (int) $quantityUtuh;
        $itemBarangGroup->quantity_potongan = (int) $quantityPotongan;
        $itemBarangGroup->save();
    }

    public function generateGroup(Request $request)
    {
        // Grouping dari semua item barang yang ada berdasarkan:
        // jenis_barang_id, bentuk_barang_id, grade_barang_id, panjang, lebar (opsional), tebal
        
        $groups = ItemBarang::select(
                'jenis_barang_id',
                'bentuk_barang_id',
                'grade_barang_id',
                'panjang',
                'lebar',
                'tebal',
                DB::raw('SUM(CASE WHEN jenis_potongan = "utuh" THEN quantity ELSE 0 END) as quantity_utuh'),
                DB::raw('SUM(CASE WHEN jenis_potongan != "utuh" OR jenis_potongan IS NULL THEN quantity ELSE 0 END) as quantity_potongan')
            )
            ->groupBy('jenis_barang_id', 'bentuk_barang_id', 'grade_barang_id', 'panjang', 'lebar', 'tebal')
            ->get();

        $createdGroups = [];
        $updatedGroups = [];
        $allGroups = [];

        foreach ($groups as $group) {
            // Cari atau buat group di tabel ref_item_barang_group
            // Untuk lebar yang bisa null, gunakan whereNull jika null
            $query = ItemBarangGroup::where('jenis_barang_id', $group->jenis_barang_id)
                ->where('bentuk_barang_id', $group->bentuk_barang_id)
                ->where('grade_barang_id', $group->grade_barang_id)
                ->where('panjang', (int) $group->panjang)
                ->where('tebal', (int) $group->tebal);

            if ($group->lebar !== null) {
                $query->where('lebar', (int) $group->lebar);
            } else {
                $query->whereNull('lebar');
            }

            $itemBarangGroup = $query->first();

            if (!$itemBarangGroup) {
                $itemBarangGroup = new ItemBarangGroup();
                $itemBarangGroup->jenis_barang_id = $group->jenis_barang_id;
                $itemBarangGroup->bentuk_barang_id = $group->bentuk_barang_id;
                $itemBarangGroup->grade_barang_id = $group->grade_barang_id;
                $itemBarangGroup->panjang = (int) $group->panjang;
                $itemBarangGroup->lebar = $group->lebar ? (int) $group->lebar : null;
                $itemBarangGroup->tebal = (int) $group->tebal;
                $isNew = true;
            } else {
                $isNew = false;
            }

            $itemBarangGroup->quantity_utuh = (int) $group->quantity_utuh;
            $itemBarangGroup->quantity_potongan = (int) $group->quantity_potongan;
            $itemBarangGroup->save();

            $itemBarangGroup->load(['jenisBarang', 'bentukBarang', 'gradeBarang']);

            if ($isNew) {
                $createdGroups[] = $itemBarangGroup;
            } else {
                $updatedGroups[] = $itemBarangGroup;
            }

            $allGroups[] = $itemBarangGroup;
        }

        return $this->successResponse([
            'total_groups' => count($allGroups),
            'created' => count($createdGroups),
            'updated' => count($updatedGroups),
            'groups' => $allGroups
        ], 'Grouping item barang berhasil dibuat/diperbarui');
    }

    public function indexGroup(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarangGroup::with(['jenisBarang', 'bentukBarang', 'gradeBarang']);

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

        // Filter berdasarkan panjang jika ada
        if ($request->filled('panjang')) {
            $query->where('panjang', $request->panjang);
        }

        // Filter berdasarkan lebar jika ada
        if ($request->filled('lebar')) {
            $query->where('lebar', $request->lebar);
        } else if ($request->has('lebar') && $request->lebar === null) {
            // Jika lebar secara eksplisit null (untuk filter item tanpa lebar)
            $query->whereNull('lebar');
        }

        // Filter berdasarkan tebal jika ada
        if ($request->filled('tebal')) {
            $query->where('tebal', $request->tebal);
        }

        // Filter berdasarkan min quantity_utuh
        if ($request->filled('min_quantity_utuh')) {
            $query->where('quantity_utuh', '>=', $request->input('min_quantity_utuh'));
        }

        // Filter berdasarkan max quantity_utuh
        if ($request->filled('max_quantity_utuh')) {
            $query->where('quantity_utuh', '<=', $request->input('max_quantity_utuh'));
        }

        // Filter berdasarkan min quantity_potongan
        if ($request->filled('min_quantity_potongan')) {
            $query->where('quantity_potongan', '>=', $request->input('min_quantity_potongan'));
        }

        // Filter berdasarkan max quantity_potongan
        if ($request->filled('max_quantity_potongan')) {
            $query->where('quantity_potongan', '<=', $request->input('max_quantity_potongan'));
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        
        return response()->json($this->paginateResponse($data, $items));
    }

    public function showGroup(Request $request, $id)
    {
        $data = ItemBarangGroup::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])->find($id);
        
        if (!$data) {
            return $this->errorResponse('Data group tidak ditemukan', 404);
        }
        
        return $this->successResponse($data);
    }

    public function show(Request $request, $id)
    {
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Filter berdasarkan gudang_id jika ada
        if ($request->has('gudang_id') && $request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        }

        $data = $query->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $query = ItemBarang::query();

        // Filter berdasarkan gudang_id jika ada
        if ($request->has('gudang_id') && $request->gudang_id) {
            $query->where('gudang_id', $request->gudang_id);
        }

        $data = $query->find($id);
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
            'berat' => 'nullable|numeric|min:0',
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
            'berat',
            'quantity',
            'quantity_tebal_sama',
            'jenis_potongan',
            'is_edit',
            'user_id',
            'gudang_id'
        ]), function ($value) {
            return $value !== null && $value !== '';
        });
        
        // Simpan nilai lama untuk sync group setelah update
        $oldJenisBarangId = $data->jenis_barang_id;
        $oldBentukBarangId = $data->bentuk_barang_id;
        $oldGradeBarangId = $data->grade_barang_id;
        $oldPanjang = $data->panjang;
        $oldLebar = $data->lebar;
        $oldTebal = $data->tebal;
        
        $data->update($updateData);
        $data->load(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);
        
        // Sync group untuk kombinasi baru setelah update
        $newJenisBarangId = $data->jenis_barang_id;
        $newBentukBarangId = $data->bentuk_barang_id;
        $newGradeBarangId = $data->grade_barang_id;
        $newPanjang = $data->panjang;
        $newLebar = $data->lebar;
        $newTebal = $data->tebal;
        
        // Sync group untuk kombinasi baru
        $this->syncItemBarangGroup($newJenisBarangId, $newBentukBarangId, $newGradeBarangId, $newPanjang, $newLebar, $newTebal);
        
        // Jika kombinasi berubah, sync juga group lama
        if ($oldJenisBarangId != $newJenisBarangId || 
            $oldBentukBarangId != $newBentukBarangId || 
            $oldGradeBarangId != $newGradeBarangId || 
            $oldPanjang != $newPanjang || 
            $oldLebar != $newLebar || 
            $oldTebal != $newTebal) {
            $this->syncItemBarangGroup($oldJenisBarangId, $oldBentukBarangId, $oldGradeBarangId, $oldPanjang, $oldLebar, $oldTebal);
        }
        
        return $this->successResponse($data, 'Data berhasil diperbarui');
    }

    public function softDelete($id)
    {
        $data = ItemBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        // Simpan nilai untuk sync group setelah delete
        $jenisBarangId = $data->jenis_barang_id;
        $bentukBarangId = $data->bentuk_barang_id;
        $gradeBarangId = $data->grade_barang_id;
        $panjang = $data->panjang;
        $lebar = $data->lebar;
        $tebal = $data->tebal;
        
        $data->delete();
        
        // Sync group setelah item dihapus
        $this->syncItemBarangGroup($jenisBarangId, $bentukBarangId, $gradeBarangId, $panjang, $lebar, $tebal);
        
        return $this->successResponse(null, 'Data berhasil dihapus');
    }

    public function restore($id)
    {
        $data = ItemBarang::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak dihapus', 404);
        }
        
        // Simpan nilai untuk sync group setelah restore
        $jenisBarangId = $data->jenis_barang_id;
        $bentukBarangId = $data->bentuk_barang_id;
        $gradeBarangId = $data->grade_barang_id;
        $panjang = $data->panjang;
        $lebar = $data->lebar;
        $tebal = $data->tebal;
        
        $data->restore();
        
        // Sync group setelah item dipulihkan
        $this->syncItemBarangGroup($jenisBarangId, $bentukBarangId, $gradeBarangId, $panjang, $lebar, $tebal);
        
        return $this->successResponse($data, 'Data berhasil dipulihkan');
    }

    public function forceDelete($id)
    {
        $data = ItemBarang::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        // Simpan nilai untuk sync group setelah force delete
        $jenisBarangId = $data->jenis_barang_id;
        $bentukBarangId = $data->bentuk_barang_id;
        $gradeBarangId = $data->grade_barang_id;
        $panjang = $data->panjang;
        $lebar = $data->lebar;
        $tebal = $data->tebal;
        
        $data->forceDelete();
        
        // Sync group setelah item dihapus permanen
        $this->syncItemBarangGroup($jenisBarangId, $bentukBarangId, $gradeBarangId, $panjang, $lebar, $tebal);
        
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
        $jenis = $request->input('jenis_potongan', $request->input('tipe'));
        if ($jenis !== null && $jenis !== '') {
            $query->where('jenis_potongan', $jenis);
        }

        $jenisBarangId = $request->input('jenis_barang_id', $request->input('tipe_barang'));
        if ($jenisBarangId !== null && $jenisBarangId !== '') {
            $query->where('jenis_barang_id', $jenisBarangId);
        }
        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', $request->input('min_quantity'));
        }
        if ($request->filled('max_quantity')) {
            $query->where('quantity', '<=', $request->input('max_quantity'));
        }
        if ($request->filled('quantity')) {
            $query->where('quantity', $request->input('quantity'));
        }
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
        $jenis = $request->input('jenis_potongan', $request->input('tipe'));
        if ($jenis !== null && $jenis !== '') {
            $query->where('jenis_potongan', $jenis);
        }

        $jenisBarangId = $request->input('jenis_barang_id', $request->input('tipe_barang'));
        if ($jenisBarangId !== null && $jenisBarangId !== '') {
            $query->where('jenis_barang_id', $jenisBarangId);
        }
        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', $request->input('min_quantity'));
        }
        if ($request->filled('max_quantity')) {
            $query->where('quantity', '<=', $request->input('max_quantity'));
        }
        if ($request->filled('quantity')) {
            $query->where('quantity', $request->input('quantity'));
        }
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function mergeable(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        $query->where('jenis_potongan', 'utuh');

        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_barang', 'like', "%{$search}%")
                  ->orWhere('nama_item_barang', 'like', "%{$search}%")
                  ->orWhereHas('gudang', function ($qq) use ($search) {
                      $qq->where('nama_gudang', 'like', "%{$search}%")
                         ->orWhere('kode', 'like', "%{$search}%");
                  })
                  ->orWhereHas('jenisBarang', function ($qq) use ($search) {
                      $qq->where('kode', 'like', "%{$search}%");
                  })
                  ->orWhereHas('bentukBarang', function ($qq) use ($search) {
                      $qq->where('kode', 'like', "%{$search}%");
                  })
                  ->orWhereHas('gradeBarang', function ($qq) use ($search) {
                      $qq->where('kode', 'like', "%{$search}%");
                  });
            });
        }

        // Conditional pagination: paginate only if per_page or page provided; otherwise return all on a single page
        $shouldPaginate = $request->filled('per_page') || $request->filled('page');
        if (!$shouldPaginate) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));

    }

    public function similarType(Request $request, $id)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang'])->find($id);
        if (!$data) {
            return $this->successResponse(null, 'Data tidak ditemukan');
        }
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        $query->where('gudang_id', $data->gudang_id);

        $query->where([
            ['jenis_barang_id', '=', $data->jenis_barang_id],
            ['bentuk_barang_id', '=', $data->bentuk_barang_id],
            ['grade_barang_id', '=', $data->grade_barang_id],
            ['tebal', '=', $data->tebal],
            ['jenis_potongan', '=', 'utuh'],
        ]);

        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_barang', 'like', "%{$search}%")
                  ->orWhere('nama_item_barang', 'like', "%{$search}%")
                  ->orWhereHas('gudang', function ($qq) use ($search) {
                      $qq->where('nama_gudang', 'like', "%{$search}%")
                         ->orWhere('kode', 'like', "%{$search}%");
                  })
                  ->orWhereHas('jenisBarang', function ($qq) use ($search) {
                      $qq->where('kode', 'like', "%{$search}%");
                  })
                  ->orWhereHas('bentukBarang', function ($qq) use ($search) {
                      $qq->where('kode', 'like', "%{$search}%");
                  })
                  ->orWhereHas('gradeBarang', function ($qq) use ($search) {
                      $qq->where('kode', 'like', "%{$search}%");
                  });
            });
        }

        $shouldPaginate = $request->filled('per_page') || $request->filled('page');
        if (!$shouldPaginate) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        $dataPaginated = $query->paginate($perPage);
        $items = collect($dataPaginated->items());
        return response()->json($this->paginateResponse($dataPaginated, $items));
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

    public function getByGudang(Request $request, $gudangId)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'gudang']);

        // Filter berdasarkan gudang_id (required)
        $query->where('gudang_id', $gudangId);

        // Search functionality untuk nama_item_barang
        if ($request->filled('search')) {
            $query->where('nama_item_barang', 'like', '%' . $request->input('search') . '%');
        }

        // Apply additional filters and sorting
        $query = $this->applyFilter($query, $request, ['kode_barang', 'nama_item_barang']);

        $jenis = $request->input('jenis_potongan', $request->input('tipe'));
        if ($jenis !== null && $jenis !== '') {
            $query->where('jenis_potongan', $jenis);
        }

        $jenisBarangId = $request->input('jenis_barang_id', $request->input('tipe_barang'));
        if ($jenisBarangId !== null && $jenisBarangId !== '') {
            $query->where('jenis_barang_id', $jenisBarangId);
        }
        if ($request->filled('min_quantity')) {
            $query->where('quantity', '>=', $request->input('min_quantity'));
        }
        if ($request->filled('max_quantity')) {
            $query->where('quantity', '<=', $request->input('max_quantity'));
        }
        if ($request->filled('quantity')) {
            $query->where('quantity', $request->input('quantity'));
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());

        return response()->json($this->paginateResponse($data, $items));
    }

    public function freezeItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gudang_id' => 'required|exists:ref_gudang,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $gudangId = $request->input('gudang_id');
        $currentUserId = auth()->id();

        if (!$currentUserId) {
            return $this->errorResponse('User tidak terautentikasi', 401);
        }

        // Update semua items yang memiliki gudang_id tersebut
        $updatedCount = ItemBarang::where('gudang_id', $gudangId)
            ->update([
                'frozen_at' => now(),
                'frozen_by' => $currentUserId
            ]);

        return $this->successResponse([
            'updated_count' => $updatedCount,
            'gudang_id' => $gudangId
        ], 'Barang berhasil dibekukan');
    }

    public function unfreezeItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gudang_id' => 'required|exists:ref_gudang,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $gudangId = $request->input('gudang_id');

        // Update semua items yang memiliki gudang_id tersebut, set frozen_at dan frozen_by menjadi null
        $updatedCount = ItemBarang::where('gudang_id', $gudangId)
            ->update([
                'frozen_at' => null,
                'frozen_by' => null
            ]);

        return $this->successResponse([
            'updated_count' => $updatedCount,
            'gudang_id' => $gudangId
        ], 'Barang berhasil dilepaskan dari status beku');
    }

}
