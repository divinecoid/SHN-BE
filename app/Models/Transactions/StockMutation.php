<?php

namespace App\Models\Transactions;

use App\Models\MasterData\Gudang;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMutation extends Model
{
    use SoftDeletes;

    protected $table = 'trx_stock_mutation';

    protected $fillable = [
        'gudang_tujuan_id',
        'gudang_asal_id',
        'requestor_id',
        'recipient_id',
        'approval_date',
        'status',
        'nomor_mutasi',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'approval_date' => 'date',
    ];
    
    public function stockMutationItems()
    {
        return $this->hasMany(StockMutationItem::class);
    }

    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }
    public function gudangAsal()
    {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id');
    }
    public function requestor()
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
    
}