# API Dokumentasi - Master Data Berat Jenis

## Deskripsi

Master data berat jenis (`ref_berat_jenis`) digunakan untuk menyimpan berat jenis setiap item berdasarkan kombinasi jenis barang, bentuk barang, dan grade barang. 

### Konsep Berat Jenis

- **Untuk Barang 1D**: Menyimpan berat (kg) per cm
- **Untuk Plat (2D)**: Menyimpan berat per luas

## Struktur Tabel

### Tabel: `ref_berat_jenis`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | Primary key |
| `jenis_barang_id` | bigint | Foreign key ke `ref_jenis_barang` |
| `bentuk_barang_id` | bigint | Foreign key ke `ref_bentuk_barang` |
| `grade_barang_id` | bigint | Foreign key ke `ref_grade_barang` |
| `berat_per_cm` | decimal(10,4) | Berat (kg) per cm untuk barang 1D (nullable) |
| `berat_per_luas` | decimal(10,4) | Berat per luas untuk plat 2D (nullable) |
| `created_at` | timestamp | Waktu dibuat |
| `updated_at` | timestamp | Waktu diupdate |
| `deleted_at` | timestamp | Soft delete (nullable) |

**Unique Constraint**: Kombinasi `jenis_barang_id`, `bentuk_barang_id`, `grade_barang_id` harus unik.

## Endpoint API

Base URL: `/api/berat-jenis`

Semua endpoint memerlukan authentication dengan middleware `checkrole`.

---

## 1. List Berat Jenis

Mendapatkan daftar berat jenis dengan pagination dan filter.

**Endpoint:** `GET /api/berat-jenis`

### Query Parameters

| Parameter | Tipe | Required | Keterangan |
|-----------|------|----------|------------|
| `per_page` | integer | No | Jumlah item per halaman (default: sesuai konfigurasi) |
| `page` | integer | No | Nomor halaman |
| `jenis_barang_id` | integer | No | Filter berdasarkan jenis barang |
| `bentuk_barang_id` | integer | No | Filter berdasarkan bentuk barang |
| `grade_barang_id` | integer | No | Filter berdasarkan grade barang |

### Response Success (200 OK)

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "jenis_barang_id": 1,
        "bentuk_barang_id": 1,
        "grade_barang_id": 1,
        "berat_per_cm": null,
        "berat_per_luas": 2.7000,
        "created_at": "2025-12-03T15:13:51.000000Z",
        "updated_at": "2025-12-03T15:13:51.000000Z",
        "jenisBarang": {
          "id": 1,
          "kode": "AL",
          "nama_jenis": "Aluminium"
        },
        "bentukBarang": {
          "id": 1,
          "kode": "PLT",
          "nama_bentuk": "Plate",
          "dimensi": "1000x500x10"
        },
        "gradeBarang": {
          "id": 1,
          "kode": "6061",
          "nama": "Aluminium 6061"
        }
      },
      {
        "id": 2,
        "jenis_barang_id": 1,
        "bentuk_barang_id": 2,
        "grade_barang_id": 1,
        "berat_per_cm": 0.0250,
        "berat_per_luas": null,
        "created_at": "2025-12-03T15:14:20.000000Z",
        "updated_at": "2025-12-03T15:14:20.000000Z",
        "jenisBarang": {
          "id": 1,
          "kode": "AL",
          "nama_jenis": "Aluminium"
        },
        "bentukBarang": {
          "id": 2,
          "kode": "PIP",
          "nama_bentuk": "Pipe",
          "dimensi": "50x2000"
        },
        "gradeBarang": {
          "id": 1,
          "kode": "6061",
          "nama": "Aluminium 6061"
        }
      }
    ],
    "first_page_url": "http://localhost:8000/api/berat-jenis?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/berat-jenis?page=1",
    "links": [...],
    "next_page_url": null,
    "path": "http://localhost:8000/api/berat-jenis",
    "per_page": 15,
    "prev_page_url": null,
    "to": 2,
    "total": 2
  }
}
```

### Contoh Request

```bash
curl -X GET "http://localhost:8000/api/berat-jenis?per_page=10&jenis_barang_id=1" \
  -H "Authorization: Bearer {token}"
