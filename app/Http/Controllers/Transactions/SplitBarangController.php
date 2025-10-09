<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\MasterData\ItemBarang;
use Auth;
use Exception;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use Illuminate\Support\Facades\DB;

class SplitBarangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int) ($request->input('per_page', $this->getPerPageDefault()));
        $query = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang']);

        if ($request->filled('search')) {
            $query->where('nama_item_barang', 'like', '%' . $request->input('search') . '%');
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'quantity' => 'required|numeric|min:0'
        ]);

        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])->find($id);

        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        // memberikan nama 001 002 003 di belakang nama item barang hasil copy
        $all_splitted_data = ItemBarang::whereLike('kode_barang', $data->kode_barang)->count();

        $new_item_quantity = $request->input('quantity');
        DB::beginTransaction();
        try {

            $today = now()->setTimezone('Asia/Jakarta')->format('Y-m-d');

            //barang baru
            $splitted_item_barang = $data->replicate();
            $splitted_item_barang->kode_barang .= ' - ' . str_pad($all_splitted_data, 3, '0', STR_PAD_LEFT);
            $splitted_item_barang->quantity = $new_item_quantity;
            $splitted_item_barang->split_date = $today;

            //barang lama
            $data->quantity -= $new_item_quantity;

            $splitted_item_barang->save();
            $data->save();

            DB::commit();

            // Reload relasi setelah update
            $splitted_item_barang->load(['jenisBarang', 'bentukBarang', 'gradeBarang']);

            return $this->successResponse($splitted_item_barang, 'Stock barang berhasil displit');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal split stock barang: ' . $e->getMessage(), 500);
        }
    }
}