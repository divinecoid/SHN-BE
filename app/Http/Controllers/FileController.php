<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    /**
     * Get file info
     */
    public function getFileInfo(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $result = getFile($request->input('path'));

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Download file
     */
    public function downloadFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'name' => 'string|nullable'
        ]);

        $path = $request->input('path');
        $downloadName = $request->input('name');

        $response = downloadFile($path, $downloadName);

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        return $response;
    }

    /**
     * Get files in folder
     */
    public function getFilesInFolder(Request $request): JsonResponse
    {
        $request->validate([
            'folder' => 'required|string'
        ]);

        $result = getFilesInFolder($request->input('folder'));

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Show file (for viewing in browser)
     */
    public function showFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $path = $request->input('path');
        
        // Check if file exists
        $fileInfo = getFile($path);
        
        if (!$fileInfo['success']) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        // Return file info for frontend to display
        return response()->json([
            'success' => true,
            'data' => $fileInfo['data'],
            'message' => 'File ditemukan'
        ]);
    }
}
