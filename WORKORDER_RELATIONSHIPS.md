# Work Order Planning - Relasi Database

## Overview
Dokumen ini menjelaskan relasi antar tabel dalam sistem Work Order Planning.

## Struktur Relasi

### 1. WorkOrderPlanning (Header)
**Tabel:** `trx_work_order_planning`

**Relasi:**
```
belongsTo:
├── salesOrder (SalesOrder) - id_sales_order
├── pelanggan (Pelanggan) - id_pelanggan  
├── gudang (Gudang) - id_gudang
└── pelaksana (Pelaksana) - id_pelaksana

hasMany:
└── workOrderPlanningItems (WorkOrderPlanningItem[])

hasOne:
└── invoicePod (InvoicePod)
```

**Field Utama:**
- `wo_unique_id` - Unique identifier
- `nomor_wo` - Nomor work order
- `tanggal_wo` - Tanggal work order
- `prioritas` - Prioritas (HIGH, MEDIUM, LOW)
- `status` - Status (DRAFT, APPROVED, IN_PROGRESS, COMPLETED)
- `handover_method` - Metode penyerahan (pickup, delivery)

---

### 2. WorkOrderPlanningItem (Detail Item)
**Tabel:** `trx_work_order_planning_item`

**Relasi:**
```
belongsTo:
├── workOrderPlanning (WorkOrderPlanning) - work_order_planning_id
├── salesOrderItem (SalesOrderItem) - sales_order_item_id
├── jenisBarang (JenisBarang) - jenis_barang_id
├── bentukBarang (BentukBarang) - bentuk_barang_id
├── gradeBarang (GradeBarang) - grade_barang_id
└── platDasar (ItemBarang) - plat_dasar_id

hasMany:
├── hasManyPelaksana (WorkOrderPlanningPelaksana[])
└── hasManySaranPlatShaftDasar (SaranPlatShaftDasar[])

hasOne:
└── workOrderActualItem (WorkOrderActualItem)
```

**Field Utama:**
- `wo_item_unique_id` - Unique identifier item
- `qty` - Quantity
- `panjang`, `lebar`, `tebal` - Dimensi
- `berat` - Berat
- `catatan` - Catatan item

---

### 3. WorkOrderPlanningPelaksana (Pelaksana per Item)
**Tabel:** `trx_work_order_planning_pelaksana`

**Relasi:**
```
belongsTo:
├── workOrderPlanningItem (WorkOrderPlanningItem) - wo_plan_item_id
└── pelaksana (Pelaksana) - pelaksana_id
```

**Field Utama:**
- `qty` - Quantity yang dikerjakan
- `weight` - Berat yang dikerjakan
- `tanggal` - Tanggal pengerjaan
- `jam_mulai`, `jam_selesai` - Jam kerja
- `catatan` - Catatan pelaksana

---

### 4. SaranPlatShaftDasar (Saran Plat Dasar)
**Tabel:** `trx_saran_plat_shaft_dasar`

**Relasi:**
```
belongsTo:
├── workOrderPlanningItem (WorkOrderPlanningItem) - wo_planning_item_id
└── itemBarang (ItemBarang) - item_barang_id
```

**Field Utama:**
- `is_selected` - Apakah dipilih sebagai plat dasar
- `qty_used` - Quantity yang digunakan

---

## Flow Relasi

```
WorkOrderPlanning (1)
    ↓
WorkOrderPlanningItem (N)
    ├── WorkOrderPlanningPelaksana (N)
    ├── SaranPlatShaftDasar (N)
    └── WorkOrderActualItem (1)
```

## Master Data yang Terkait

### Master Data (belongsTo)
- **SalesOrder** - Sales order yang menjadi referensi
- **Pelanggan** - Data pelanggan
- **Gudang** - Lokasi gudang
- **Pelaksana** - Data pelaksana/operator
- **JenisBarang** - Jenis barang (Aluminium, Steel, dll)
- **BentukBarang** - Bentuk barang (Plate, Shaft, dll)
- **GradeBarang** - Grade barang (A, B, C, dll)
- **ItemBarang** - Item barang sebagai plat dasar

### Transaction Data (hasMany/hasOne)
- **WorkOrderActual** - Realisasi work order
- **InvoicePod** - Invoice proof of delivery

## Contoh Query dengan Relasi

```php
// Get work order dengan semua relasi
$workOrder = WorkOrderPlanning::with([
    'salesOrder',
    'pelanggan', 
    'gudang',
    'workOrderPlanningItems.jenisBarang',
    'workOrderPlanningItems.bentukBarang',
    'workOrderPlanningItems.gradeBarang',
    'workOrderPlanningItems.hasManyPelaksana.pelaksana',
    'workOrderPlanningItems.hasManySaranPlatShaftDasar.itemBarang'
])->find($id);
```

## Notes
- Semua tabel menggunakan **Soft Deletes**
- Relasi menggunakan **HideTimestampsInRelations** trait
- Field `wo_unique_id` dan `wo_item_unique_id` harus unik
- Satu item bisa memiliki multiple pelaksana
- Satu item bisa memiliki multiple saran plat dasar
