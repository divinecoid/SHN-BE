# SHN-BE (Laravel Backend API)

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-Auth-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

> **SHN Backend API** adalah sistem backend untuk aplikasi manajemen inventory dan transaksi dengan fitur autentikasi JWT, role-based access control, dan comprehensive master data management.

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Requirements](#-requirements)
- [Quick Start](#-quick-start)
- [JWT Configuration](#-jwt-configuration)
- [API Documentation](#-api-documentation)
- [Master Data Endpoints](#-master-data-endpoints)
- [Role & Permission](#-role--permission)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Troubleshooting](#-troubleshooting)
- [Struktur Project](#-struktur-project)

## ✨ Fitur Utama

- 🔐 **JWT Authentication** - Secure token-based authentication
- 👥 **Role-Based Access Control** - Multi-level user permissions (Admin, Sales, Operator, Operator Warehouse)
- 📦 **Master Data Management** - Comprehensive CRUD operations untuk 11+ master data
- 🗑️ **Soft Delete & Restore** - Data recovery capabilities
- 🔍 **Advanced Filtering** - Search, sort, dan pagination
- 📊 **API Documentation** - Complete Postman collection included
- 🧪 **Testing Ready** - Built with Pest PHP testing framework
- 🚀 **Production Ready** - Optimized for deployment

## 🛠 Teknologi yang Digunakan

| Technology | Version | Purpose |
|------------|---------|---------|
| **Laravel** | 12.x | PHP Framework |
| **PHP** | 8.2+ | Backend Language |
| **MySQL** | 8.0+ | Database |
| **JWT Auth** | 2.2+ | Authentication |
| **Pest PHP** | 3.8+ | Testing Framework |
| **Composer** | 2.x | Dependency Manager |

## 📋 Requirements

- **PHP** >= 8.2
- **Composer** >= 2.0
- **MySQL** >= 8.0 atau **MariaDB** >= 10.3
- **Node.js** >= 18.x (untuk asset compilation)
- **Git** untuk version control

## 🚀 Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/username/SHN-BE.git
cd SHN-BE
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
```

Edit file `.env` dan sesuaikan konfigurasi database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shn_be
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Generate JWT Secret
```bash
php artisan jwt:secret
```

### 6. Database Setup
```bash
# Jalankan migrasi
php artisan migrate

# Seed data dummy (opsional)
php artisan db:seed
```

### 7. Start Development Server
```bash
php artisan serve
# Server akan berjalan di: http://localhost:8000
```

## 🔐 JWT Configuration

### Setup JWT Secret

JWT secret sudah disediakan di `.env.example`, namun untuk keamanan production, generate secret baru:

```bash
php artisan jwt:secret
```

### JWT Settings

Konfigurasi JWT dapat diubah di `config/jwt.php`:

```php
// Token expiration (default: 60 minutes)
'ttl' => env('JWT_TTL', 60),

// Refresh token expiration (default: 2 weeks)
'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

// Algorithm used for signing
'algo' => env('JWT_ALGO', 'HS256'),
```

### Environment Variables untuk JWT

Tambahkan ke file `.env`:
```env
JWT_SECRET=your_jwt_secret_here
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256
JWT_BLACKLIST_ENABLED=true
```

### JWT Token Usage

Setelah login berhasil, gunakan token di header request:
```http
Authorization: Bearer your_jwt_token_here
```

## 📚 API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|---------|
| `POST` | `/login` | User login | Public |
| `POST` | `/register` | Register new user | Admin only |
| `GET` | `/roles` | Get all roles | Public |

### Example Login Request
```json
POST /api/login
Content-Type: application/json

{
  "username": "admin",
  "password": "password123"
}
```

### Example Login Response
```json
{
  "success": true,
  "message": "Login berhasil",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer"
}
```

## 🗂 Master Data Endpoints

Sistem menyediakan 11 master data dengan operasi CRUD lengkap:

### 1. Jenis Barang (`/api/jenis-barang`)
### 2. Bentuk Barang (`/api/bentuk-barang`)
### 3. Grade Barang (`/api/grade-barang`)
### 4. Item Barang (`/api/item-barang`)
### 5. Jenis Mutasi Stock (`/api/jenis-mutasi-stock`)
### 6. Supplier (`/api/supplier`)
### 7. Pelanggan (`/api/pelanggan`)
### 8. Gudang (`/api/gudang`)
### 9. Pelaksana (`/api/pelaksana`)
### 10. Jenis Biaya (`/api/jenis-biaya`)
### 11. Jenis Transaksi Kas (`/api/jenis-transaksi-kas`)

### Standard Operations untuk Setiap Master Data

| Method | Endpoint Pattern | Description | Access |
|--------|------------------|-------------|---------|
| `GET` | `/{resource}` | List all records | Authenticated |
| `GET` | `/{resource}/{id}` | Get specific record | Authenticated |
| `POST` | `/{resource}` | Create new record | Authenticated |
| `PUT/PATCH` | `/{resource}/{id}` | Update record | Authenticated |
| `DELETE` | `/{resource}/{id}/soft` | Soft delete | Admin only |
| `PATCH` | `/{resource}/{id}/restore` | Restore deleted | Admin only |
| `DELETE` | `/{resource}/{id}/force` | Permanent delete | Admin only |
| `GET` | `/{resource}/with-trashed/all` | List with deleted | Admin only |
| `GET` | `/{resource}/with-trashed/trashed` | List only deleted | Admin only |

### Example Master Data Request
```json
POST /api/jenis-barang
Authorization: Bearer your_jwt_token
Content-Type: application/json

{
  "kode": "J001",
  "nama_jenis": "Elektronik"
}
```

## 👥 Role & Permission

### Available Roles

| Role | ID | Description | Permissions |
|------|----|-----------|-----------| 
| **Admin** | 1 | Full system access | All operations including delete/restore |
| **Sales** | 2 | Sales operations | Read, Create, Update master data |
| **Operator** | 3 | Basic operations | Read, Create, Update master data |
| **Operator Warehouse** | 4 | Warehouse operations | Read, Create, Update master data |

### Default Users (Seeded)

| Username | Password | Role | Email |
|----------|----------|------|-------|
| `admin` | `password123` | Admin | admin@example.com |
| `sales` | `password123` | Sales | sales@example.com |
| `operator` | `password123` | Operator | operator@example.com |
| `warehouse` | `password123` | Operator Warehouse | warehouse@example.com |

### Permission Matrix

| Operation | Admin | Sales | Operator | Warehouse |
|-----------|-------|-------|----------|-----------|
| Read | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ✅ | ✅ |
| Update | ✅ | ✅ | ✅ | ✅ |
| Soft Delete | ✅ | ❌ | ❌ | ❌ |
| Restore | ✅ | ❌ | ❌ | ❌ |
| Force Delete | ✅ | ❌ | ❌ | ❌ |
| View Trashed | ✅ | ❌ | ❌ | ❌ |
| Register User | ✅ | ❌ | ❌ | ❌ |

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run with coverage
php artisan test --coverage
```

### Test Structure

```
tests/
├── Feature/          # Integration tests
│   └── ExampleTest.php
├── Unit/            # Unit tests
│   └── ExampleTest.php
├── Pest.php         # Pest configuration
└── TestCase.php     # Base test case
```

### Writing Tests

Proyek menggunakan **Pest PHP** untuk testing:

```php
it('can login with valid credentials', function () {
    $response = $this->postJson('/api/login', [
        'username' => 'admin',
        'password' => 'password123'
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'token',
                 'token_type'
             ]);
});
```

## 🚀 Deployment

### Production Environment Setup

1. **Server Requirements**
   - PHP 8.2+ dengan extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
   - MySQL 8.0+ atau MariaDB 10.3+
   - Nginx atau Apache
   - Composer
   - SSL Certificate (recommended)

2. **Environment Configuration**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_PORT=3306
DB_DATABASE=your_production_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

JWT_SECRET=your_production_jwt_secret
JWT_TTL=60
```

3. **Deployment Steps**
```bash
# Clone repository
git clone https://github.com/username/SHN-BE.git
cd SHN-BE

# Install dependencies
composer install --optimize-autoloader --no-dev

# Setup environment
cp .env.example .env
# Edit .env with production values

# Generate keys
php artisan key:generate
php artisan jwt:secret

# Database setup
php artisan migrate --force
php artisan db:seed --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

4. **Nginx Configuration**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/SHN-BE/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Docker Deployment (Optional)

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage

EXPOSE 9000
CMD ["php-fpm"]
```

## 🔧 Troubleshooting

### Common Issues

#### 1. JWT Token Issues

**Problem**: "Token invalid atau tidak ada"
```bash
# Solution: Regenerate JWT secret
php artisan jwt:secret

# Clear config cache
php artisan config:clear
```

**Problem**: "Token expired"
```bash
# Solution: Increase TTL in .env
JWT_TTL=120  # 2 hours instead of 1
```

#### 2. Database Connection Issues

**Problem**: "SQLSTATE[HY000] [2002] Connection refused"
```bash
# Check database service
sudo systemctl status mysql

# Verify .env database credentials
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shn_be
DB_USERNAME=root
DB_PASSWORD=your_password
```

#### 3. Permission Issues

**Problem**: "The stream or file could not be opened"
```bash
# Fix storage permissions
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

#### 4. Composer Issues

**Problem**: "Your requirements could not be resolved"
```bash
# Update composer
composer self-update

# Clear composer cache
composer clear-cache

# Install with ignore platform requirements (if needed)
composer install --ignore-platform-reqs
```

#### 5. Migration Issues

**Problem**: "Base table or view already exists"
```bash
# Reset database
php artisan migrate:fresh --seed

# Or rollback and re-migrate
php artisan migrate:rollback
php artisan migrate
```

### Debug Mode

Untuk debugging, aktifkan debug mode di `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Log Files

Check log files untuk error details:
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# System logs (Ubuntu/Debian)
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.2-fpm.log
```

## 📁 Struktur Project

```
SHN-BE/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/              # Authentication controllers
│   │   │   ├── MasterData/        # Master data controllers
│   │   │   ├── Controller.php
│   │   │   ├── RoleController.php
│   │   │   └── UserController.php
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php # JWT & Role middleware
│   │   └── Traits/
│   │       └── ApiFilterTrait.php # API filtering utilities
│   ├── Models/
│   │   ├── MasterData/            # Master data models
│   │   ├── Traits/
│   │   ├── Role.php
│   │   └── User.php
│   └── Providers/
├── config/
│   ├── jwt.php                    # JWT configuration
│   └── ...
├── database/
│   ├── migrations/                # Database migrations
│   ├── seeders/                   # Database seeders
│   └── factories/                 # Model factories
├── routes/
│   ├── api.php                    # API routes
│   └── web.php
├── tests/
│   ├── Feature/                   # Integration tests
│   └── Unit/                      # Unit tests
├── .env.example                   # Environment template
├── composer.json                  # PHP dependencies
├── README.md                      # This file
└── SHN-BE.postman_collection.json # Postman collection
```

### Key Files

| File | Purpose |
|------|---------|
| [`routes/api.php`](routes/api.php) | All API endpoints definition |
| [`app/Http/Middleware/RoleMiddleware.php`](app/Http/Middleware/RoleMiddleware.php) | JWT authentication & role checking |
| [`config/jwt.php`](config/jwt.php) | JWT configuration |
| [`database/seeders/`](database/seeders/) | Default data seeding |
| [`SHN-BE.postman_collection.json`](SHN-BE.postman_collection.json) | Complete API documentation |

---

## 📞 Support

Jika mengalami masalah atau membutuhkan bantuan:

1. **Check Documentation** - Baca dokumentasi ini dengan teliti
2. **Check Logs** - Periksa file log untuk error details
3. **Postman Collection** - Import dan test API endpoints
4. **GitHub Issues** - Report bugs atau request features

---

**Happy Coding! 🚀**

> Dibuat dengan ❤️ menggunakan Laravel 12 & JWT Authentication
