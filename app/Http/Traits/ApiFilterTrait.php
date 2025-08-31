<?php

namespace App\Http\Traits;

trait ApiFilterTrait
{
    public function applyFilter($query, $request, $searchFields = [])
    {
        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        // Multiple Sort
        if ($sort = $request->input('sort')) {
            $sortArray = explode(';', $sort);
            foreach ($sortArray as $sortItem) {
                $sortParts = explode(',', $sortItem);
                if (count($sortParts) == 2) {
                    $column = trim($sortParts[0]);
                    $order = trim($sortParts[1]);
                    $query->orderBy($column, $order);
                }
            }
        } else {
            // Default sort
            $sortBy = $request->input('sort_by', 'id');
            $order = $request->input('order', 'asc');
            $query->orderBy($sortBy, $order);
        }

        return $query;
    }

    public function paginateResponse($data, $items)
    {
        return [
            'success' => true,
            'message' => $items->count() ? 'Data ditemukan' : 'Tidak ada data yang ditemukan',
            'data' => $items,
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ],
        ];
    }

    public function getPerPageDefault()
    {
        return 100;
    }

    // single response

    public function successResponse($data, $message = 'Data ditemukan')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function errorResponse($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $code);
    }
} 