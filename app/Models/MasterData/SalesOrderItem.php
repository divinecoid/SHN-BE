<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class SalesOrderItem extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'trx_sales_order_item';
    
    protected $fillable = [
        'sales_order_id',
        'panjang',
        'lebar',
        'tebal',
        'qty',
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'harga',
        'satuan',
        'jenis_potongan',
        'diskon',
        'catatan',
    ];
    
    protected $hidden = ['deleted_at'];
    
    protected $casts = [
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tebal' => 'decimal:2',
        'qty' => 'integer',
        'harga' => 'decimal:2',
        'diskon' => 'decimal:2',
    ];
    
    /**
     * Get the sales order that owns the item
     */
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    
    /**
     * Get the jenis barang
     */
    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }
    
    /**
     * Get the bentuk barang
     */
    public function bentukBarang()
    {
        return $this->belongsTo(BentukBarang::class, 'bentuk_barang_id');
    }
    
    /**
     * Get the grade barang
     */
    public function gradeBarang()
    {
        return $this->belongsTo(GradeBarang::class, 'grade_barang_id');
    }
}