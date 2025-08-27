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
        if (!request()) {
            return false;
        }

        // Selalu hide timestamps untuk semua model yang menggunakan trait ini
        return true;
    }
} 