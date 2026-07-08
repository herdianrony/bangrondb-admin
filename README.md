# Bangron Studio

<p align="center">
  <strong>Self-hosted Backend Platform</strong><br>
  Database-centric admin studio untuk BangronDB — dibangun dengan Flight PHP, Inertia.js (Vue 3), dan Tailwind CSS.
</p>

---

## Daftar Isi

- [Tentang Bangron Studio](#tentang-bangron-studio)
- [Tech Stack](#tech-stack)
- [Arsitektur & Alur Kerja](#arsitektur--alur-kerja)
- [Fitur Utama](#fitur-utama)
  - [1. Setup Wizard](#1-setup-wizard)
  - [2. Dashboard & KPI](#2-dashboard--kpi)
  - [3. Database Management](#3-database-management)
  - [4. Collection Management](#4-collection-management)
  - [5. Document CRUD dengan Data Table](#5-document-crud-dengan-data-table)
  - [6. Query Builder](#6-query-builder)
  - [7. Schema Validation & Visual Schema Builder](#7-schema-validation--visual-schema-builder)
  - [8. Relasi & Populate](#8-relasi--populate)
  - [9. Field-Level Encryption (AES-256-GCM)](#9-field-level-encryption-aes-256-gcm)
  - [10. Soft Deletes](#10-soft-deletes)
  - [11. Hooks (Event System)](#11-hooks-event-system)
  - [12. Indexing](#12-indexing)
  - [13. Health Monitoring & Performance](#13-health-monitoring--performance)
  - [14. JWT Authentication & RBAC](#14-jwt-authentication--rbac)
  - [15. Access Control Lists (ACL)](#15-access-control-lists-acl)
  - [16. Admin Panel (Users & Roles)](#16-admin-panel-users--roles)
  - [17. Audit Logging](#17-audit-logging)
  - [18. Token Management (Refresh, Blacklist, Revoke)](#18-token-management-refresh-blacklist-revoke)
  - [19. Collection Configuration & ID Modes](#19-collection-configuration--id-modes)
  - [20. Transactions](#20-transactions)
- [Struktur Proyek](#struktur-proyek)
- [Instalasi](#instalasi)
- [Web Routes (Inertia)](#web-routes-inertia)
- [API Reference](#api-reference)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Lisensi](#lisensi)

---

## Tentang Bangron Studio

Bangron Studio adalah platform backend all-in-one yang berfungsi sebagai admin panel dan REST API untuk **BangronDB** — database dokumen embedded berbasis SQLite dengan enkripsi AES-256-GCM. Platform ini terinspirasi dari PocketBase dan Cockpit CMS, menyediakan antarmuka visual untuk mengelola database, koleksi, dokumen, schema, autentikasi, dan kontrol akses tanpa perlu menulis kode backend terpisah.

Semua data disimpan dalam file `.bangron` (format SQLite) yang portabel. Cukup satu folder proyek, satu command untuk menjalankan — cocok untuk prototyping, MVP, dan aplikasi skala kecil-menengah.

---

## Tech Stack

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| **Backend Framework** | Flight PHP | ^3.13 |
| **Database Engine** | BangronDB (SQLite-based) | 1.3.0-enhanced (embedded) |
| **Frontend** | Vue 3 (Composition API) + Inertia.js SSR | 3.x / 1.x |
| **Build Tool** | Vite | 5.x |
| **CSS Framework** | Tailwind CSS | 3.x |
| **Icon Library** | lucide-vue-next | latest |
| **Authentication** | JWT (HS256) + Argon2ID password hashing | — |
| **Encryption** | AES-256-GCM (OpenSSL) | — |
| **PHP Requirement** | PHP 8.1+ (ext-pdo_sqlite, ext-openssl, ext-json) | — |

---

## Arsitektur & Alur Kerja

```
Browser (Vue 3 + Inertia.js)
    ↕ SSR / JSON
Flight PHP (backend/index.php)
    ↕
BangronService (service layer)
    ↕
BangronDB Client → SQLite (.bangron files)
```

**Alur kerja utama:**

1. **Setup Wizard** — Saat pertama kali diakses, pengguna diarahkan ke `/setup` untuk membuat admin account dan database awal. Environment check memastikan semua ekstensi PHP tersedia.
2. **Dashboard** — Setelah setup, halaman utama menampilkan daftar semua database dengan KPI (total collections, documents, health status).
3. **Navigasi Database-Centric** — Sidebar menampilkan tree database → collections. Klik database untuk melihat koleksinya, klik koleksi untuk melihat dokumen-dokumennya.
4. **Schema-Driven UI** — Setiap koleksi bisa memiliki schema yang mendefinisikan tipe field, validasi, label, icon, badge warna, dan lainnya. Data table otomatis menyesuaikan kolom, sort, filter, dan form input berdasarkan schema.
5. **REST API** — Semua operasi CRUD tersedia melalui REST API yang sama, dilindungi oleh JWT auth dan ACL.

---

## Fitur Utama

### 1. Setup Wizard

Halaman pertama yang muncul saat Bangron Studio diakses untuk pertama kalinya. Terdiri dari 4 langkah:

- **Step 0 — Environment Check**: Memverifikasi ketersediaan PHP 8.1+, SQLite3, JSON, OpenSSL, dan izin write storage.
- **Step 1 — Create Admin Account**: Membuat akun administrator pertama dengan username, email, dan password (min. 8 karakter, di-hash dengan Argon2ID).
- **Step 2 — Database & Seed Data**: Memilih nama database utama dan opsi seed koleksi contoh (Blog, Tasks, Products, Users) lengkap dengan schema dan data sampel.
- **Step 3 — Done**: Konfirmasi setup berhasil dengan ringkasan fitur yang tersedia.

**Endpoint:** `GET /setup/status`, `POST /setup/initialize`

Seed data otomatis membuat:
- Database `auth` dengan koleksi `roles` dan `users`
- 5 peran sistem: superadmin, admin, editor, user, guest
- Database aplikasi dengan koleksi yang dipilih, lengkap dengan schema enhanced dan ACL default

---

### 2. Dashboard & KPI

Halaman utama (`/`) setelah setup selesai. Menampilkan:

- **KPI Cards**: Jumlah database, koleksi, dokumen, dan status kesehatan secara real-time.
- **Database Grid**: Kartu untuk setiap database dengan nama, tindakan rename/delete, dan link untuk membuka detail.
- **Create Database Modal**: Form untuk membuat database baru dengan validasi nama (hanya huruf kecil, angka, underscore).

Data diambil melalui `BangronService::dashboardStats()` yang menghitung total database, koleksi, dokumen, dan ukuran file storage secara real-time.

---

### 3. Database Management

Mengelola database BangronDB melalui UI dan API:

| Operasi | Web UI | API Endpoint |
|---------|--------|--------------|
| List databases | Dashboard page | `GET /databases` |
| Create database | Modal form | `POST /databases` |
| Rename database | Prompt dialog | `POST /databases/@name/rename` |
| Drop database | Confirm dialog | `DELETE /databases/@name` |
| Health check | — | `GET /databases/@db/health` |
| Performance metrics | — | `GET /databases/@db/metrics` |
| Vacuum/optimize | — | `POST /databases/@db/vacuum` |

Setiap database disimpan sebagai file `.bangron` di folder `storage/data/`. Database bisa di-rename dan di-drop kapan saja.

---

### 4. Collection Management

Halaman detail database (`/databases/@db`) menampilkan semua koleksi di dalam database tersebut:

- **Collection Cards**: Menampilkan nama, jumlah dokumen, jumlah field (dari schema), dan link ke halaman dokumen.
- **Create Collection**: Modal form dengan validasi nama (hanya huruf kecil, angka, underscore).
- **Delete Collection**: Konfirmasi sebelum menghapus, dengan peringatan bahwa semua dokumen dan schema akan hilang.
- **KPI Row**: Total koleksi, total dokumen, dan engine (SQLite).

| Operasi | API Endpoint |
|---------|--------------|
| List collections | `GET /databases/@db/collections` |
| Create collection | `POST /databases/@db/collections` |
| Drop collection | `DELETE /databases/@db/collections/@col` |
| Rename collection | `POST /databases/@db/collections/@col/rename` |

---

### 5. Document CRUD dengan Data Table

Halaman detail koleksi (`/databases/@db/collections/@col`) adalah inti dari Bangron Studio. Menampilkan data table yang kaya fitur:

**Fitur Data Table:**

- **Auto-generate columns dari schema**: Label, tipe data, sortability, dan filterability otomatis dibaca dari schema koleksi. Jika tidak ada schema, kolom diinfer dari dokumen pertama.
- **Sorting**: Klik header kolom untuk sort ascending/descending. Kolom yang sortable ditandai di schema.
- **Filtering**: Baris filter per kolom (tampil/sembunyikan). Mendukung regex dan exact match.
- **Global Search**: Pencarian di semua kolom bertipe string/text/email/url/slug sekaligus menggunakan operator `$or`.
- **Pagination**: Halaman dengan configurable page size (10/25/50/100).
- **Column Visibility Toggle**: Dropdown untuk menyembunyikan/menampilkan kolom dengan counter.
- **Row Selection & Bulk Actions**: Checkbox per baris dan "select all", dengan bulk delete.
- **Expand Row**: Klik untuk melihat full JSON dokumen.
- **Inline Cell Edit**: Klik ganda sel untuk edit langsung di tabel (save via upsert).
- **Relation Populate**: Kolom bertipe `relation` otomatis di-populate dengan menampilkan `display` field dari koleksi terkait.
- **Soft Delete Support**: Toggle `withTrashed` dan `onlyTrashed` untuk melihat dokumen yang di-soft-delete.
- **Document Form Modal**: Form create/edit yang otomatis menyesuaikan input type (text, textarea, email, date, enum select, checkbox, tags input, url, dll.) berdasarkan schema.

**Operasi CRUD:**

| Operasi | UI | API Endpoint |
|---------|-----|--------------|
| List documents | Data table | `GET /databases/@db/collections/@col/documents` |
| Create document | Modal form | `POST /databases/@db/collections/@col/documents` |
| Update document | Modal form / inline edit | `PUT /databases/@db/collections/@col/documents/@id` |
| Delete document | Confirm dialog | `DELETE /databases/@db/collections/@col/documents` |
| Upsert (save) | Inline cell edit | `POST /databases/@db/collections/@col/save` |
| Advanced query | — | `POST /databases/@db/collections/@col/query` |
| Count | — | `POST /databases/@db/collections/@col/count` |

**Query Parameters (GET documents):**
- `filter` — JSON filter object (e.g., `{"status":"published"}`)
- `sort` — JSON sort object (e.g., `{"created_at":-1}`)
- `limit` — Jumlah dokumen per halaman (default: 50)
- `skip` — Jumlah dokumen yang dilewati (pagination offset)
- `with_trashed` — Include soft-deleted documents (0/1)
- `only_trashed` — Hanya tampilkan soft-deleted (0/1)

---

### 6. Query Builder

BangronDB mendukung query operator lengkap untuk pencarian data yang fleksibel:

| Operator | Contoh | Keterangan |
|----------|--------|------------|
| **Comparison** | `$gt`, `$gte`, `$lt`, `$lte`, `$ne` | Lebih besar, lebih kecil, tidak sama dengan |
| **Set** | `$in`, `$nin`, `$all`, `$size` | Dalam set, tidak dalam set, cocok semua, ukuran array |
| **Existence** | `$exists` | Field ada atau tidak |
| **Logical** | `$or`, `$and` | OR / AND antar kondisi |
| **Pattern** | `$regex` | Regex pattern matching |
| **Fuzzy** | `$fuzzy` | Pencarian fuzzy (approximate) |
| **Function** | `$where`, `$func` | Custom callback function |
| **Dot Notation** | `address.city` | Akses nested field |
| **Null** | `{field: null}` | Cari field yang null atau tidak ada |

**Endpoint:** `POST /databases/@db/collections/@col/query`

```json
{
  "filter": { "status": "published", "views": { "$gte": 100 } },
  "sort": { "published_at": -1 },
  "limit": 25,
  "skip": 0
}
```

---

### 7. Schema Validation & Visual Schema Builder

Schema adalah **Single Source of Truth (SSOT)** untuk setiap koleksi. Didefinisikan langsung di BangronDB core — tidak ada file konfigurasi terpisah.

**Tipe Field yang Didukung:**

| Kategori | Tipe |
|----------|------|
| **Text** | `string`, `text`, `email`, `password`, `url`, `slug` |
| **Number** | `int`, `integer`, `float`, `double`, `number` |
| **Boolean** | `bool`, `boolean`, `checkbox`, `switch` |
| **Composite** | `array`, `object`, `json` |
| **Enum** | `enum` (dengan `options` array) |
| **Date/Time** | `date`, `datetime`, `time` |
| **Special** | `relation`, `tags` |

**Schema Definition:**

```json
{
  "title": {
    "type": "string",
    "label": "Title",
    "required": true,
    "min": 3,
    "max": 200,
    "searchable": true,
    "sortable": true,
    "index": true,
    "ui": { "placeholder": "Enter title", "icon": "list-checks" }
  },
  "priority": {
    "type": "enum",
    "label": "Priority",
    "options": ["low", "medium", "high", "urgent"],
    "default": "medium",
    "filterable": true,
    "sortable": true,
    "ui": { "badge": true, "color": { "urgent": "red", "high": "amber" } }
  },
  "author": {
    "type": "relation",
    "label": "Author",
    "required": true,
    "relation": { "db": "app", "collection": "users", "field": "_id", "display": "name" },
    "filterable": true
  }
}
```

**Metadata Schema:**

| Key | Keterangan |
|-----|------------|
| `label` | Label tampilan di UI |
| `required` | Wajib diisi |
| `unique` | Harus unik dalam koleksi |
| `min` / `max` | Panjang minimum/maximum (string) atau nilai min/max (number) |
| `regex` | Pattern regex untuk validasi |
| `default` | Nilai default |
| `readonly` | Tidak bisa di-edit di form |
| `hidden` | Tidak tampil di tabel (bisa di-toggle) |
| `options` | Pilihan enum |
| `searchable` | Diindex untuk pencarian teks |
| `sortable` | Bisa di-sort di tabel |
| `filterable` | Bisa di-filter di tabel |
| `index` | Auto-create index |
| `ui.placeholder` | Placeholder text di form |
| `ui.icon` | Icon lucide di form |
| `ui.badge` | Tampilkan sebagai badge berwarna |
| `ui.color` | Warna badge per nilai |
| `ui.rows` | Jumlah baris textarea |
| `relation.db/collection/field/display` | Konfigurasi relasi |

**Auto-Index:** Saat schema disimpan, field yang memiliki `index`, `sortable`, atau `filterable` akan otomatis di-create index.

**API:**

| Operasi | Endpoint |
|---------|----------|
| Get schema | `GET /databases/@db/collections/@col/schema` |
| Save schema | `POST /databases/@db/collections/@col/schema` |
| Validate document | `POST /databases/@db/collections/@col/schema/validate` |

---

### 8. Relasi & Populate

Mendukung relasi antar koleksi, bahkan antar database:

- **Schema Definition**: Field bertipe `relation` dengan konfigurasi `{db, collection, field, display}`.
- **Auto-Populate di UI**: Data table otomatis melakukan populate untuk kolom relasi, menampilkan `display` field (misalnya nama user) alih-alih ID.
- **Populate API**: `POST /databases/@db/collections/@col/populate` dengan body `{filter, local_field, foreign, as}`.
- **Cross-Database**: Populate bisa merujuk koleksi di database lain (misalnya `app.users` dari `blog.posts`).

Contoh relasi: field `author_id` di koleksi `posts` yang mereferensikan `_id` di koleksi `users`, menampilkan `name` sebagai label.

---

### 9. Field-Level Encryption (AES-256-GCM)

Melindungi data sensitif dengan enkripsi per-field menggunakan AES-256-GCM:

- **Set Encryption Key**: Mengatur kunci enkripsi per koleksi via `POST /databases/@db/collections/@col/encryption`.
- **Searchable Fields**: Field yang di-enkripsi tetap bisa dicari menggunakan hash SHA-256 (blind index). Dikonfigurasi via `setSearchableFields()`.
- **Konfigurasi Persisten**: Kunci enkripsi dan daftar searchable fields disimpan di `.bangron` config.
- **Environment Variable**: Kunci utama bisa di-set via `ENCRYPTION_KEY` di `.env`.

Alur: Set encryption key → tentukan field searchable → save configuration → data yang di-insert/update setelahnya akan otomatis di-enkripsi.

---

### 10. Soft Deletes

Menghapus dokumen secara logis (tidak permanen) dengan menambahkan `_deleted_at` timestamp:

| Operasi | API Endpoint | Keterangan |
|---------|--------------|------------|
| Toggle soft deletes | `POST /databases/@db/collections/@col/soft-deletes` | Aktifkan/nonaktifkan |
| Restore | `POST /databases/@db/collections/@col/restore` | Pulihkan dokumen |
| Force delete | `POST /databases/@db/collections/@col/force-delete` | Hapus permanen |

Di UI, toggle `withTrashed` dan `onlyTrashed` tersedia di toolbar halaman koleksi untuk melihat/mengelola dokumen yang di-soft-delete.

---

### 11. Hooks (Event System)

BangronDB mendukung lifecycle hooks yang bisa di-attach ke koleksi:

| Hook Event | Keterangan |
|------------|------------|
| `beforeInsert` | Sebelum dokumen disimpan baru |
| `afterInsert` | Setelah dokumen berhasil disimpan |
| `beforeUpdate` | Sebelum dokumen diupdate |
| `afterUpdate` | Setelah dokumen berhasil diupdate |
| `beforeRemove` | Sebelum dokumen dihapus |
| `afterRemove` | Setelah dokumen berhasil dihapus |

**API:** `GET /databases/@db/collections/@col/hooks` — Melihat daftar hook yang terdaftar.

Hooks bisa digunakan untuk validasi kustom, auto-timestamp, notifikasi, atau transformasi data.

---

### 12. Indexing

Mengoptimalkan performa query dengan index pada field tertentu:

| Operasi | API Endpoint |
|---------|--------------|
| Create index | `POST /databases/@db/collections/@col/indexes` |
| List indexes (DB-level) | `GET /databases/@db/indexes` |
| Drop index (DB-level) | `DELETE /databases/@db/indexes/@name` |

**Auto-Index:** Saat schema disimpan, field dengan flag `index`, `sortable`, atau `filterable` otomatis mendapat index. Bisa juga manually create index melalui API.

---

### 13. Health Monitoring & Performance

Memantau kesehatan dan performa database secara real-time:

| Endpoint | Keterangan |
|----------|------------|
| `GET /databases/@db/health` | Health metrics: integrity check, koleksi, ukuran, index count |
| `GET /databases/@db/metrics` | Performance metrics, collection-level metrics |
| `POST /databases/@db/vacuum` | Vacuum/optimize database (shrink file size) |

BangronDB juga menyediakan `getHealthReport()` dan `getPerformanceMetrics()` untuk analisis mendalam.

---

### 14. JWT Authentication & RBAC

Sistem autentikasi berbasis JWT dengan Role-Based Access Control:

**Fitur Autentikasi:**

- **Register**: `POST /auth/register` — Buat akun baru dengan username, email, password (Argon2ID hash).
- **Login**: `POST /auth/login` — Mendapatkan access token + refresh token pair.
- **Refresh**: `POST /auth/refresh` — Rotate refresh token, dapatkan token pair baru.
- **Logout**: `POST /auth/logout` — Revoke access + refresh token.
- **Me**: `GET /auth/me` — Cek profil user dari token yang aktif.
- **Password Hashing**: Menggunakan `PASSWORD_ARGON2ID` (algoritma terkuat yang tersedia).
- **Token Pair**: Access token (short-lived, default 15 menit) + Refresh token (long-lived, default 30 hari).
- **Token Rotation**: Setiap refresh, token lama di-revoke dan token baru diterbitkan.

**Roles (dibuat saat setup):**

| Role | Permissions |
|------|-------------|
| `superadmin` | `*` (semua akses) |
| `admin` | read, create, update, delete, manage_schema, manage_acl |
| `editor` | read, create, update |
| `user` | read |
| `guest` | (tidak ada akses) |

---

### 15. Access Control Lists (ACL)

Sistem ACL per-koleksi yang granular, mendukung row-level dan field-level filtering:

**Konfigurasi ACL per Koleksi:**

```json
{
  "enabled": true,
  "default_role": "guest",
  "roles": {
    "superadmin": ["*"],
    "admin": ["read", "create", "update", "delete", "manage_schema"],
    "editor": ["read", "create", "update"],
    "user": ["read"],
    "guest": []
  },
  "field_rules": {
    "editor": { "salary": "deny", "password_hash": "deny" }
  },
  "row_filters": {
    "user": { "owner_id": "{user.id}" }
  },
  "api_keys": []
}
```

**Level Akses:**

| Level | Keterangan |
|-------|------------|
| **Role-level** | Setiap role punya daftar permission yang diizinkan |
| **Field-level** | Deny/allow akses ke field tertentu per role |
| **Row-level** | Filter dokumen berdasarkan kondisi (misalnya hanya dokumen milik user) |
| **API Keys** | Akses via API key (bukan JWT) |

**API:**

| Operasi | Endpoint |
|---------|----------|
| Get ACL config | `GET /databases/@db/collections/@col/acl` |
| Save ACL config | `PUT /databases/@db/collections/@col/acl` |
| Test permission | `POST /databases/@db/collections/@col/acl/test` |

ACL middleware (`AclMiddleware`) secara otomatis:
- Membaca JWT token dari header Authorization
- Men-load ACL config koleksi
- Menerapkan row filter ke query
- Memfilter field dari response

---

### 16. Admin Panel (Users & Roles)

Manajemen pengguna dan peran melalui API admin:

**Users:**

| Operasi | Endpoint |
|---------|----------|
| List users | `GET /admin/users` |
| Create user | `POST /admin/users` |
| Update user | `PUT /admin/users/@id` |
| Delete user | `DELETE /admin/users/@id` |
| Reset password | `POST /admin/users/@id/reset-password` |
| Toggle active | `POST /admin/users/@id/toggle-active` |
| Revoke all tokens | `POST /admin/users/@id/revoke-tokens` |

**Roles:**

| Operasi | Endpoint |
|---------|----------|
| List roles | `GET /admin/roles` |
| Create role | `POST /admin/roles` |
| Update role | `PUT /admin/roles/@name` |
| Delete role | `DELETE /admin/roles/@name` |

**Permission Test:** `POST /admin/acl/check` — Menguji apakah user/role tertentu memiliki akses ke operasi tertentu.

---

### 17. Audit Logging

Semua operasi penting (auth, CRUD, ACL changes) dicatat ke audit log:

- **Log Entry**: Mencatat action, database, collection, data, diff, dan status.
- **View Logs**: `GET /audit/logs` — Melihat riwayat operasi.
- **Stats**: `GET /audit/stats` — Statistik audit (jumlah per action type).

Audit log disimpan di database `auth`, koleksi `audit_logs`, termasuk:
- Login success/failure
- Token issue/refresh/revoke
- Document insert/update/delete
- Schema changes

---

### 18. Token Management (Refresh, Blacklist, Revoke)

Sistem manajemen token yang komprehensif untuk keamanan sesi:

| Fitur | Endpoint | Keterangan |
|-------|----------|------------|
| Active refresh tokens | `GET /auth/tokens` | Lihat semua refresh token aktif |
| Token blacklist | `GET /auth/blacklist` | Lihat semua token yang di-revoke |
| Manual revoke | `POST /auth/revoke` | Revoke token berdasarkan JTI |
| Auto-purge | — | Token kadaluarsa otomatis di-purge |

**Refresh Token Store**: Menyimpan refresh token di koleksi `auth.refresh_tokens` dengan metadata (username, roles, IP, User-Agent, rotated_from).

**Token Blacklist**: Menyimpan JTI token yang di-revoke di koleksi `auth.token_blacklist` dengan alasan (logout, refresh_rotate, manual).

---

### 19. Collection Configuration & ID Modes

Mengelola konfigurasi koleksi dan mode pembuatan ID:

**ID Modes:**

| Mode | Keterangan | Contoh |
|------|------------|--------|
| `auto` (default) | Auto-generated ID (timestamp + random) | `65f1a2b3c4d5e` |
| `manual` | User menentukan ID sendiri | `post-001` |
| `prefix` | Auto ID dengan prefix kustom | `usr_65f1a2b3` |

**API:**

| Operasi | Endpoint |
|---------|----------|
| Get config | `GET /databases/@db/collections/@col/config` |
| Save config | `POST /databases/@db/collections/@col/config/save` |
| Set ID mode | `POST /databases/@db/collections/@col/id-mode` |

Konfigurasi koleksi disimpan di file `.bangron` (format SQLite metadata table) dan persisten antar sesi.

---

### 20. Transactions

Mendukung database transaction untuk operasi batch yang atomic:

**Endpoint:** `POST /databases/@db/transaction`

```json
{
  "operations": [
    { "collection": "users", "action": "insert", "document": { "name": "John" } },
    { "collection": "posts", "action": "insert", "document": { "title": "Hello", "author": "John" } },
    { "collection": "logs", "action": "find", "filter": {} }
  ]
}
```

Jika salah satu operasi gagal, seluruh transaction di-rollback. Mendukung action: `insert`, `update`, `remove`, `find`.

---

## Struktur Proyek

```
bangron-studio-latest/
├── backend/
│   ├── public/
│   │   └── index.php              # Entry point (Flight + Inertia)
│   ├── src/
│   │   ├── Controllers/
│   │   │   ├── InertiaController.php   # Web/SSR page rendering
│   │   │   ├── SetupController.php     # Setup wizard API
│   │   │   ├── AuthController.php      # JWT auth (register, login, refresh, etc.)
│   │   │   ├── AdminController.php     # User & role management
│   │   │   ├── DatabaseController.php  # Database CRUD
│   │   │   ├── CollectionController.php # Collection CRUD
│   │   │   ├── DocumentController.php  # Document CRUD + query
│   │   │   ├── SchemaController.php    # Schema management
│   │   │   ├── AclController.php       # ACL management
│   │   │   ├── EncryptionController.php # Field encryption
│   │   │   ├── SoftDeleteController.php # Soft delete ops
│   │   │   ├── HookController.php      # Hooks management
│   │   │   ├── RelationController.php  # Populate/relations
│   │   │   ├── IndexController.php     # Indexing
│   │   │   ├── HealthController.php    # Health & monitoring
│   │   │   ├── ConfigController.php    # Collection config & ID mode
│   │   │   ├── AuditController.php     # Audit logs
│   │   │   └── StatusController.php    # System status
│   │   ├── Services/
│   │   │   └── BangronService.php      # Central service layer
│   │   ├── Security/
│   │   │   ├── Jwt.php                 # JWT encode/decode
│   │   │   ├── Acl.php                 # ACL engine
│   │   │   ├── Audit.php               # Audit logging
│   │   │   ├── TokenBlacklist.php      # Token revocation
│   │   │   └── RefreshTokenStore.php   # Refresh token storage
│   │   ├── Http/
│   │   │   ├── Routes/
│   │   │   │   ├── web.php             # Inertia page routes
│   │   │   │   ├── api.php             # REST API routes
│   │   │   │   ├── auth.php            # Auth API routes
│   │   │   │   └── admin.php           # Admin API routes
│   │   │   └── Middleware/
│   │   │       ├── AclMiddleware.php   # JWT + ACL guard
│   │   │       └── CorsMiddleware.php  # CORS headers
│   │   ├── Inertia/
│   │   │   └── Inertia.php             # Inertia.js SSR bridge
│   │   ├── Support/
│   │   │   └── SchemaMapper.php        # Schema helper (index extraction)
│   │   ├── Logging/
│   │   │   └── LoggerFactory.php       # Structured logging
│   │   └── bootstrap.php               # App bootstrap, constants
│   ├── lib/
│   │   └── BangronDB/                  # Embedded BangronDB v1.3-enhanced
│   │       ├── src/
│   │       │   ├── Client.php          # Main client
│   │       │   ├── Database.php        # Database instance
│   │       │   ├── Collection.php      # Collection instance
│   │       │   ├── Cursor.php          # Query cursor
│   │       │   ├── Config.php          # Configuration
│   │       │   ├── QueryExecutor.php   # Query execution engine
│   │       │   ├── CollectionManager.php # Collection management
│   │       │   ├── DatabaseMetrics.php # Metrics collection
│   │       │   ├── Traits/
│   │       │   │   ├── EncryptionTrait.php
│   │       │   │   ├── SchemaValidationTrait.php
│   │       │   │   ├── SoftDeleteTrait.php
│   │       │   │   ├── HooksTrait.php
│   │       │   │   ├── SearchableFieldsTrait.php
│   │       │   │   ├── IdGeneratorTrait.php
│   │       │   │   ├── QueryBuilderTrait.php
│   │       │   │   ├── ChangeTrackingTrait.php
│   │       │   │   └── ConfigurationPersistenceTrait.php
│   │       │   ├── Security/
│   │       │   │   └── FieldValidator.php
│   │       │   └── Enums/
│   │       │       ├── HookEvent.php
│   │       │       └── IdMode.php
│   │       └── tests/                  # Unit & integration tests
│   ├── storage/
│   │   ├── data/                       # Database files (.bangron)
│   │   └── logs/                       # Application logs
│   ├── composer.json
│   ├── seed.php                        # Manual seed script
│   └── .env.example
│
├── frontend/
│   ├── src/
│   │   ├── Pages/
│   │   │   ├── Setup/Index.vue         # Setup wizard (4 steps)
│   │   │   ├── Auth/
│   │   │   │   ├── Login.vue           # Login page
│   │   │   │   └── Register.vue        # Register page
│   │   │   ├── Dashboard/Index.vue     # Database list + KPIs
│   │   │   ├── Databases/
│   │   │   │   ├── Index.vue           # (legacy, unused)
│   │   │   │   └── Show.vue            # Collections in a DB
│   │   │   └── Collections/
│   │   │       ├── Index.vue           # (legacy, unused)
│   │   │       └── Show.vue            # Documents table in a collection
│   │   ├── Components/
│   │   │   ├── DataTable.vue           # Reusable data table component
│   │   │   ├── DocumentFormModal.vue   # Auto-generated form from schema
│   │   │   ├── SchemaForm.vue          # Schema field editor
│   │   │   ├── SchemaBuilder.vue       # Visual schema builder
│   │   │   └── ToastContainer.vue      # Global toast notifications
│   │   ├── Layouts/
│   │   │   ├── AppLayout.vue           # Main layout (sidebar + content)
│   │   │   ├── SetupLayout.vue         # Setup wizard layout (centered)
│   │   │   └── AuthLayout.vue          # Auth pages layout
│   │   ├── composables/
│   │   │   └── useToast.js             # Toast notification composable
│   │   ├── lib/
│   │   │   └── api.js                  # API helper
│   │   ├── app.css                     # Global styles + custom utilities
│   │   └── main.js                     # Vue app entry + skeleton CSS
│   ├── index.html
│   ├── vite.config.js
│   ├── tailwind.config.js
│   ├── postcss.config.js
│   └── package.json
│
├── run.sh                              # Quick start script
└── README.md
```

---

## Instalasi

### Prasyarat

- PHP 8.1+ dengan ekstensi: `pdo_sqlite`, `openssl`, `json`
- Node.js 18+ dan npm (untuk frontend build)
- Composer (untuk PHP dependencies)

### Langkah-langkah

**1. Backend Setup:**

```bash
cd backend
cp .env.example .env
composer install
# BangronDB sudah embedded di ./lib/BangronDB — tidak perlu install terpisah
php -S localhost:8000 -t public
```

**2. Frontend Setup (development):**

```bash
cd frontend
npm install
npm run dev
```

**3. Frontend Build (production):**

```bash
cd frontend
npm run build
```

**4. Akses Aplikasi:**

Buka `http://localhost:8000` — Setup Wizard akan otomatis muncul jika belum ada admin account.

### Quick Start (run.sh)

```bash
chmod +x run.sh
./run.sh
```

---

## Web Routes (Inertia)

Route yang merender halaman Vue via Inertia.js SSR:

| Route | Page Component | Keterangan |
|-------|---------------|------------|
| `GET /` | `Dashboard/Index` | Dashboard (atau Setup jika belum init) |
| `GET /setup` | `Setup/Index` | Setup wizard |
| `GET /auth/login` | `Auth/Login` | Halaman login |
| `GET /auth/register` | `Auth/Register` | Halaman register |
| `GET /databases/@db` | `Databases/Show` | Detail database (daftar koleksi) |
| `GET /databases/@db/collections/@col` | `Collections/Show` | Detail koleksi (tabel dokumen) |

---

## API Reference

### Database Operations

```
GET    /databases                        List all databases
POST   /databases                        Create database
DELETE /databases/@name                  Drop database
POST   /databases/@name/rename           Rename database
GET    /databases/@db/health             Health metrics
GET    /databases/@db/metrics            Performance metrics
POST   /databases/@db/vacuum             Vacuum database
POST   /databases/@db/transaction        Run transaction
GET    /databases/@db/indexes            List indexes
DELETE /databases/@db/indexes/@name      Drop index
```

### Collection Operations

```
GET    /databases/@db/collections                List collections
POST   /databases/@db/collections                Create collection
DELETE /databases/@db/collections/@col           Drop collection
POST   /databases/@db/collections/@col/rename    Rename collection
```

### Document Operations

```
GET    /databases/@db/collections/@col/documents              List documents (filter, sort, paginate)
POST   /databases/@db/collections/@col/documents              Insert document
GET    /databases/@db/collections/@col/documents/@id          Get single document
PUT    /databases/@db/collections/@col/documents/@id          Update document
DELETE /databases/@db/collections/@col/documents              Delete documents (by filter)
POST   /databases/@db/collections/@col/save                   Upsert document
POST   /databases/@db/collections/@col/count                  Count documents
POST   /databases/@db/collections/@col/query                  Advanced query
```

### Schema

```
GET    /databases/@db/collections/@col/schema                Get schema
POST   /databases/@db/collections/@col/schema                Save schema
POST   /databases/@db/collections/@col/schema/validate       Validate document against schema
```

### Authentication

```
POST   /auth/register          Register new user
POST   /auth/login             Login (returns JWT pair)
POST   /auth/refresh           Refresh token pair
POST   /auth/logout            Revoke tokens
POST   /auth/revoke            Revoke token by JTI
GET    /auth/blacklist         List revoked tokens
GET    /auth/tokens            List active refresh tokens
GET    /auth/me                Get current user profile
```

### Admin

```
GET    /admin/users                              List users
POST   /admin/users                              Create user
PUT    /admin/users/@id                          Update user
DELETE /admin/users/@id                          Delete user
POST   /admin/users/@id/reset-password           Reset password
POST   /admin/users/@id/toggle-active            Toggle active status
POST   /admin/users/@id/revoke-tokens            Revoke all user tokens
GET    /admin/roles                              List roles
POST   /admin/roles                              Create role
PUT    /admin/roles/@name                        Update role
DELETE /admin/roles/@name                        Delete role
POST   /admin/acl/check                          Test permission
```

### Collection Sub-resources

```
GET    /databases/@db/collections/@col/acl                  Get ACL config
PUT    /databases/@db/collections/@col/acl                  Save ACL config
POST   /databases/@db/collections/@col/acl/test             Test ACL
GET    /databases/@db/collections/@col/config                Get config
POST   /databases/@db/collections/@col/config/save           Save config
POST   /databases/@db/collections/@col/id-mode               Set ID mode
POST   /databases/@db/collections/@col/encryption            Set encryption key
POST   /databases/@db/collections/@col/soft-deletes          Toggle soft deletes
POST   /databases/@db/collections/@col/restore               Restore soft-deleted
POST   /databases/@db/collections/@col/force-delete          Force delete permanently
GET    /databases/@db/collections/@col/hooks                 List hooks
POST   /databases/@db/collections/@col/populate              Populate relations
POST   /databases/@db/collections/@col/indexes               Create index
```

### System

```
GET    /status                 System status
GET    /setup/status           Setup status check
POST   /setup/initialize       Run setup
GET    /audit/logs             Audit log entries
GET    /audit/stats            Audit statistics
```

---

## Konfigurasi Environment

Variabel lingkungan yang didukung (dari `.env`):

| Variable | Default | Keterangan |
|----------|---------|------------|
| `ENCRYPTION_KEY` | — | Kunci enkripsi AES-256 (min. 32 karakter) |
| `JWT_SECRET` | falls back to `ENCRYPTION_KEY` | Secret untuk JWT signing |
| `JWT_ACCESS_TTL` | `900` (15 min) | Access token lifetime (seconds) |
| `JWT_REFRESH_TTL` | `2592000` (30 days) | Refresh token lifetime (seconds) |
| `AUTH_DB` | `auth` | Database untuk autentikasi |
| `AUTH_COLLECTION` | `users` | Koleksi untuk user accounts |
| `QUERY_LOGGING` | `false` | Aktifkan query logging |
| `PERFORMANCE_MONITORING` | `false` | Aktifkan performance monitoring |
| `BANGRON_DB_PATH` | `backend/storage/data` | Path ke folder database |

---

## Lisensi

- **BangronDB** — MIT License — [herdianrony](https://github.com/herdianrony) — Embedded di `backend/lib/BangronDB` (patched v1.3-enhanced)
- **Bangron Studio** — MIT License — Free to use, modify, and distribute