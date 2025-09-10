# File Helper Usage Guide

Helper untuk menangani file operations (save, get, delete) yang bisa digunakan di seluruh aplikasi Laravel.

## Setup

1. Jalankan composer dump-autoload untuk register helper functions:
```bash
composer dump-autoload
```

2. Pastikan storage link sudah dibuat:
```bash
php artisan storage:link
```

## Penggunaan Helper Functions

### 1. Save Single File

```php
use Illuminate\Http\Request;

public function uploadDocument(Request $request)
{
    $file = $request->file('document');
    $folder = 'documents/work-order'; // folder tujuan
    
    $result = saveFile($file, $folder);
    
    if ($result['success']) {
        // File berhasil disimpan
        $fileInfo = $result['data'];
        echo "File tersimpan di: " . $fileInfo['path'];
        echo "URL: " . $fileInfo['url'];
    } else {
        // Gagal menyimpan
        echo "Error: " . $result['message'];
    }
}
```

### 2. Save Multiple Files

```php
public function uploadMultipleDocuments(Request $request)
{
    $files = $request->file('documents');
    $folder = 'documents/batch-upload';
    
    $result = saveMultipleFiles($files, $folder);
    
    if ($result['success']) {
        $savedFiles = $result['data'];
        foreach ($savedFiles as $fileResult) {
            if ($fileResult['success']) {
                echo "File tersimpan: " . $fileResult['data']['filename'];
            }
        }
    }
}
```

### 3. Get File Info

```php
public function getDocumentInfo($filePath)
{
    $result = getFile($filePath);
    
    if ($result['success']) {
        $fileInfo = $result['data'];
        echo "File size: " . $fileInfo['size'] . " bytes";
        echo "MIME type: " . $fileInfo['mime_type'];
        echo "URL: " . $fileInfo['url'];
    } else {
        echo "File tidak ditemukan";
    }
}
```

### 4. Download File

```php
public function downloadDocument($filePath, $downloadName = null)
{
    $response = downloadFile($filePath, $downloadName);
    
    if ($response) {
        return $response; // Laravel akan handle download
    } else {
        return response()->json(['error' => 'File tidak ditemukan'], 404);
    }
}
```

### 5. Delete File

```php
public function deleteDocument($filePath)
{
    $result = deleteFile($filePath);
    
    if ($result['success']) {
        echo "File berhasil dihapus";
    } else {
        echo "Error: " . $result['message'];
    }
}
```

### 6. Get Files in Folder

```php
public function listDocuments($folder)
{
    $result = getFilesInFolder($folder);
    
    if ($result['success']) {
        $files = $result['data'];
        foreach ($files as $file) {
            echo "File: " . $file['path'];
            echo "Size: " . $file['size'] . " bytes";
        }
    }
}
```

### 7. Validate File

```php
public function validateUpload(Request $request)
{
    $file = $request->file('document');
    
    // Validate file type
    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    if (!validateFileType($file, $allowedTypes)) {
        return response()->json(['error' => 'Tipe file tidak diizinkan'], 400);
    }
    
    // Validate file size (max 5MB)
    if (!validateFileSize($file, 5)) {
        return response()->json(['error' => 'Ukuran file terlalu besar'], 400);
    }
    
    // File valid, lanjutkan upload
    $result = saveFile($file, 'validated-uploads');
    return response()->json($result);
}
```

## API Endpoints

### Upload Single File
```
POST /api/files/upload
Content-Type: multipart/form-data

Parameters:
- file: File yang akan diupload (required)
- folder: Nama folder tujuan (optional, default: 'uploads')
```

### Upload Multiple Files
```
POST /api/files/upload-multiple
Content-Type: multipart/form-data

Parameters:
- files[]: Array file yang akan diupload (required)
- folder: Nama folder tujuan (optional, default: 'uploads')
```

### Get File Info
```
GET /api/files/info?path=uploads/document.pdf
```

### Download File
```
GET /api/files/download?path=uploads/document.pdf&name=my-document.pdf
```

### Delete File
```
DELETE /api/files/delete?path=uploads/document.pdf
```

### Get Files in Folder
```
GET /api/files/folder?folder=uploads/documents
```

## Contoh Penggunaan di Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'document' => 'required|file|max:10240', // Max 10MB
            'work_order_id' => 'required|exists:work_orders,id'
        ]);

        $file = $request->file('document');
        $folder = 'documents/work-order/' . $request->work_order_id;
        
        // Validate file type
        if (!validateFileType($file, ['pdf', 'doc', 'docx', 'jpg', 'png'])) {
            return response()->json([
                'success' => false,
                'message' => 'Tipe file tidak diizinkan'
            ], 400);
        }

        $result = saveFile($file, $folder);
        
        if ($result['success']) {
            // Simpan info file ke database
            $document = new Document();
            $document->work_order_id = $request->work_order_id;
            $document->filename = $result['data']['filename'];
            $document->original_name = $result['data']['original_name'];
            $document->path = $result['data']['path'];
            $document->url = $result['data']['url'];
            $document->size = $result['data']['size'];
            $document->mime_type = $result['data']['mime_type'];
            $document->save();

            return response()->json([
                'success' => true,
                'message' => 'Document berhasil diupload',
                'data' => $document
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }

    public function download($id)
    {
        $document = Document::findOrFail($id);
        
        $response = downloadFile($document->path, $document->original_name);
        
        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan'
            ], 404);
        }

        return $response;
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        
        // Hapus file dari storage
        $deleteResult = deleteFile($document->path);
        
        if ($deleteResult['success']) {
            // Hapus record dari database
            $document->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Document berhasil dihapus'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $deleteResult['message']
        ], 400);
    }
}
```

## File Structure

Setelah upload, file akan tersimpan di:
```
storage/app/public/
├── uploads/
│   ├── documents/
│   │   └── work-order/
│   │       └── 123/
│   │           └── uuid-filename.pdf
│   └── images/
│       └── profile/
│           └── uuid-avatar.jpg
```

## Response Format

Semua helper functions mengembalikan response dengan format:

```php
[
    'success' => true|false,
    'data' => [
        'original_name' => 'document.pdf',
        'filename' => 'uuid-filename.pdf',
        'path' => 'uploads/documents/document.pdf',
        'url' => 'http://localhost/storage/uploads/documents/document.pdf',
        'size' => 1024000,
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'folder' => 'uploads/documents'
    ],
    'message' => 'File berhasil disimpan'
]
```

## Tips

1. **Folder Organization**: Gunakan folder yang terstruktur berdasarkan kategori, misal:
   - `documents/work-order/`
   - `images/profile/`
   - `attachments/invoice/`

2. **File Validation**: Selalu validasi tipe dan ukuran file sebelum upload

3. **Database Storage**: Simpan informasi file ke database untuk tracking dan management

4. **Cleanup**: Implementasikan cleanup untuk file yang tidak terpakai

5. **Security**: Pastikan file yang diupload aman dan tidak mengandung malware
