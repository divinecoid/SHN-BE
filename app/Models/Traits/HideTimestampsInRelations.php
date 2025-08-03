<?php

namespace App\Models\Traits;

trait HideTimestampsInRelations
{
    public function toArray()
    {
        $array = parent::toArray();
        
        // Cek apakah sedang di API dengan relasi
        if ($this->shouldHideTimestamps()) {
            unset($array['created_at'], $array['updated_at'], $array['deleted_at']);
        }
        
        return $array;
    }

    protected function shouldHideTimestamps()
    {
        // Cek apakah request ada dan sedang di API
        if (!request()) {
            return false;
        }

        // Cek URL pattern untuk API yang menggunakan relasi
        $path = request()->path();
        
        // Untuk JenisBarang, BentukBarang, GradeBarang
        if (in_array(get_class($this), [
            \App\Models\MasterData\JenisBarang::class,
            \App\Models\MasterData\BentukBarang::class,
            \App\Models\MasterData\GradeBarang::class
        ])) {
            return str_contains($path, 'item-barang');
        }
        
        // Untuk JenisBiaya
        if (get_class($this) === \App\Models\MasterData\JenisBiaya::class) {
            return str_contains($path, 'jenis-transaksi-kas');
        }
        
        return false;
    }
} 