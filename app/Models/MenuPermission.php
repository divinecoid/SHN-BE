<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HideTimestampsInRelations;

class MenuPermission extends Model
{
    use HideTimestampsInRelations;

    protected $table = 'ref_menu_menu_permission';

    protected $fillable = [
        'menu_id',
        'permission_id',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }
}

