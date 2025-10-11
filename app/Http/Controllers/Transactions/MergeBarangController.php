<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\MasterData\ItemBarang;
use Auth;
use Exception;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use Illuminate\Support\Facades\DB;

class MergeBarangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang']);

        if ($request->filled('search')) {
            $query->where('nama_item_barang', 'like', '%' . $request->input('search') . '%');
        }
        $query->whereNotNull('merge_date');

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id_1' => 'required',
            'id_2' => 'required'
        ]);

        $data_1 = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])->find($request->input('id_1'));
        $data_2 = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])->find($request->input('id_2'));

        if (!$data_1 || !$data_2) {
            return $this->errorResponse('Salah satu data tidak ditemukan', 404);
        }

        $fields = [
            'jenis_barang_id',
            'bentuk_barang_id',
            'grade_barang_id',
            'tebal',
            'jenis_potongan',
        ];

        foreach ($fields as $field) {
            if ($data_1->$field != $data_2->$field) {
                return $this->errorResponse('Barang memiliki jenis/bentuk/grade/tebal/jenis potongan yang tidak sama', 404);
            }
        }

        if ($data_1->jenis_potongan != 'utuh') {
            return $this->errorResponse('Barang memiliki jenis/bentuk/grade/tebal/jenis potongan yang tidak sama', 404);
        }

        $all_splitted_data = ItemBarang::where('kode_barang', 'LIKE', "%{$data_1->kode_barang}%")->count();

        DB::beginTransaction();
        try {

            $today = now()->setTimezone('Asia/Jakarta')->format('Y-m-d');

            //barang baru
            $data_new = $data_1->replicate();
            $data_new->quantity = $data_1->quantity + $data_2->quantity;
            $data_new->kode_barang .= ' - ' . str_pad($all_splitted_data, 3, '0', STR_PAD_LEFT);
            $data_new->merge_date = $today;

            $data_new->save();

            //barang 1
            $data_1->delete();

            //barang 2
            $data_2->delete();

            DB::commit();

            // Reload relasi setelah update
            $data_new->load(['jenisBarang', 'bentukBarang', 'gradeBarang']);

            return $this->successResponse($data_new, 'Stock barang berhasil displit');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal split stock barang: ' . $e->getMessage(), 500);
        }
    }
}