<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;
use App\Models\MasterData\Gudang;
use App\Models\User;

class ItemBarangRequest extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
    protected $table = 'trx_item_barang_request';
    
    protected $fillable = [
        'nomor_request',
        'item_barang_id',
        'nama_item_barang',
        'jenis_barang_id',
        'bentuk_barang_id',
        'grade_barang_id',
        'panjang',
        'lebar',
        'tebal',
        'quantity',
        'gudang_asal_id',
        'gudang_tujuan_id',
        'keterangan',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tebal' => 'decimal:2',
        'quantity' => 'integer'
    ];

    protected $appends = [
        'requested_at'
    ];

    // Relationships
    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }

    public function bentukBarang()
    {
        return $this->belongsTo(BentukBarang::class, 'bentuk_barang_id');
    }

    public function gradeBarang()
    {
        return $this->belongsTo(GradeBarang::class, 'grade_barang_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function itemBarang()
    {
        return $this->belongsTo(\App\Models\MasterData\ItemBarang::class, 'item_barang_id');
    }

    public function asalGudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id');
    }

    public function tujuanGudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function getRequestedAtAttribute()
    {
        return optional($this->created_at)->setTimezone('Asia/Jakarta')->toIso8601String();
    }
}
