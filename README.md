# SHN-BE (Laravel Backend)

## 🚀 Langkah-langkah Menjalankan Project Setelah Clone dari Git

### 1. **Clone Repository**
```bash
# Ganti URL sesuai repo kamu
git clone https://github.com/username/SHN-BE.git
cd SHN-BE
```

### 2. **Copy & Edit Environment**
```bash
cp .env.example .env
# Edit .env sesuai konfigurasi database, mail, dsb
```

### 3. **Install Dependencies**
```bash
composer install
```

### 4. **Generate Key**
```bash
php artisan key:generate
```

### 5. **Migrate Database**
```bash
php artisan migrate
```

### 6. **(Opsional) Seed Data Dummy**
```bash
php artisan db:seed
```

### 7. **Jalankan Server**
```bash
php artisan serve
# Default: http://localhost:8000
```

### 8. **Testing API dengan Postman**
- Import file `SHN-BE.postman_collection.json` ke Postman
- Set variable `base_url` ke alamat backend kamu (misal: http://localhost:8000)
- Login via endpoint `/api/login` untuk dapatkan JWT token
- Set variable `jwt_token` di Postman (otomatis dipakai semua request)
- Cek folder **Master Data** untuk semua endpoint master data (CRUD, soft delete, restore, force delete, with trashed)

### 9. **Role & Akses**
- Semua endpoint master data hanya bisa diakses user yang sudah login (JWT)
- Endpoint delete/restore/with-trashed hanya bisa diakses oleh admin

### 10. **Tips**
- Untuk register user baru, gunakan endpoint `/api/register` (hanya admin)
- Cek file `.env` untuk konfigurasi database, mail, dsb
- Cek folder `database/seeders` untuk struktur data dummy

---

## 📁 Struktur Penting
- `routes/api.php` : Semua route API
- `app/Http/Controllers/MasterData/` : Controller master data
- `app/Models/MasterData/` : Model master data
- `database/migrations/` : Struktur tabel
- `database/seeders/` : Seeder data dummy
- `SHN-BE.postman_collection.json` : Postman collection siap pakai


