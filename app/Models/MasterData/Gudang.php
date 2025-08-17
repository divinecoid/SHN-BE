<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gudang extends Model
{
    use SoftDeletes;
    protected $table = 'ref_gudang';
    protected $fillable = [
        'kode',
        'nama_gudang',
        'tipe_gudang',
        'parent_id',
        'telepon_hp',
        'kapasitas',
    ];
    protected $hidden = ['deleted_at'];
    
    /**
     * Get the parent gudang
     */
    public function parent()
    {
        return $this->belongsTo(Gudang::class, 'parent_id');
    }

    /**
     * Get the child gudang
     */
    public function children()
    {
        return $this->hasMany(Gudang::class, 'parent_id');
    }

    /**
     * Get all descendants recursively (children, children of children, etc.)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors recursively (parent, parent of parent, etc.)
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }
} 