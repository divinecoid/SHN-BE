<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HideTimestampsInRelations;

class RoleMenuPermission extends Model
{
    use HasFactory, HideTimestampsInRelations;

    protected $table = 'ref_role_menu_permission';

    protected $fillable = [
        'role_id',
        'menu_menu_permission_id',
    ];

    /**
     * Get the role that owns the role menu permission.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the menu that owns the role menu permission.
     */
    public function menuPermission()
    {
        return $this->belongsTo(MenuPermission::class, 'menu_menu_permission_id');
    }

    /**
     * Get the permission that owns the role menu permission.
     */
    public function permission()
    {
        return $this->hasOneThrough(Permission::class, MenuPermission::class, 'id', 'id', 'menu_menu_permission_id', 'permission_id');
    }

    public function menu()
    {
        return $this->hasOneThrough(Menu::class, MenuPermission::class, 'id', 'id', 'menu_menu_permission_id', 'menu_id');
    }
}