```

---

## 2. Detail Berat Jenis

Mendapatkan detail satu berat jenis berdasarkan ID.

**Endpoint:** `GET /api/berat-jenis/{id}`

### Path Parameters

| Parameter | Tipe | Required | Keterangan |
|-----------|------|----------|------------|
| `id` | integer | Yes | ID berat jenis |

### Response Success (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "jenis_barang_id": 1,
    "bentuk_barang_id": 1,
    "grade_barang_id": 1,
    "berat_per_cm": null,
    "berat_per_luas": 2.7000,
    "created_at": "2025-12-03T15:13:51.000000Z",
    "updated_at": "2025-12-03T15:13:51.000000Z",
    "jenisBarang": {
      "id": 1,
      "kode": "AL",
      "nama_jenis": "Aluminium"
    },
    "bentukBarang": {
      "id": 1,
      "kode": "PLT",
      "nama_bentuk": "Plate",
      "dimensi": "1000x500x10"
    },
    "gradeBarang": {
      "id": 1,
      "kode": "6061",
      "nama": "Aluminium 6061"
    }
  }
}
```

### Response Error (404 Not Found)

```json
{
  "success": false,
  "message": "Data tidak ditemukan"
}
```

### Contoh Request

```bash
curl -X GET "http://localhost:8000/api/berat-jenis/1" \
  -H "Authorization: Bearer {token}"
```

---

## 3. Create Berat Jenis

Membuat berat jenis baru.

**Endpoint:** `POST /api/berat-jenis`

### Request Body

| Field | Tipe | Required | Keterangan |
|-------|------|----------|------------|
| `jenis_barang_id` | integer | Yes | ID jenis barang (harus exist di `ref_jenis_barang`) |
| `bentuk_barang_id` | integer | Yes | ID bentuk barang (harus exist di `ref_bentuk_barang`) |
| `grade_barang_id` | integer | Yes | ID grade barang (harus exist di `ref_grade_barang`) |
| `berat_per_cm` | decimal | Conditional | Wajib untuk barang 1D |
| `berat_per_luas` | decimal | Conditional | Wajib untuk plat (2D) |

### Validasi

1. **Kombinasi Unik**: Kombinasi `jenis_barang_id`, `bentuk_barang_id`, `grade_barang_id` harus unik
2. **Berdasarkan Dimensi**:
   - Jika `bentuk_barang.dimensi = '1D'`: `berat_per_cm` wajib diisi
   - Jika `bentuk_barang.dimensi != '1D'`: `berat_per_luas` wajib diisi

### Request Body - Barang 1D

```json
{
  "jenis_barang_id": 1,
  "bentuk_barang_id": 2,
  "grade_barang_id": 1,
  "berat_per_cm": 0.025
}
```

### Request Body - Plat (2D)

```json
{
  "jenis_barang_id": 1,
  "bentuk_barang_id": 1,
  "grade_barang_id": 1,
  "berat_per_luas": 2.7
}
```

### Response Success (200 OK)

```json
{
  "success": true,
  "message": "Data berhasil ditambahkan",
  "data": {
    "id": 1,
    "jenis_barang_id": 1,
    "bentuk_barang_id": 1,
    "grade_barang_id": 1,
    "berat_per_cm": null,
    "berat_per_luas": 2.7000,
    "created_at": "2025-12-03T15:13:51.000000Z",
    "updated_at": "2025-12-03T15:13:51.000000Z",
    "jenisBarang": {
      "id": 1,
      "kode": "AL",
      "nama_jenis": "Aluminium"
    },
    "bentukBarang": {
      "id": 1,
      "kode": "PLT",
      "nama_bentuk": "Plate",
      "dimensi": "1000x500x10"
    },
    "gradeBarang": {
      "id": 1,
      "kode": "6061",
      "nama": "Aluminium 6061"
    }
  }
}
```

### Response Error (422 Validation Error)

```json
{
  "success": false,
  "message": "Berat per cm wajib diisi untuk barang 1D"
}
```

```json
{
  "success": false,
  "message": "Berat jenis untuk kombinasi tersebut sudah ada"
}
```

### Contoh Request

```bash
curl -X POST "http://localhost:8000/api/berat-jenis" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "jenis_barang_id": 1,
    "bentuk_barang_id": 1,
    "grade_barang_id": 1,
    "berat_per_luas": 2.7
  }'
```

---

## 4. Update Berat Jenis

Mengupdate berat jenis yang sudah ada.

**Endpoint:** `PUT /api/berat-jenis/{id}` atau `PATCH /api/berat-jenis/{id}`

### Path Parameters

| Parameter | Tipe | Required | Keterangan |
|-----------|------|----------|------------|
| `id` | integer | Yes | ID berat jenis |

### Request Body

Semua field bersifat optional (partial update). Hanya field yang dikirim yang akan diupdate.

