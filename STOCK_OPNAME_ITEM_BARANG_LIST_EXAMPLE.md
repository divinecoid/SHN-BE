# Contoh Request dan Response - Get Item Barang List dengan Checked Flag

## Endpoint
```
GET /api/stock-opname/item-barang-list
```

## Request Headers
```
Authorization: Bearer {your_jwt_token}
Content-Type: application/json
Accept: application/json
```

---

## Request Parameters (Query String)

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `gudang_id` | integer | Yes | ID gudang untuk filter item barang - harus exists di tabel `ref_gudang` |
| `stock_opname_id` | integer | No | ID stock opname header untuk mengecek apakah item sudah ada di detail. Jika tidak dikirim, semua item akan memiliki `checked=false` |
| `search` | string | No | Pencarian berdasarkan `kode_barang` atau `nama_item_barang` |
| `sort_by` | string | No | Field untuk sorting (default: `id`) |
| `order` | string | No | Order sorting: `asc` atau `desc` (default: `asc`) |
| `sort` | string | No | Multiple sort format: `field1,asc;field2,desc` |

---

## Request Examples

### Contoh 1: Request tanpa stock_opname_id (semua checked=false)
```
GET /api/stock-opname/item-barang-list?gudang_id=2
```

### Contoh 2: Request dengan stock_opname_id (checked berdasarkan detail)
```
GET /api/stock-opname/item-barang-list?gudang_id=2&stock_opname_id=1
```

### Contoh 3: Request dengan search dan stock_opname_id
```
GET /api/stock-opname/item-barang-list?gudang_id=2&stock_opname_id=1&search=PLT
```

---

## Response Success (200 OK)

### Response Body (tanpa stock_opname_id - semua checked=false)
```json
{
  "success": true,
  "message": "List item barang berhasil diambil",
  "data": [
    {
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
      "gudang_id": 2,
      "checked": false,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z",
      "jenis_barang": {
        "id": 1,
        "kode": "PLT",
        "nama": "Plat"
      },
      "bentuk_barang": {
        "id": 1,
        "kode": "BJA",
        "nama": "Baja"
      },
      "grade_barang": {
        "id": 1,
        "kode": "GRD",
        "nama": "Grade"
      },
      "gudang": {
        "id": 2,
        "nama": "Gudang Utama",
        "kode": "GUD-001"
      }
    },
    {
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
      "gudang_id": 2,
      "checked": false,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z",
      "jenis_barang": {
        "id": 1,
        "kode": "PLT",
        "nama": "Plat"
      },
      "bentuk_barang": {
        "id": 1,
        "kode": "BJA",
        "nama": "Baja"
      },
      "grade_barang": {
        "id": 1,
        "kode": "GRD",
        "nama": "Grade"
      },
      "gudang": {
        "id": 2,
        "nama": "Gudang Utama",
        "kode": "GUD-001"
      }
    }
  ]
}
```

### Response Body (dengan stock_opname_id - checked berdasarkan detail)
```json
{
  "success": true,
  "message": "List item barang berhasil diambil",
  "data": [
    {
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
      "gudang_id": 2,
      "checked": true,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z",
      "jenis_barang": {
        "id": 1,
        "kode": "PLT",
        "nama": "Plat"
      },
      "bentuk_barang": {
        "id": 1,
        "kode": "BJA",
        "nama": "Baja"
      },
      "grade_barang": {
        "id": 1,
        "kode": "GRD",
        "nama": "Grade"
      },
      "gudang": {
        "id": 2,
        "nama": "Gudang Utama",
        "kode": "GUD-001"
      }
    },
    {
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
      "gudang_id": 2,
      "checked": false,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z",
      "jenis_barang": {
        "id": 1,
        "kode": "PLT",
        "nama": "Plat"
      },
      "bentuk_barang": {
        "id": 1,
        "kode": "BJA",
        "nama": "Baja"
      },
      "grade_barang": {
        "id": 1,
        "kode": "GRD",
        "nama": "Grade"
      },
      "gudang": {
        "id": 2,
        "nama": "Gudang Utama",
        "kode": "GUD-001"
      }
    }
  ]
}
```

**Catatan:** 
- Field `checked` ditambahkan ke setiap item barang
- `checked=true` jika item barang ada di stock opname detail
- `checked=false` jika item barang tidak ada di stock opname detail atau `stock_opname_id` tidak dikirim
- Response termasuk relasi `jenisBarang`, `bentukBarang`, `gradeBarang`, dan `gudang`

---

## Response Error

### 1. Validation Error (422 Unprocessable Entity)

#### Contoh: gudang_id tidak ada
```json
{
  "success": false,
  "message": "The gudang id field is required.",
  "data": null
}
```

