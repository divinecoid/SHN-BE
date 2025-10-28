<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use App\Models\Transactions\PurchaseOrder;
use App\Models\Transactions\PurchaseOrderItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\MasterData\DocumentSequenceController;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;

class PurchaseOrderController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PurchaseOrder::with(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier']);
        $query = $this->applyFilter($query, $request, ['nomor_po', 'tanggal_po', 'tanggal_jatuh_tempo', 'status']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_po' => 'nullable|date',
            'tanggal_jatuh_tempo' => 'required|date',
            'id_supplier' => 'required|exists:ref_supplier,id',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
            'catatan' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.qty' => 'required|integer|min:0',
            'items.*.panjang' => 'required|numeric|min:0',
            'items.*.lebar' => 'nullable|numeric|min:0',
            'items.*.tebal' => 'required|numeric|min:0',
            'items.*.jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'items.*.bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'items.*.grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.satuan' => 'nullable|string|max:50',
            'items.*.diskon' => 'nullable|numeric|min:0|max:100',
            'items.*.catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            // Generate nomor_po menggunakan DocumentSequenceController
            $nomorPoResponse = app(DocumentSequenceController::class)->generateDocumentSequence('po');
            if ($nomorPoResponse->getStatusCode() !== 200) {
                return $this->errorResponse('Gagal generate nomor PO', 500);
            }
            $nomorPo = $nomorPoResponse->getData()->data;

            // Membuat header Purchase Order dengan default values
            $purchaseOrderData = $request->only([
                'tanggal_jatuh_tempo',
                'id_supplier',
                'total_amount',
                'catatan',
            ]);
            $purchaseOrderData['nomor_po'] = $nomorPo;
            $purchaseOrderData['tanggal_po'] = $request->tanggal_po ?? now()->format('Y-m-d');
            $purchaseOrderData['status'] = $request->status ?? 'draft';
            $purchaseOrder = PurchaseOrder::create($purchaseOrderData);

            // Jika ada items, simpan items
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $poItem = PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'qty' => $item['qty'] ?? 0,
                        'panjang' => $item['panjang'] ?? 0,
                        'lebar' => $item['lebar'] ?? null,
                        'tebal' => $item['tebal'] ?? 0,
                        'jenis_barang_id' => $item['jenis_barang_id'] ?? null,
                        'bentuk_barang_id' => $item['bentuk_barang_id'] ?? null,
                        'grade_barang_id' => $item['grade_barang_id'] ?? null,
                        'harga' => $item['harga'] ?? 0,
                        'satuan' => $item['satuan'] ?? 'PCS',
                        'diskon' => $item['diskon'] ?? 0,
                        'catatan' => $item['catatan'] ?? null,
                    ]);

                    $qty = (int)($item['qty'] ?? 0);
                    $firstItemBarangId = null; // Simpan ID ItemBarang pertama untuk di-link ke PurchaseOrderItem
                    
                    if ($qty > 0 && isset($item['jenis_barang_id'], $item['bentuk_barang_id'], $item['grade_barang_id'])) {
                        $jenisBarang = JenisBarang::find($item['jenis_barang_id']);
                        $bentukBarang = BentukBarang::find($item['bentuk_barang_id']);
                        $gradeBarang = GradeBarang::find($item['grade_barang_id']);

                        if ($jenisBarang && $bentukBarang && $gradeBarang) {
                            $dimensi = $bentukBarang->dimensi;
                            $panjang = (float)($item['panjang'] ?? 0);
                            $lebar = array_key_exists('lebar', $item) ? (float)$item['lebar'] : null;
                            $tebal = (float)($item['tebal'] ?? 0);

                            if ($dimensi === '1D') {
                                $namaItemBarang = $bentukBarang->kode . '-' . $jenisBarang->kode . '-' . $gradeBarang->kode . '-' . $panjang . 'x' . $tebal;
                                $sisaLuas = $panjang * $tebal;
                            } else {
                                $namaItemBarang = $bentukBarang->kode . '-' . $jenisBarang->kode . '-' . $gradeBarang->kode . '-' . $panjang . 'x' . ($lebar ?? 0) . 'x' . $tebal;
                                $sisaLuas = $panjang * (($lebar ?? 0)) * $tebal;
                            }

                            $sequenceController = app(DocumentSequenceController::class);

                            for ($i = 0; $i < $qty; $i++) {
                                $sequenceResponse = $sequenceController->generateDocumentSequence('barang');
                                $sequenceData = method_exists($sequenceResponse, 'getData') ? $sequenceResponse->getData() : null;
                                $sequenceNomor = $sequenceData && isset($sequenceData->data) ? $sequenceData->data : null;
                                if (!$sequenceNomor) {
                                    throw new \Exception('Gagal generate nomor barang');
                                }

                                $kodeBarang = $namaItemBarang . '-' . $sequenceNomor;

                                $created = ItemBarang::create([
                                    'kode_barang' => $kodeBarang,
                                    'jenis_barang_id' => $jenisBarang->id,
                                    'bentuk_barang_id' => $bentukBarang->id,
                                    'grade_barang_id' => $gradeBarang->id,
                                    'nama_item_barang' => $namaItemBarang,
                                    'sisa_luas' => $sisaLuas,
                                    'panjang' => $panjang,
                                    'lebar' => $lebar,
                                    'tebal' => $tebal,
                                    'quantity' => 1,
                                    'jenis_potongan' => 'utuh',
                                    'is_edit' => false,
                                    'is_onprogress_po' => true,
                                    'user_id' => auth()->id(),
                                    'gudang_id' => null,
                                ]);

                                // Simpan ID ItemBarang pertama untuk di-link ke PurchaseOrderItem
                                if ($i === 0) {
                                    $firstItemBarangId = $created->id;
                                }

                                // Tambah urutan sequence barang
                                $sequenceController->increaseSequence('barang');
                            }
                        }
                    }

                    // Update PurchaseOrderItem dengan id_item_barang
                    if ($firstItemBarangId) {
                        $poItem->update(['id_item_barang' => $firstItemBarangId]);
                    }
                }
            }

            // Update sequence counter setelah berhasil create PurchaseOrder
            app(DocumentSequenceController::class)->increaseSequence('po');

            DB::commit();

            // Load relasi setelah simpan
            $purchaseOrder->load(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'supplier']);

            return $this->successResponse($purchaseOrder, 'Purchase Order berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menyimpan Purchase Order: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $data = PurchaseOrder::with(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function scanNomorPo($nomor_po)
    {
        $data = PurchaseOrder::with(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier'])->where('nomor_po', $nomor_po)->first();
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        // Transform data ke struktur response yang diinginkan
        $transformedData = [
            'nomor_dokumen' => $data->nomor_po,
            'tipe_dokumen' => 'purchase_order',
            'status' => $data->status,
            'tanggal_dokumen' => $data->tanggal_po,
            'tanggal_penerimaan' => $data->tanggal_penerimaan,
            'user_penerima' => null,
            'gudang_asal' => null,
            'gudang_tujuan' => null,
            'supplier' => $data->supplier ? $data->supplier->nama_supplier : null,
            'catatan' => $data->catatan,
            'items' => $data->purchaseOrderItems->map(function ($item) {
                $itemBarang = $item->itemBarang;
                return [
                    'id' => $item->id,
                    'item_barang_id' => $item->id_item_barang,
                    'panjang' => $item->panjang,
                    'lebar' => $item->lebar,
                    'tebal' => $item->tebal,
                    'qty' => $item->qty,
                    'jenis_barang_id' => $item->jenis_barang_id,
                    'bentuk_barang_id' => $item->bentuk_barang_id,
                    'grade_barang_id' => $item->grade_barang_id,
                    'satuan' => $item->satuan,
                    'catatan' => $item->catatan,
                    'item_barang_id' => $item->id_item_barang ?? null,
                    'kode_barang' => $itemBarang ? $itemBarang->kode_barang : null,
                    'unit' => null,
                    'status' => null
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data ditemukan',
            'data' => $transformedData
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_po' => 'nullable|date',
            'tanggal_jatuh_tempo' => 'required|date',
            'id_supplier' => 'required|exists:ref_supplier,id',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'required|string',
            'catatan' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.id_item_barang' => 'nullable|exists:ref_item_barang,id',
            'items.*.qty' => 'required_with:items|numeric|min:0',
            'items.*.harga_satuan' => 'required_with:items|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
            'items.*.panjang' => 'nullable|numeric|min:0',
            'items.*.lebar' => 'nullable|numeric|min:0',
            'items.*.tebal' => 'nullable|numeric|min:0',
            'items.*.berat' => 'nullable|numeric|min:0',
            'items.*.catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

            $data = PurchaseOrder::with(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        DB::beginTransaction();
        try {
            $data->update($request->only([
                'tanggal_po',
                'tanggal_jatuh_tempo',
                'id_supplier',
                'total_amount',
                'status',
                'catatan',
            ]));

            // Update items jika ada
            if ($request->has('items') && is_array($request->items)) {
                // Hapus items yang lama
                $data->purchaseOrderItems()->delete();

                // Tambah items yang baru
                foreach ($request->items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $data->id,
                        'id_item_barang' => $item['id_item_barang'] ?? null,
                        'qty' => $item['qty'] ?? 0,
                        'harga_satuan' => $item['harga_satuan'] ?? 0,
                        'subtotal' => $item['subtotal'] ?? 0,
                        'panjang' => $item['panjang'] ?? null,
                        'lebar' => $item['lebar'] ?? null,
                        'tebal' => $item['tebal'] ?? null,
                        'berat' => $item['berat'] ?? 0,
                        'catatan' => $item['catatan'] ?? null,
                    ]);
                }
            }

            DB::commit();

            // Load relasi setelah update
            $data->load(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier']);

            return $this->successResponse($data, 'Purchase Order berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal mengupdate Purchase Order: ' . $e->getMessage(), 500);
        }
    }
    
    public function destroy($id)
    {
        $data = PurchaseOrder::with(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            // Hapus data (soft delete)
            $data->delete();
            return $this->successResponse(null, 'Purchase Order berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus Purchase Order: ' . $e->getMessage(), 500);
        }
    }
    
    public function restore($id)
    {
        $data = PurchaseOrder::with(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier'])->onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $data->restore();
            return $this->successResponse($data, 'Purchase Order berhasil direstore');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal restore Purchase Order: ' . $e->getMessage(), 500);
        }
    }

    public function forceDelete($id)
    {
        $data = PurchaseOrder::with(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier'])->withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $data->forceDelete();
            return $this->successResponse(null, 'Purchase Order berhasil dihapus permanen');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus permanen Purchase Order: ' . $e->getMessage(), 500);
        }
    }

    public function softDelete($id)
    {
        $data = PurchaseOrder::with(['purchaseOrderItems.jenisBarang', 'purchaseOrderItems.bentukBarang', 'purchaseOrderItems.gradeBarang', 'purchaseOrderItems.itemBarang', 'supplier'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $data->delete();
            return $this->successResponse(null, 'Purchase Order berhasil dihapus (soft delete)');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal soft delete Purchase Order: ' . $e->getMessage(), 500);
        }
    }
    

    
}
