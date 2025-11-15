<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class DocumentSequence extends Model
{
    use SoftDeletes, HideTimestampsInRelations;

    protected $table = 'ref_document_sequence';
    protected $fillable = [
        'sequence_date',
        'po',
        'so',
        'wo',
        'pod',
        'invoice',
        'mutasi',
        'barang',
        'receipt',
    ];
}
