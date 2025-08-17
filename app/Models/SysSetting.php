<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SysSetting extends Model
{
    use HasFactory;

    protected $table = 'sys_setting';
    
    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    /**
     * Get setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $cacheKey = "sys_setting_{$key}";
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return $setting->value;
        });
    }

    /**
     * Set setting value by key
     */
    public static function setValue($key, $value, $description = null)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );

        // Clear cache
        Cache::forget("sys_setting_{$key}");

        return $setting;
    }


}
