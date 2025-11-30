<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuPermission;
use App\Http\Traits\ApiFilterTrait;

class MenuMenuPermissionController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = MenuPermission::with(['menu', 'permission']);
        $query = $this->applyFilter($query, $request, ['menu_id', 'permission_id']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function show($id)
    {
        $data = MenuPermission::with(['menu', 'permission'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }
}
