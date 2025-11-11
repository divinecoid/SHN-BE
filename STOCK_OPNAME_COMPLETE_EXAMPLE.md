# Contoh Request dan Response - Complete Stock Opname

## Endpoint
```
PATCH /api/stock-opname/{id}/complete
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
| `id` | integer | Yes | ID stock opname yang akan diselesaikan |

---

## Response Success (200 OK)

### Response Body
```json
{
  "success": true,
  "message": "Stock opname berhasil diselesaikan",
  "data": {
    "id": 1,
    "pic_user_id": 1,
    "gudang_id": 2,
    "catatan": "Stock opname gudang utama bulan November 2025",
    "status": "completed",
    "created_at": "2025-11-02T10:30:00.000000Z",
    "updated_at": "2025-11-02T12:00:00.000000Z",
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
    }
  }
}
```

**Catatan:** 
- Field `status` akan berubah menjadi `completed`
- Field `deleted_at` tidak muncul karena di-hidden di model
- Response termasuk relasi `picUser` dan `gudang`
- Response tidak termasuk `stockOpnameDetails` untuk mengurangi ukuran response

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

### 2. Validation Error (422 Unprocessable Entity)

#### Contoh: Stock opname sudah dibatalkan
```json
{
  "success": false,
  "message": "Stock opname yang sudah dibatalkan tidak dapat diselesaikan",
  "data": null
}
```

#### Contoh: Stock opname sudah selesai
```json
{
  "success": false,
  "message": "Stock opname sudah selesai",
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
  "message": "Gagal menyelesaikan stock opname: [error message]",
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
5. **Update Status** - Update status stock opname menjadi `completed`
6. **Commit Transaction** - Commit semua perubahan
7. **Load Relationships** - Load `picUser` dan `gudang`
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
- Stock opname dengan status `cancelled` tidak dapat diselesaikan
- Stock opname dengan status `completed` tidak dapat diselesaikan lagi
- Hanya stock opname dengan status `draft` atau `active` yang dapat diselesaikan

---

## Catatan Penting

1. **Status Update**: 
   - Status akan diupdate menjadi `completed` dan tidak dapat diubah kembali
   - Setelah status menjadi `completed`, stock opname tidak dapat ditambahkan detail lagi

2. **Unfreeze Items**: 
   - Saat complete, semua items di gudang yang terkait akan di-unfreeze secara otomatis (jika ada yang di-freeze)
   - Jika unfreeze gagal, transaction akan di-rollback dan return error

3. **Transaction**: 
   - Semua operasi dilakukan dalam satu transaction
   - Jika ada error di step manapun (termasuk unfreeze), semua perubahan akan di-rollback

4. **Detail Stock Opname**: 
   - Detail stock opname tidak akan dihapus, hanya status header yang diupdate
   - Semua detail tetap tersimpan dan dapat dilihat

---

## Contoh Penggunaan dengan cURL

```bash
curl -X PATCH "http://your-domain.com/api/stock-opname/1/complete" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

---

## Contoh Penggunaan dengan JavaScript (Fetch API)

```javascript
const response = await fetch('http://your-domain.com/api/stock-opname/1/complete', {
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

const response = await axios.patch('http://your-domain.com/api/stock-opname/1/complete', {}, {
  headers: {
    'Authorization': 'Bearer your_jwt_token_here',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

console.log(response.data);
```

---

## Perbandingan dengan Cancel

| Fitur | Complete | Cancel |
|-------|----------|--------|
| **Status** | `completed` | `cancelled` |
| **Unfreeze Items** | ✅ Ya | ✅ Ya |
| **Dapat Ditambahkan Detail** | ❌ Tidak | ❌ Tidak |
| **Dapat Dibatalkan** | ❌ Tidak | ❌ Tidak |
| **Dapat Diselesaikan** | ❌ Tidak | ❌ Tidak |

---

## Workflow Stock Opname

1. **Create** → Status: `active`
2. **Add Detail** → Menambahkan detail stock opname
3. **Complete** → Status: `completed` (final)
   - Atau
4. **Cancel** → Status: `cancelled` (final)

**Catatan:** Setelah status menjadi `completed` atau `cancelled`, stock opname tidak dapat diubah lagi.

