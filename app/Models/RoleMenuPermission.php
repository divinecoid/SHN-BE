<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleMenuPermission extends Model
{
    use HasFactory;

    protected $table = 'ref_role_menu_permission';

    protected $fillable = [
        'role_id',
        'menu_id',
        'permission_id',
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
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * Get the permission that owns the role menu permission.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}
