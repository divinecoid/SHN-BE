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

    /**
     * Save base64 image as JPG file
     *
     * @param string $base64Data
     * @param string $folder
     * @param string|null $filename
     * @return array
     */
    public static function saveBase64AsJpg(string $base64Data, string $folder = 'uploads', ?string $filename = null): array
    {
        try {
            // Remove data:image/jpeg;base64, prefix if exists
            $base64Data = preg_replace('/^data:image\/[a-zA-Z]+;base64,/', '', $base64Data);
            
            // Decode base64
            $imageData = base64_decode($base64Data);
            
            if ($imageData === false) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Invalid base64 data'
                ];
            }

            // Generate filename if not provided
            if (!$filename) {
                $filename = Str::uuid() . '.jpg';
            } else {
                // Ensure .jpg extension
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
            }

            // Ensure folder path is clean
            $folder = trim($folder, '/');
            
            // Create folder if not exists
            $fullPath = storage_path('app/public/' . $folder);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            // Save file
            $filePath = $fullPath . '/' . $filename;
            $result = file_put_contents($filePath, $imageData);
            
            if ($result === false) {
                return [
                    'success' => false,
                    'data' => null,
                    'message' => 'Failed to save image file'
                ];
            }
            
            $relativePath = $folder . '/' . $filename;
            
            // Get file info
            $fileInfo = [
                'filename' => $filename,
                'path' => $relativePath,
                'url' => Storage::url($relativePath),
                'size' => filesize($filePath),
                'mime_type' => 'image/jpeg',
                'extension' => 'jpg',
                'folder' => $folder
            ];

            return [
                'success' => true,
                'data' => $fileInfo,
                'message' => 'Image berhasil disimpan'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Gagal menyimpan image: ' . $e->getMessage()
            ];
        }
    }
}
