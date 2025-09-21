# Error Handling Documentation

## Overview
Dokumentasi ini menjelaskan implementasi error handling untuk menangani error database connection dan error lainnya dalam aplikasi Laravel.

## Error Types yang Ditangani

### 1. Database Connection Error
- **Error Code**: `SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it`
- **HTTP Status**: 503 (Service Unavailable)
- **Response**: JSON dengan pesan yang user-friendly

### 2. Database Query Error
- **Error Code**: QueryException
- **HTTP Status**: 500 (Internal Server Error)
- **Response**: JSON dengan pesan yang user-friendly

### 3. General Exception
- **Error Code**: Exception
- **HTTP Status**: 500 (Internal Server Error)
- **Response**: JSON dengan pesan yang user-friendly

## Implementasi

### 1. Exception Handler (`app/Exceptions/Handler.php`)
Handler ini menangani semua exception yang terjadi dalam aplikasi:

```php
// Menangani database connection error
if ($this->isDatabaseConnectionError($exception)) {
    return $this->handleDatabaseConnectionError($request, $exception);
}

// Menangani database query error
if ($exception instanceof QueryException) {
    return $this->handleDatabaseError($request, $exception);
}
```

### 2. LoginController Error Handling
Semua method dalam LoginController telah dilengkapi dengan error handling:

- `login()` - Menangani error saat login
- `refresh()` - Menangani error saat refresh token
- `logout()` - Menangani error saat logout

### 3. Response Format
Semua error response mengikuti format JSON yang konsisten:

```json
{
    "success": false,
    "message": "Pesan error yang user-friendly",
    "error": "ERROR_CODE",
    "data": null
}
```

## Error Codes

| Error Code | Description | HTTP Status |
|------------|-------------|-------------|
| `DATABASE_CONNECTION_ERROR` | Koneksi database gagal | 503 |
| `DATABASE_ERROR` | Error pada query database | 500 |
| `TOKEN_GENERATION_ERROR` | Error saat generate token | 500 |
| `UNEXPECTED_ERROR` | Error yang tidak terduga | 500 |

## Error Views

### 503.blade.php
View untuk error 503 (Service Unavailable) - database connection error

### 500.blade.php
View untuk error 500 (Internal Server Error) - general server error

## Logging

Semua error dicatat dalam log dengan detail:
- Error message
- Stack trace
- Request URL
- Request method
- Timestamp

## Testing

Untuk test error handling:

1. **Database Connection Error**:
   - Matikan MySQL service
   - Coba login
   - Response: 503 dengan pesan "Koneksi ke database gagal"

2. **Database Query Error**:
   - Buat query yang salah
   - Response: 500 dengan pesan "Terjadi kesalahan pada database"

## Best Practices

1. **Selalu log error** untuk debugging
2. **Gunakan pesan yang user-friendly** untuk user
3. **Gunakan HTTP status code yang tepat**
4. **Konsisten dalam format response**
5. **Jangan expose sensitive information** dalam error message

## Maintenance

- Monitor log files untuk error patterns
- Update error messages sesuai kebutuhan
- Test error handling secara berkala
- Dokumentasikan error codes yang baru
