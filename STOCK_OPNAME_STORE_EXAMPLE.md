# Contoh Request dan Response - Store Stock Opname

## Endpoint
```
POST /api/stock-opname
```

## Request Headers
```
Authorization: Bearer {your_jwt_token}
Content-Type: application/json
Accept: application/json
```

---

## Request Body

### Contoh 1: Request dengan freeze items (should_freeze = true)
```json
{
  "pic_user_id": 1,
  "gudang_id": 2,
  "catatan": "Stock opname gudang utama bulan November 2025",
  "should_freeze": true
}
```

### Contoh 2: Request tanpa freeze items (should_freeze = false atau tidak dikirim)
```json
{
  "pic_user_id": 1,
  "gudang_id": 2,
  "catatan": "Stock opname gudang utama bulan November 2025",
  "should_freeze": false
}
```

### Contoh 3: Request minimal (tanpa catatan dan should_freeze)
```json
{
  "pic_user_id": 1,
  "gudang_id": 2
}
```

---

## Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `pic_user_id` | integer | Yes | ID user yang bertanggung jawab (PIC) - harus exists di tabel `users` |
| `gudang_id` | integer | Yes | ID gudang yang akan di-stock opname - harus exists di tabel `ref_gudang` |
| `catatan` | string | No | Catatan tambahan untuk stock opname |
| `should_freeze` | boolean | No | Jika `true`, akan freeze items di gudang tersebut. Jika `false` atau tidak dikirim, akan unfreeze items |

---

## Response Success (200 OK)

### Response Body
```json
{
  "success": true,
  "message": "Data berhasil ditambahkan",
  "data": {
    "id": 1,
    "pic_user_id": 1,
    "gudang_id": 2,
    "catatan": "Stock opname gudang utama bulan November 2025",
    "created_at": "2025-11-02T10:30:00.000000Z",
    "updated_at": "2025-11-02T10:30:00.000000Z",
    "pic_user": {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    },
    "gudang": {
      "id": 2,
      "nama": "Gudang Utama",
      "kode": "GUD-001",
      "tipe_gudang_id": 1,
      "parent_id": null,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    },
    "stock_opname_details": []
  }
}
```

**Catatan:** 
- Field `stock_opname_details` akan kosong array `[]` karena detail ditambahkan melalui endpoint `update` (PUT/PATCH)
- Field `deleted_at` tidak muncul karena di-hidden di model

---

## Response Error

### 1. Validation Error (422 Unprocessable Entity)

#### Contoh: pic_user_id tidak ada
```json
{
  "success": false,
  "message": "The pic user id field is required.",
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

#### Contoh: should_freeze bukan boolean
```json
{
  "success": false,
  "message": "The should freeze field must be true or false.",
  "data": null
}
```

### 2. Freeze/Unfreeze Error (500 Internal Server Error)

#### Contoh: Gagal freeze items
```json
{
  "success": false,
  "message": "Gagal membekukan barang",
  "data": null
}
```

#### Contoh: Gagal unfreeze items
```json
{
  "success": false,
  "message": "Gagal melepas status beku barang",
  "data": null
}
```

### 3. Server Error (500 Internal Server Error)

#### Contoh: Error umum
```json
{
  "success": false,
  "message": "Gagal menyimpan data: [error message]",
  "data": null
}
```

### 4. Not Found Error (404 Not Found)

Jika terjadi error saat freeze/unfreeze dan method mengembalikan status selain 200:
```json
{
  "success": false,
  "message": "[error message dari freeze/unfreeze]",
  "data": null
}
```

---

## Alur Proses

1. **Validasi Request** - Memvalidasi semua field yang dikirim
2. **Begin Transaction** - Memulai database transaction
3. **Freeze/Unfreeze Items** (jika `should_freeze` dikirim):
   - Jika `should_freeze = true` → Panggil `freezeItems()` dari `ItemBarangController`
   - Jika `should_freeze = false` → Panggil `unfreezeItems()` dari `ItemBarangController`
   - Jika freeze/unfreeze gagal → Rollback transaction dan return error
4. **Create Stock Opname Header** - Membuat record di tabel `trx_stock_opname`
5. **Commit Transaction** - Commit semua perubahan
6. **Load Relationships** - Load `picUser`, `gudang`, dan `stockOpnameDetails.itemBarang`
7. **Return Success Response**

---

## Catatan Penting

1. **Detail Stock Opname**: Endpoint `store` hanya membuat header stock opname. Detail (items) ditambahkan melalui endpoint `update` (PUT/PATCH `/api/stock-opname/{id}`)

2. **Freeze/Unfreeze**: 
   - `should_freeze = true` → Freeze semua items di gudang tersebut sebelum membuat stock opname
   - `should_freeze = false` atau tidak dikirim → Unfreeze semua items di gudang tersebut
   - Proses freeze/unfreeze dilakukan dalam transaction yang sama dengan create stock opname

3. **Transaction**: Semua operasi dilakukan dalam satu transaction. Jika ada error di step manapun, semua perubahan akan di-rollback.

4. **Soft Delete**: Model menggunakan soft delete, jadi data tidak benar-benar dihapus dari database.

---

## Contoh Penggunaan dengan cURL

```bash
curl -X POST "http://your-domain.com/api/stock-opname" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "pic_user_id": 1,
    "gudang_id": 2,
    "catatan": "Stock opname gudang utama bulan November 2025",
    "should_freeze": true
  }'
```

---

## Contoh Penggunaan dengan JavaScript (Fetch API)

```javascript
const response = await fetch('http://your-domain.com/api/stock-opname', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    pic_user_id: 1,
    gudang_id: 2,
    catatan: 'Stock opname gudang utama bulan November 2025',
    should_freeze: true
  })
});

const data = await response.json();
console.log(data);
```

---

## Contoh Penggunaan dengan Axios

```javascript
import axios from 'axios';

const response = await axios.post('http://your-domain.com/api/stock-opname', {
  pic_user_id: 1,
  gudang_id: 2,
  catatan: 'Stock opname gudang utama bulan November 2025',
  should_freeze: true
}, {
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

console.log(response.data);
```

