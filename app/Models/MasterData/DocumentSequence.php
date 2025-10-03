<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;

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
    ];
}