#### Contoh: gudang_id tidak valid
```json
{
  "success": false,
  "message": "The selected gudang id is invalid.",
  "data": null
}
```

#### Contoh: stock_opname_id tidak valid
```json
{
  "success": false,
  "message": "The selected stock opname id is invalid.",
  "data": null
}
```

### 2. Not Found Error (404 Not Found)

#### Contoh: Stock opname tidak ditemukan (jika stock_opname_id dikirim)
```json
{
  "success": false,
  "message": "Stock opname tidak ditemukan",
  "data": null
}
```

---

## Alur Proses

1. **Validasi Request** - Memvalidasi `gudang_id` (required) dan `stock_opname_id` (optional)
2. **Query Item Barang** - Mengambil semua item barang berdasarkan `gudang_id` dengan relasi
3. **Apply Filter** - Menerapkan filter search jika ada
4. **Check Stock Opname Detail** (jika `stock_opname_id` dikirim):
   - Validasi stock opname ada di database
   - Ambil semua `item_barang_id` dari stock opname detail
5. **Transform Items** - Menambahkan flag `checked` ke setiap item:
   - `checked=true` jika `item_barang_id` ada di stock opname detail
   - `checked=false` jika `item_barang_id` tidak ada di stock opname detail atau `stock_opname_id` tidak dikirim
6. **Return Success Response** - Return array item barang dengan flag `checked`

---

## Catatan Penting

1. **Flag Checked**: 
   - Field `checked` ditambahkan ke setiap item barang dalam response
   - `checked=true` berarti item barang sudah ada di stock opname detail
   - `checked=false` berarti item barang belum ada di stock opname detail

2. **Filter Gudang**: 
   - Hanya item barang dengan `gudang_id` yang sesuai yang akan ditampilkan
   - `gudang_id` wajib diisi

3. **Stock Opname ID**: 
   - Jika `stock_opname_id` tidak dikirim, semua item akan memiliki `checked=false`
   - Jika `stock_opname_id` dikirim, akan dicek apakah item ada di detail stock opname tersebut

4. **Search Filter**: 
   - Mendukung pencarian berdasarkan `kode_barang` atau `nama_item_barang`
   - Menggunakan parameter `search` di query string

5. **Relasi**: 
   - Setiap item barang sudah include relasi: `jenisBarang`, `bentukBarang`, `gradeBarang`, dan `gudang`

---

## Contoh Penggunaan dengan cURL

### Tanpa stock_opname_id
```bash
curl -X GET "http://your-domain.com/api/stock-opname/item-barang-list?gudang_id=2" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

### Dengan stock_opname_id
```bash
curl -X GET "http://your-domain.com/api/stock-opname/item-barang-list?gudang_id=2&stock_opname_id=1" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

### Dengan search
```bash
curl -X GET "http://your-domain.com/api/stock-opname/item-barang-list?gudang_id=2&stock_opname_id=1&search=PLT" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

---

## Contoh Penggunaan dengan JavaScript (Fetch API)

### Tanpa stock_opname_id
```javascript
const response = await fetch('http://your-domain.com/api/stock-opname/item-barang-list?gudang_id=2', {
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

### Dengan stock_opname_id
```javascript
const params = new URLSearchParams({
  gudang_id: 2,
  stock_opname_id: 1
});

const response = await fetch(`http://your-domain.com/api/stock-opname/item-barang-list?${params}`, {
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

### Tanpa stock_opname_id
```javascript
import axios from 'axios';

const response = await axios.get('http://your-domain.com/api/stock-opname/item-barang-list', {
  params: {
    gudang_id: 2
  },
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

console.log(response.data);
```

### Dengan stock_opname_id
```javascript
import axios from 'axios';

const response = await axios.get('http://your-domain.com/api/stock-opname/item-barang-list', {
  params: {
    gudang_id: 2,
    stock_opname_id: 1,
    search: 'PLT' // optional
  },
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

console.log(response.data);
```

---

## Perbandingan dengan GET /api/item-barang

| Endpoint | Deskripsi | Flag Checked |
|----------|-----------|--------------|
| `GET /api/item-barang?gudang_id={id}` | List item barang berdasarkan gudang | Tidak ada |
| `GET /api/stock-opname/item-barang-list?gudang_id={id}` | List item barang berdasarkan gudang dengan flag checked | Ada (semua false jika stock_opname_id tidak dikirim) |
| `GET /api/stock-opname/item-barang-list?gudang_id={id}&stock_opname_id={id}` | List item barang dengan flag checked berdasarkan stock opname detail | Ada (true/false berdasarkan detail) |