| Field | Tipe | Required | Keterangan |
|-------|------|----------|------------|
| `jenis_barang_id` | integer | No | ID jenis barang |
| `bentuk_barang_id` | integer | No | ID bentuk barang |
| `grade_barang_id` | integer | No | ID grade barang |
| `berat_per_cm` | decimal | Conditional | Wajib untuk barang 1D |
| `berat_per_luas` | decimal | Conditional | Wajib untuk plat (2D) |

### Validasi

Sama seperti create, validasi berdasarkan dimensi bentuk barang dan kombinasi unik.

### Request Body

```json
{
  "berat_per_luas": 2.8
}
```

atau

```json
{
  "jenis_barang_id": 2,
  "bentuk_barang_id": 1,
  "grade_barang_id": 1,
  "berat_per_luas": 2.8
}
```

### Response Success (200 OK)

```json
{
  "success": true,
  "message": "Data berhasil diperbarui",
  "data": {
    "id": 1,
    "jenis_barang_id": 1,
    "bentuk_barang_id": 1,
    "grade_barang_id": 1,
    "berat_per_cm": null,
    "berat_per_luas": 2.8000,
    "created_at": "2025-12-03T15:13:51.000000Z",
    "updated_at": "2025-12-03T15:20:30.000000Z",
    "jenisBarang": {...},
    "bentukBarang": {...},
    "gradeBarang": {...}
  }
}
```

### Contoh Request

```bash
curl -X PUT "http://localhost:8000/api/berat-jenis/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "berat_per_luas": 2.8
  }'
```

---

## 5. Soft Delete Berat Jenis

Menghapus berat jenis secara soft delete.

**Endpoint:** `DELETE /api/berat-jenis/{id}/soft`

### Path Parameters

| Parameter | Tipe | Required | Keterangan |
|-----------|------|----------|------------|
| `id` | integer | Yes | ID berat jenis |

### Response Success (200 OK)

```json
{
  "success": true,
  "message": "Data berhasil dihapus",
  "data": null
}
```

### Contoh Request

```bash
curl -X DELETE "http://localhost:8000/api/berat-jenis/1/soft" \
  -H "Authorization: Bearer {token}"
```

---

## 6. Restore Berat Jenis

Memulihkan berat jenis yang sudah di-soft delete.

**Endpoint:** `PATCH /api/berat-jenis/{id}/restore`

### Path Parameters

| Parameter | Tipe | Required | Keterangan |
|-----------|------|----------|------------|
| `id` | integer | Yes | ID berat jenis |

### Response Success (200 OK)

```json
{
  "success": true,
  "message": "Data berhasil dipulihkan",
  "data": {
    "id": 1,
    "jenis_barang_id": 1,
    "bentuk_barang_id": 1,
    "grade_barang_id": 1,
    "berat_per_cm": null,
    "berat_per_luas": 2.7000,
    "created_at": "2025-12-03T15:13:51.000000Z",
    "updated_at": "2025-12-03T15:13:51.000000Z",
    "deleted_at": null,
    "jenisBarang": {...},
    "bentukBarang": {...},
    "gradeBarang": {...}
  }
}
```

### Contoh Request

```bash
curl -X PATCH "http://localhost:8000/api/berat-jenis/1/restore" \
  -H "Authorization: Bearer {token}"
```

---

## 7. Force Delete Berat Jenis

Menghapus berat jenis secara permanen.

**Endpoint:** `DELETE /api/berat-jenis/{id}/force`

### Path Parameters

| Parameter | Tipe | Required | Keterangan |
|-----------|------|----------|------------|
| `id` | integer | Yes | ID berat jenis |

### Response Success (200 OK)

```json
{
  "success": true,
  "message": "Data berhasil dihapus permanen",
  "data": null
}
```

### Contoh Request

```bash
curl -X DELETE "http://localhost:8000/api/berat-jenis/1/force" \
  -H "Authorization: Bearer {token}"
```

---

## 8. List dengan Trashed

Mendapatkan daftar berat jenis termasuk yang sudah di-soft delete.

**Endpoint:** `GET /api/berat-jenis/with-trashed/all`

### Query Parameters

Sama seperti endpoint list biasa, ditambah:
- Data yang sudah di-soft delete juga akan muncul
- Field `deleted_at` akan terisi jika data sudah dihapus

