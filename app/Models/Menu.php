<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_menu';

    protected $fillable = [
        'kode',
        'nama_menu',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the role menu permissions for this menu.
     */
    public function roleMenuPermissions()
    {
        return $this->hasMany(RoleMenuPermission::class, 'menu_id');
    }
}
