<?php

use App\Helpers\FileHelper;
use Illuminate\Http\UploadedFile;

if (!function_exists('saveFile')) {
    /**
     * Save file helper function
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string|null $filename
     * @return array
     */
    function saveFile(UploadedFile $file, string $folder = 'uploads', ?string $filename = null): array
    {
        return FileHelper::saveFile($file, $folder, $filename);
    }
}

if (!function_exists('saveMultipleFiles')) {
    /**
     * Save multiple files helper function
     *
     * @param array $files
     * @param string $folder
     * @return array
     */
    function saveMultipleFiles(array $files, string $folder = 'uploads'): array
    {
        return FileHelper::saveMultipleFiles($files, $folder);
    }
}

if (!function_exists('getFile')) {
    /**
     * Get file helper function
     *
     * @param string $path
     * @return array
     */
    function getFile(string $path): array
    {
        return FileHelper::getFile($path);
    }
}

if (!function_exists('downloadFile')) {
    /**
     * Download file helper function
     *
     * @param string $path
     * @param string|null $downloadName
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|null
     */
    function downloadFile(string $path, ?string $downloadName = null)
    {
        return FileHelper::downloadFile($path, $downloadName);
    }
}

if (!function_exists('deleteFile')) {
    /**
     * Delete file helper function
     *
     * @param string $path
     * @return array
     */
    function deleteFile(string $path): array
    {
        return FileHelper::deleteFile($path);
    }
}

if (!function_exists('getFilesInFolder')) {
    /**
     * Get files in folder helper function
     *
     * @param string $folder
     * @return array
     */
    function getFilesInFolder(string $folder): array
    {
        return FileHelper::getFilesInFolder($folder);
    }
}

if (!function_exists('validateFileType')) {
    /**
     * Validate file type helper function
     *
     * @param UploadedFile $file
     * @param array $allowedTypes
     * @return bool
     */
    function validateFileType(UploadedFile $file, array $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']): bool
    {
        return FileHelper::validateFileType($file, $allowedTypes);
    }
}

if (!function_exists('validateFileSize')) {
    /**
     * Validate file size helper function
     *
     * @param UploadedFile $file
     * @param int $maxSizeInMB
     * @return bool
     */
    function validateFileSize(UploadedFile $file, int $maxSizeInMB = 10): bool
    {
        return FileHelper::validateFileSize($file, $maxSizeInMB);
    }
}