### Response Success (200 OK)

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "jenis_barang_id": 1,
        "bentuk_barang_id": 1,
        "grade_barang_id": 1,
        "berat_per_cm": null,
        "berat_per_luas": 2.7000,
        "created_at": "2025-12-03T15:13:51.000000Z",
        "updated_at": "2025-12-03T15:13:51.000000Z",
        "deleted_at": null,
        "jenisBarang": {...},
        "bentukBarang": {...},
        "gradeBarang": {...}
      },
      {
        "id": 2,
        "jenis_barang_id": 1,
        "bentuk_barang_id": 2,
        "grade_barang_id": 1,
        "berat_per_cm": 0.0250,
        "berat_per_luas": null,
        "created_at": "2025-12-03T15:14:20.000000Z",
        "updated_at": "2025-12-03T15:14:20.000000Z",
        "deleted_at": "2025-12-03T15:30:00.000000Z",
        "jenisBarang": {...},
        "bentukBarang": {...},
        "gradeBarang": {...}
      }
    ],
    ...
  }
}
```

---

## 9. List Trashed Only

Mendapatkan daftar berat jenis yang sudah di-soft delete saja.

**Endpoint:** `GET /api/berat-jenis/with-trashed/trashed`

### Query Parameters

Sama seperti endpoint list biasa, tetapi hanya menampilkan data yang sudah di-soft delete.

---

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "message": "Error message"
}
```

### Common Error Codes

| Status Code | Keterangan |
|-------------|------------|
| 200 | Success |
| 404 | Data tidak ditemukan |
| 422 | Validation error |
| 401 | Unauthorized |
| 500 | Internal server error |

### Error Messages

- `"Data tidak ditemukan"` - ID tidak ditemukan
- `"Berat per cm wajib diisi untuk barang 1D"` - Validasi untuk barang 1D
- `"Berat per luas wajib diisi untuk plat (2D)"` - Validasi untuk plat 2D
- `"Berat jenis untuk kombinasi tersebut sudah ada"` - Duplikasi kombinasi
- `"Bentuk barang tidak ditemukan"` - Bentuk barang tidak valid
- `"Data tidak ditemukan atau tidak dihapus"` - Restore data yang tidak dihapus

---

## Catatan Penting

1. **Kombinasi Unik**: Setiap kombinasi `jenis_barang_id`, `bentuk_barang_id`, `grade_barang_id` hanya boleh ada satu record.

2. **Validasi Dimensi**: 
   - Sistem akan otomatis mengecek dimensi dari `bentuk_barang` untuk menentukan field mana yang wajib diisi.
   - Jika `dimensi = '1D'`, maka `berat_per_cm` wajib diisi.
   - Jika `dimensi != '1D'`, maka `berat_per_luas` wajib diisi.

3. **Soft Delete**: Data yang dihapus menggunakan soft delete, sehingga masih bisa dipulihkan dengan endpoint restore.

4. **Relasi**: Semua endpoint yang mengembalikan data akan include relasi `jenisBarang`, `bentukBarang`, dan `gradeBarang`.

5. **Pagination**: Semua endpoint list menggunakan pagination dengan default per_page sesuai konfigurasi sistem.

---

## Contoh Penggunaan Lengkap

### Scenario: Menambahkan Berat Jenis untuk Aluminium Plate 6061

```bash
# 1. Cek bentuk barang untuk mengetahui dimensi
GET /api/bentuk-barang/1

# Response menunjukkan dimensi = "1000x500x10" (2D)

# 2. Create berat jenis untuk plat (2D)
POST /api/berat-jenis
{
  "jenis_barang_id": 1,
  "bentuk_barang_id": 1,
  "grade_barang_id": 1,
  "berat_per_luas": 2.7
}

# 3. Update berat jenis jika perlu
PUT /api/berat-jenis/1
{
  "berat_per_luas": 2.8
}

# 4. List semua berat jenis
GET /api/berat-jenis?jenis_barang_id=1
```

### Scenario: Menambahkan Berat Jenis untuk Aluminium Pipe 6061

```bash
# 1. Cek bentuk barang untuk mengetahui dimensi
GET /api/bentuk-barang/2

# Response menunjukkan dimensi = "50x2000" (1D)

# 2. Create berat jenis untuk pipe (1D)
POST /api/berat-jenis
{
  "jenis_barang_id": 1,
  "bentuk_barang_id": 2,
  "grade_barang_id": 1,
  "berat_per_cm": 0.025
}
```

---

## Testing dengan Postman

1. Import collection dengan base URL: `http://localhost:8000/api`
2. Set authorization header dengan Bearer token
3. Gunakan endpoint sesuai kebutuhan

---

**Dokumentasi ini dibuat pada:** 3 Desember 2025  
**Versi API:** 1.0

