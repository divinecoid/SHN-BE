# Contoh Request dan Response - Add Detail Stock Opname

## Endpoint
```
POST /api/stock-opname/{id}/detail
```

## Request Headers
```
Authorization: Bearer {your_jwt_token}
Content-Type: application/json
Accept: application/json
```

---

## Request Body

### Contoh 1: Request lengkap dengan semua field
```json
{
  "kode_barang": "PLT-BJA-GRD-100x50x5-0001",
  "stok_sistem": 100,
  "stok_fisik": 95,
  "catatan": "Ada selisih 5 unit, perlu investigasi"
}
```

### Contoh 2: Request untuk item yang TIDAK di-freeze (stok_sistem akan null)
```json
{
  "kode_barang": "PLT-BJA-GRD-100x50x5-0001",
  "stok_fisik": 95,
  "catatan": "Stock opname rutin"
}
```

**Catatan:** Jika item tidak di-freeze, `stok_sistem` tidak perlu dikirim (akan otomatis null)

### Contoh 3: Request untuk item yang di-FREEZE (stok_sistem wajib diisi)
```json
{
  "kode_barang": "PLT-BJA-GRD-100x50x5-0002",
  "stok_sistem": 100,
  "stok_fisik": 95,
  "catatan": "Item dalam status beku"
}
```

**Catatan:** Jika item di-freeze, `stok_sistem` wajib diisi, jika tidak akan error

---

## Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `kode_barang` | string | Yes | Kode barang yang akan ditambahkan ke detail - harus exists di tabel `ref_item_barang` dengan field `kode_barang`. **Barang harus berada di gudang yang sama dengan stock opname** |
| `stok_sistem` | integer | Conditional | **Wajib diisi** jika item barang dalam status beku (frozen_at tidak null). **Tidak boleh diisi** jika item tidak beku (akan otomatis menjadi null) |
| `stok_fisik` | integer | Yes | Stok fisik hasil stock opname. Minimum: 0 |
| `catatan` | string | No | Catatan tambahan untuk detail stock opname |

**Catatan Validasi Gudang:**
- Barang yang di-scan harus memiliki gudang yang ditetapkan (gudang_id tidak null)
- Barang harus berada di gudang yang sama dengan stock opname
- Jika barang berada di gudang yang berbeda, akan return error dengan nama gudang tempat barang berada

---

## URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | ID stock opname yang akan ditambahkan detailnya |

---

## Response Success (200 OK)

