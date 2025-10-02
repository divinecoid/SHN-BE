<?php

namespace App\Models\Transactions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class StockMutation extends Model
{
    use SoftDeletes, HideTimestampsInRelations;
    
}