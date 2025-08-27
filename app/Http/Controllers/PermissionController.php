<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Http\Traits\ApiFilterTrait;

class PermissionController extends Controller
{
    use ApiFilterTrait;

    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Permission::query();
        $query = $this->applyFilter($query, $request, ['nama_permission']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Display the specified permission.
     */
    public function show($id)
    {
        $data = Permission::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }
}
