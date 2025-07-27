<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Get all roles
     */
    public function index()
    {
        $roles = Role::all(['id', 'name', 'description']);
        
        return response()->json([
            'message' => 'Roles retrieved successfully',
            'data' => $roles
        ]);
    }
}