### Response untuk Item yang di-FREEZE (stok_sistem diisi)
```json
{
  "success": true,
  "message": "Detail stock opname berhasil ditambahkan",
  "data": {
    "id": 1,
    "stock_opname_id": 1,
    "item_barang_id": 10,
    "stok_sistem": 100,
    "stok_fisik": 95,
    "catatan": "Ada selisih 5 unit, perlu investigasi",
    "created_at": "2025-11-02T12:00:00.000000Z",
    "updated_at": "2025-11-02T12:00:00.000000Z",
    "item_barang": {
      "id": 10,
      "kode_barang": "PLT-BJA-GRD-100x50x5-0001",
      "nama_item_barang": "Plat Baja",
      "jenis_barang_id": 1,
      "bentuk_barang_id": 1,
      "grade_barang_id": 1,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

### Response untuk Item yang TIDAK di-freeze (stok_sistem null)
```json
{
  "success": true,
  "message": "Detail stock opname berhasil ditambahkan",
  "data": {
    "id": 2,
    "stock_opname_id": 1,
    "item_barang_id": 11,
    "stok_sistem": null,
    "stok_fisik": 95,
    "catatan": "Stock opname rutin",
    "created_at": "2025-11-02T12:05:00.000000Z",
    "updated_at": "2025-11-02T12:05:00.000000Z",
    "item_barang": {
      "id": 11,
      "kode_barang": "PLT-BJA-GRD-100x50x5-0002",
      "nama_item_barang": "Plat Baja",
      "jenis_barang_id": 1,
      "bentuk_barang_id": 1,
      "grade_barang_id": 1,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

**Catatan:** 
- Field `deleted_at` tidak muncul karena di-hidden di model
- Response termasuk relasi `itemBarang` yang sudah di-load
- `stok_sistem` akan `null` jika item tidak di-freeze, atau berisi nilai jika item di-freeze

---

## Response Error

### 1. Not Found Error (404 Not Found)

#### Contoh: Stock opname tidak ditemukan
```json
{
  "success": false,
  "message": "Stock opname tidak ditemukan",
  "data": null
}
```

#### Contoh: Kode barang tidak ditemukan
```json
{
  "success": false,
  "message": "Kode barang tidak ditemukan",
  "data": null
}
```

### 2. Validation Error (422 Unprocessable Entity)

#### Contoh: kode_barang tidak ada
```json
{
  "success": false,
  "message": "The kode barang field is required.",
  "data": null
}
```

#### Contoh: kode_barang tidak valid
```json
{
  "success": false,
  "message": "The selected kode barang is invalid.",
  "data": null
}
```

#### Contoh: Barang tidak memiliki gudang yang ditetapkan
```json
{
  "success": false,
  "message": "Barang tidak memiliki gudang yang ditetapkan",
  "data": null
}
```

#### Contoh: Barang tidak berada di gudang stock opname
```json
{
  "success": false,
  "message": "Barang tidak berada di gudang stock opname. Barang berada di gudang: Gudang Utama",
  "data": null
}
```

#### Contoh: stok_fisik tidak ada
```json
{
  "success": false,
  "message": "The stok fisik field is required.",
  "data": null
}
```

#### Contoh: stok_fisik negatif
```json
{
  "success": false,
  "message": "The stok fisik must be at least 0.",
  "data": null
}
```

#### Contoh: Stock opname sudah dibatalkan
```json
{
  "success": false,
  "message": "Tidak dapat menambahkan detail ke stock opname yang sudah dibatalkan",
  "data": null
}
```

#### Contoh: Stock opname sudah selesai
```json
{
  "success": false,
  "message": "Tidak dapat menambahkan detail ke stock opname yang sudah selesai",
  "data": null
}
```

#### Contoh: Item barang sudah ada di detail
```json
{
  "success": false,
  "message": "Item barang sudah ada di detail stock opname ini",
  "data": null
}
```

#### Contoh: Item di-freeze tapi stok_sistem tidak diisi
```json
{
  "success": false,
  "message": "Stok sistem wajib diisi karena item barang dalam status beku",
  "data": null
}
```

### 3. Server Error (500 Internal Server Error)

#### Contoh: Error umum
```json
{
  "success": false,
  "message": "Gagal menambahkan detail: [error message]",
  "data": null
}
```

---

## Alur Proses

1. **Validasi Stock Opname** - Memastikan stock opname ada di database
2. **Validasi Status** - Memastikan stock opname belum `cancelled` atau `completed`
3. **Validasi Request** - Memvalidasi semua field yang dikirim
4. **Begin Transaction** - Memulai database transaction
5. **Cari Item Barang** - Mencari item barang berdasarkan `kode_barang` beserta relasi `gudang`
6. **Validasi Kode Barang** - Memastikan kode barang ditemukan di database
7. **Validasi Gudang Barang** - Memastikan barang memiliki gudang yang ditetapkan (gudang_id tidak null)
8. **Validasi Lokasi Gudang** - Memastikan barang berada di gudang yang sama dengan stock opname
   - Jika barang tidak berada di gudang stock opname, akan return error dengan nama gudang tempat barang berada
9. **Cek Status Freeze** - Mengecek apakah item di-freeze (frozen_at tidak null)
10. **Validasi Stok Sistem**:
    - Jika item di-freeze: `stok_sistem` wajib diisi, jika tidak akan error
    - Jika item tidak di-freeze: `stok_sistem` akan diset ke null (tidak peduli dikirim atau tidak)
11. **Check Duplikasi** - Memastikan item barang belum ada di detail stock opname ini
12. **Create Detail** - Membuat record detail stock opname dengan `stok_sistem` yang sesuai
13. **Commit Transaction** - Commit semua perubahan
14. **Load Relationships** - Load relasi `itemBarang`
15. **Return Success Response**

---

## Catatan Penting

1. **Validasi Kode Barang**: 
   - Kode barang harus exists di tabel `ref_item_barang`
   - Jika kode barang tidak ditemukan, akan return error: "Kode barang tidak ditemukan" (404)

2. **Validasi Gudang Barang**: 
   - Barang harus memiliki gudang yang ditetapkan (gudang_id tidak null)
   - Jika barang tidak memiliki gudang, akan return error: "Barang tidak memiliki gudang yang ditetapkan" (422)

3. **Validasi Lokasi Gudang**: 
   - Barang harus berada di gudang yang sama dengan stock opname
   - Jika barang berada di gudang yang berbeda, akan return error dengan format: "Barang tidak berada di gudang stock opname. Barang berada di gudang: [nama_gudang]" (422)
   - Error message akan menampilkan nama gudang tempat barang sebenarnya berada

4. **Stok Sistem (stok_sistem)**: 
   - **Jika item di-FREEZE** (frozen_at tidak null):
     - `stok_sistem` **WAJIB diisi** (required)
     - Harus berupa integer dan minimum 0
     - Jika tidak diisi, akan return error: "Stok sistem wajib diisi karena item barang dalam status beku"
   - **Jika item TIDAK di-freeze** (frozen_at null):
     - `stok_sistem` **TIDAK boleh diisi** (akan otomatis menjadi null)
     - Jika dikirim, akan diabaikan dan diset ke null
     - Tidak perlu dikirim dalam request

5. **Stok Fisik (stok_fisik)**: 
   - Field `stok_fisik` wajib diisi
   - Harus integer dan minimum 0

6. **Duplikasi**: 
   - Setiap item barang hanya bisa ditambahkan sekali per stock opname
   - Jika item barang sudah ada di detail, akan return error

7. **Status Stock Opname**: 
   - Hanya stock opname dengan status `draft` atau `active` yang dapat ditambahkan detail
   - Stock opname dengan status `cancelled` atau `completed` tidak dapat ditambahkan detail

8. **Transaction**: 
   - Semua operasi dilakukan dalam satu transaction
   - Jika ada error di step manapun, semua perubahan akan di-rollback

---

## Contoh Penggunaan dengan cURL

```bash
curl -X POST "http://your-domain.com/api/stock-opname/1/detail" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "kode_barang": "PLT-BJA-GRD-100x50x5-0001",
    "stok_sistem": 100,
    "stok_fisik": 95,
    "catatan": "Ada selisih 5 unit, perlu investigasi"
  }'
```

---

## Contoh Penggunaan dengan JavaScript (Fetch API)

```javascript
const response = await fetch('http://your-domain.com/api/stock-opname/1/detail', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    kode_barang: 'PLT-BJA-GRD-100x50x5-0001',
    stok_sistem: 100,
    stok_fisik: 95,
    catatan: 'Ada selisih 5 unit, perlu investigasi'
  })
});

const data = await response.json();
console.log(data);
```

---

## Contoh Penggunaan dengan Axios

```javascript
import axios from 'axios';

const response = await axios.post('http://your-domain.com/api/stock-opname/1/detail', {
  kode_barang: 'PLT-BJA-GRD-100x50x5-0001',
  stok_sistem: 100,
  stok_fisik: 95,
  catatan: 'Ada selisih 5 unit, perlu investigasi'
}, {
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

console.log(response.data);
```

