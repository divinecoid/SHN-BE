<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ref_permission';

    protected $fillable = [
        'nama_permission',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the role menu permissions for this permission.
     */
    public function roleMenuPermissions()
    {
        return $this->hasMany(RoleMenuPermission::class, 'permission_id');
    }
}
