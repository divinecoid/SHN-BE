# Contoh Request dan Response - Get Stock Opname Details

## Endpoint
```
GET /api/stock-opname/{id}/details
```

## Request Headers
```
Authorization: Bearer {your_jwt_token}
Content-Type: application/json
Accept: application/json
```

---

## Request Body
Tidak ada request body yang diperlukan. Endpoint ini hanya memerlukan ID stock opname di URL.

---

## URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | ID stock opname header yang akan diambil detailnya |

---

## Response Success (200 OK)

### Response Body
```json
{
  "success": true,
  "message": "Detail stock opname berhasil diambil",
  "data": [
    {
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
        "panjang": 100,
        "lebar": 50,
        "tebal": 5,
        "quantity": 95,
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-01-01T00:00:00.000000Z"
      }
    },
    {
      "id": 2,
      "stock_opname_id": 1,
      "item_barang_id": 11,
      "stok_sistem": null,
      "stok_fisik": 80,
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
        "panjang": 100,
        "lebar": 50,
        "tebal": 5,
        "quantity": 80,
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-01-01T00:00:00.000000Z"
      }
    }
  ]
}
```

### Response jika tidak ada detail (array kosong)
```json
{
  "success": true,
  "message": "Detail stock opname berhasil diambil",
  "data": []
}
```

**Catatan:** 
- Field `deleted_at` tidak muncul karena di-hidden di model
- Response berupa array dari detail stock opname
- Setiap detail termasuk relasi `itemBarang` yang sudah di-load
- Detail diurutkan berdasarkan `created_at` descending (terbaru di atas)
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

---

## Alur Proses

1. **Validasi Stock Opname** - Memastikan stock opname ada di database
2. **Query Details** - Mengambil semua detail stock opname berdasarkan `stock_opname_id`
3. **Load Relationships** - Load relasi `itemBarang` untuk setiap detail
4. **Order By** - Mengurutkan detail berdasarkan `created_at` descending
5. **Return Success Response** - Return array detail stock opname

---

## Catatan Penting

1. **Response Format**: 
   - Response berupa array dari detail stock opname
   - Jika tidak ada detail, akan return array kosong `[]`

2. **Relasi Item Barang**: 
   - Setiap detail sudah include relasi `itemBarang` dengan informasi lengkap
   - Informasi item barang termasuk: `kode_barang`, `nama_item_barang`, dimensi, dll

3. **Stok Sistem**: 
   - `stok_sistem` akan `null` jika item tidak di-freeze saat stock opname dibuat
   - `stok_sistem` akan berisi nilai jika item di-freeze saat stock opname dibuat

4. **Ordering**: 
   - Detail diurutkan berdasarkan `created_at` descending (terbaru di atas)
   - Detail yang terakhir ditambahkan akan muncul di posisi pertama

---

## Contoh Penggunaan dengan cURL

```bash
curl -X GET "http://your-domain.com/api/stock-opname/1/details" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

---

## Contoh Penggunaan dengan JavaScript (Fetch API)

```javascript
const response = await fetch('http://your-domain.com/api/stock-opname/1/details', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

const data = await response.json();
console.log(data);
```

---

## Contoh Penggunaan dengan Axios

```javascript
import axios from 'axios';

const response = await axios.get('http://your-domain.com/api/stock-opname/1/details', {
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

console.log(response.data);
```

---

## Perbedaan dengan Endpoint `show`

| Endpoint | Deskripsi | Response |
|----------|-----------|----------|
| `GET /api/stock-opname/{id}` | Mengambil header stock opname beserta detail | Object dengan header dan array detail di dalamnya |
| `GET /api/stock-opname/{id}/details` | Mengambil hanya detail stock opname | Array detail saja |

### Contoh Response `show`:
```json
{
  "success": true,
  "message": "Data ditemukan",
  "data": {
    "id": 1,
    "pic_user_id": 1,
    "gudang_id": 2,
    "status": "active",
    "stock_opname_details": [...]
  }
}
```

### Contoh Response `getDetails`:
```json
{
  "success": true,
  "message": "Detail stock opname berhasil diambil",
  "data": [...]
}
```

