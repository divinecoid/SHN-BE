<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HideTimestampsInRelations;

class Menu extends Model
{
    use HasFactory, SoftDeletes, HideTimestampsInRelations;

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
        return $this->hasMany(MenuPermission::class, 'menu_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'ref_menu_menu_permission', 'menu_id', 'permission_id');
    }
}
