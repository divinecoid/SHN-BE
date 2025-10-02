<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockMutationController extends Controller
{
    use ApiFilterTrait;
}