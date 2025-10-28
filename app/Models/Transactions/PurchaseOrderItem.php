<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\MasterData\ItemBarang;

class PurchaseOrderItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    protected $table = 'trx_purchase_order_item';
    
    protected $hidden = ['deleted_at'];

    protected $casts = [
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tebal' => 'decimal:2',
        'qty' => 'integer',
        'harga' => 'decimal:2',
        'diskon' => 'decimal:2',
    ];
    protected $fillable = [
        'purchase_order_id',
        'id_item_barang',
        'qty',
        'panjang',
        'lebar',
        'tebal',
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'harga',
        'satuan',
        'diskon',
        'catatan',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function jenisBarang()
    {
        return $this->belongsTo(\App\Models\MasterData\JenisBarang::class, 'jenis_barang_id');
    }

    public function bentukBarang()
    {
        return $this->belongsTo(\App\Models\MasterData\BentukBarang::class, 'bentuk_barang_id');
    }

    public function gradeBarang()
    {
        return $this->belongsTo(\App\Models\MasterData\GradeBarang::class, 'grade_barang_id');
    }

    public function itemBarang()
    {
        return $this->belongsTo(ItemBarang::class, 'id_item_barang');
    }
}
