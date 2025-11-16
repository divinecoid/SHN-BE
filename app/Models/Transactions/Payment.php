<?php

namespace App\Models\Transactions;

use App\Models\Output\InvoicePod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class Payment extends Model
{
    use SoftDeletes, HideTimestampsInRelations;

    protected $table = 'trx_payment';

    protected $fillable = [
        'invoice_pod_id',
        'purchase_order_id',
        'jumlah_payment',
        'tanggal_payment',
        'catatan',
        'has_generated_receipt',
        'nomor_receipt',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'tanggal_payment' => 'date',
        'jumlah_payment' => 'decimal:2',
        'has_generated_receipt' => 'boolean',
    ];

    /**
     * Get the invoice POD
     */
    public function invoicePod()
    {
        return $this->belongsTo(InvoicePod::class, 'invoice_pod_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}

