<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHelper
{
    /**
     * Save file to storage
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string|null $filename
     * @return array
     */
    public static function saveFile(UploadedFile $file, string $folder = 'uploads', ?string $filename = null): array
    {
        try {
            // Generate filename if not provided
            if (!$filename) {
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid() . '.' . $extension;
            }

            // Ensure folder path is clean
            $folder = trim($folder, '/');
            
            // Store file
            $path = $file->storeAs($folder, $filename, 'public');
            
            // Get file info
            $fileInfo = [
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'path' => $path,
                'url' => Storage::url($path),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'folder' => $folder
            ];

            return [
                'success' => true,
                'data' => $fileInfo,
                'message' => 'File berhasil disimpan'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Gagal menyimpan file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Save multiple files
     *
     * @param array $files
     * @param string $folder
     * @return array
     */
    public static function saveMultipleFiles(array $files, string $folder = 'uploads'): array
    {
        $results = [];
        $successCount = 0;

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $result = self::saveFile($file, $folder);
                $results[] = $result;
                
                if ($result['success']) {
                    $successCount++;
                }
            }
        }

        return [
            'success' => $successCount > 0,
            'data' => $results,
            'message' => "Berhasil menyimpan {$successCount} dari " . count($files) . " file"
        ];
    }

    /**
     * Get file from storage
     *
     * @param string $path
     * @return array
     */
    public static function getFile(string $path): array
    {
        try {
            // Check if file exists
            if (!Storage::disk('public')->exists($path)) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'File tidak ditemukan'
                ];
            }

            // Get file info
            $fileInfo = [
                'path' => $path,
                'url' => Storage::url($path),
                'size' => Storage::disk('public')->size($path),
                'mime_type' => Storage::disk('public')->mimeType($path),
                'last_modified' => Storage::disk('public')->lastModified($path),
                'exists' => true
            ];

            return [
                'success' => true,
                'data' => $fileInfo,
                'message' => 'File ditemukan'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Gagal mengambil file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get file content as download response
     *
     * @param string $path
     * @param string|null $downloadName
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|null
     */
    public static function downloadFile(string $path, ?string $downloadName = null)
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                return null;
            }

            $filename = $downloadName ?: basename($path);
            
            return Storage::disk('public')->download($path, $filename);

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Delete file from storage
     *
     * @param string $path
     * @return array
     */
    public static function deleteFile(string $path): array
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'File tidak ditemukan'
                ];
            }

            Storage::disk('public')->delete($path);

            return [
                'success' => true,
                'data' => null,
                'message' => 'File berhasil dihapus'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Gagal menghapus file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get files in a folder
     *
     * @param string $folder
     * @return array
     */
    public static function getFilesInFolder(string $folder): array
    {
        try {
            $folder = trim($folder, '/');
            $files = Storage::disk('public')->files($folder);
            
            $fileList = [];
            foreach ($files as $file) {
                $fileInfo = self::getFile($file);
                if ($fileInfo['success']) {
                    $fileList[] = $fileInfo['data'];
                }
            }

            return [
                'success' => true,
                'data' => $fileList,
                'message' => 'Berhasil mengambil daftar file'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Gagal mengambil daftar file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate file type
     *
     * @param UploadedFile $file
     * @param array $allowedTypes
     * @return bool
     */
    public static function validateFileType(UploadedFile $file, array $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, $allowedTypes);
    }

    /**
     * Validate file size
     *
     * @param UploadedFile $file
     * @param int $maxSizeInMB
     * @return bool
     */
    public static function validateFileSize(UploadedFile $file, int $maxSizeInMB = 10): bool
    {
        $maxSizeInBytes = $maxSizeInMB * 1024 * 1024;
        return $file->getSize() <= $maxSizeInBytes;
    }
}
