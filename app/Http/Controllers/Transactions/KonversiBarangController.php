<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\MasterData\ItemBarang;
use Exception;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use Illuminate\Support\Facades\DB;

class KonversiBarangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang']);

        if ($request->filled('search')) {
            $query->where('nama_item_barang', 'like', '%' . $request->input('search') . '%');

        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('jenis_potongan', $request->input('status'));
        }

        $query->whereIn('jenis_potongan', ['potongan', 'utuh']);
        $query->where('quantity', 1);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function update($id)
    {
        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])->find($id);

        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        DB::beginTransaction();
        try {
            $today = now()->setTimezone('Asia/Jakarta')->format('Y-m-d');

            $data->update([
                'jenis_potongan' => 'potongan',
                'convert_date' => $today
            ]);

            DB::commit();

            // Reload relasi setelah update
            $data->load(['jenisBarang', 'bentukBarang', 'gradeBarang']);

            return $this->successResponse($data, 'Stock barang berhasil dikonversi');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal mengkonversi stock barang: ' . $e->getMessage(), 500);
        }
    }

}