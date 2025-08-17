<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SysSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Http\Traits\ApiFilterTrait;

class SysSettingController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = SysSetting::query();
        $query = $this->applyFilter($query, $request, ['key', 'value', 'description']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:sys_setting,key',
            'value' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $data = SysSetting::create($request->only(['key', 'value', 'description']));

        return $this->successResponse($data, 'Setting berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = SysSetting::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = SysSetting::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:sys_setting,key,' . $id,
            'value' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $data->update($request->only(['key', 'value', 'description']));

        // Clear cache for this specific setting
        Cache::forget("sys_setting_{$data->key}");

        return $this->successResponse($data, 'Setting berhasil diupdate');
    }

    public function destroy($id)
    {
        $data = SysSetting::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $key = $data->key;
        $data->delete();

        // Clear cache for this specific setting
        Cache::forget("sys_setting_{$key}");

        return $this->successResponse(null, 'Setting berhasil dihapus');
    }

    /**
     * Get setting value by key (with cache)
     */
    public function getValueByKey($key)
    {
        // Coba ambil dari cache dulu (lebih cepat)
        $cachedValue = Cache::get("sys_setting_{$key}");
        
        if ($cachedValue !== null) {
            return $this->successResponse([
                'key' => $key,
                'value' => $cachedValue,
                'source' => 'cache'
            ]);
        }
        
        // Jika tidak ada di cache, coba ambil dari database
        $setting = SysSetting::where('key', $key)->first();
        
        if ($setting) {
            // Jika ada di database, cache dulu untuk akses selanjutnya
            Cache::put("sys_setting_{$key}", $setting->value, 3600);
            
            return $this->successResponse([
                'key' => $key,
                'value' => $setting->value,
                'source' => 'database'
            ]);
        }
        
        // Jika tidak ada di cache maupun database
        return $this->errorResponse('Setting tidak ditemukan', 404);
    }
}
