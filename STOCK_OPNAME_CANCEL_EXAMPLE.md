# Contoh Request dan Response - Cancel Stock Opname

## Endpoint
```
PATCH /api/stock-opname/{id}/cancel
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
| `id` | integer | Yes | ID stock opname yang akan dibatalkan |

---

## Response Success (200 OK)

### Response Body
```json
{
  "success": true,
  "message": "Stock opname berhasil dibatalkan",
  "data": {
    "id": 1,
    "pic_user_id": 1,
    "gudang_id": 2,
    "catatan": "Stock opname gudang utama bulan November 2025",
    "status": "cancelled",
    "created_at": "2025-11-02T10:30:00.000000Z",
    "updated_at": "2025-11-02T11:00:00.000000Z",
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

---

## Response Error

### 1. Not Found Error (404 Not Found)

#### Contoh: Stock opname tidak ditemukan
```json
{
  "success": false,
  "message": "Data tidak ditemukan",
  "data": null
}
```

### 2. Validation Error (422 Unprocessable Entity)

#### Contoh: Stock opname sudah dibatalkan
```json
{
  "success": false,
  "message": "Stock opname sudah dibatalkan",
  "data": null
}
```

#### Contoh: Stock opname sudah selesai (completed)
```json
{
  "success": false,
  "message": "Stock opname yang sudah selesai tidak dapat dibatalkan",
  "data": null
}
```

### 3. Unfreeze Error (500 Internal Server Error)

#### Contoh: Gagal unfreeze items
```json
{
  "success": false,
  "message": "Gagal melepas status beku barang",
  "data": null
}
```

### 4. Server Error (500 Internal Server Error)

#### Contoh: Error umum
```json
{
  "success": false,
  "message": "Gagal membatalkan stock opname: [error message]",
  "data": null
}
```

---

## Alur Proses

1. **Validasi Stock Opname** - Memastikan stock opname ada di database
2. **Validasi Status** - Memastikan stock opname belum `cancelled` atau `completed`
3. **Begin Transaction** - Memulai database transaction
4. **Unfreeze Items** - Melepas status beku semua items di gudang tersebut
   - Jika unfreeze gagal → Rollback transaction dan return error
5. **Update Status** - Update status stock opname menjadi `cancelled`
6. **Commit Transaction** - Commit semua perubahan
7. **Load Relationships** - Load `picUser`, `gudang`, dan `stockOpnameDetails.itemBarang`
8. **Return Success Response**

---

## Status Stock Opname

| Status | Description |
|--------|-------------|
| `draft` | Draft (belum aktif) |
| `active` | Sedang aktif/diproses (default saat dibuat) |
| `completed` | Sudah selesai |
| `cancelled` | Dibatalkan |

**Catatan:**
- Stock opname dengan status `cancelled` tidak dapat dibatalkan lagi
- Stock opname dengan status `completed` tidak dapat dibatalkan
- Hanya stock opname dengan status `draft` atau `active` yang dapat dibatalkan

---

## Catatan Penting

1. **Unfreeze Items**: Saat cancel, semua items di gudang yang terkait akan di-unfreeze secara otomatis (jika ada yang di-freeze)

2. **Transaction**: Semua operasi dilakukan dalam satu transaction. Jika ada error di step manapun, semua perubahan akan di-rollback.

3. **Status Update**: Status akan diupdate menjadi `cancelled` dan tidak dapat diubah kembali.

4. **Details**: Detail stock opname tidak akan dihapus, hanya status header yang diupdate.

---

## Contoh Penggunaan dengan cURL

```bash
curl -X PATCH "http://your-domain.com/api/stock-opname/1/cancel" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

---

## Contoh Penggunaan dengan JavaScript (Fetch API)

```javascript
const response = await fetch('http://your-domain.com/api/stock-opname/1/cancel', {
  method: 'PATCH',
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

const response = await axios.patch('http://your-domain.com/api/stock-opname/1/cancel', {}, {
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

console.log(response.data);
```

