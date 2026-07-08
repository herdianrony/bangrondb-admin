# Bangron Studio

Dynamic backend platform untuk **BangronDB** — dibangun dengan:
- **Flight PHP** (backend API)
- **Inertia.js + Vue 3 + Vite + Tailwind** (frontend)
- **BangronDB 1.3.0-enhanced** (SQLite document DB, AES-256-GCM) — **embedded di `backend/lib/BangronDB`**

Semua fitur BangronDB punya UI/UX. Schema Builder visual lengkap. **Tidak ada SSOT terpisah – schema enhanced = native schema.**

## Fitur yang di-cover (13 modul + Schema Builder)

1. **Dashboard** – KPI databases, collections, documents, health
2. **Databases** – createDB, dbExists, renameDB, dropDB, listDBs
3. **Collections** – createCollection, rename, drop, list
4. **Documents CRUD** – insert, find, findOne, update, remove, save/upsert, count + pagination + projection
   - **Table auto columns** dari schema: label, sortable, filterable
   - **Relation populate otomatis**
5. **Query Builder** – $gt/$gte/$lt/$lte/$ne, $in/$nin/$all/$size, $exists, $or/$and, $regex, $fuzzy, $where/$func, dot notation
6. **Encryption** – setEncryptionKey, setSearchableFields (hash SHA-256), saveConfiguration
7. **Schema Validation + Schema Builder**
   - type: string, text, email, password, url, slug, int, integer, float, double, number, bool, boolean, checkbox, switch, array, object, json, enum, date, datetime, time, relation, tags
   - rules: required, unique, min, max, regex, enum / options, default, readonly, hidden
   - ui: label, placeholder, icon, rows, badge, color
   - table: filterable, sortable, index, searchable
   - relation: {db, collection, field, display}
   - **Visual Schema Builder UI** – add field, drag reorder, live validate
8. **Soft Deletes** – useSoftDeletes, withTrashed, onlyTrashed, restore, forceDelete
9. **Hooks** – beforeInsert, afterInsert, beforeUpdate, afterUpdate, beforeRemove, afterRemove
10. **Relations / Populate** – populate antar-collection & antar-database
11. **Indexes** – createIndex, dropIndex, getIndexMetrics
12. **Health & Monitoring** – getHealthMetrics, getHealthReport, getPerformanceMetrics, checkIntegrity, vacuum, notifyChange
13. **Config** – ID Mode (auto/manual/prefix), saveConfiguration, loadCollectionConfig

Bonus: **Transactions** API demo.

---

## Struktur baru

```
bangrondb-admin/
  backend/
    public/index.php              # Flight + Inertia
    src/
      Services/BangronService.php
      Inertia/Inertia.php
      Support/SchemaMapper.php    # helper (optional – core sudah native)
    lib/
      BangronDB/                  # <-- BangronDB embedded, patched v1.3 enhanced
        src/
          Traits/SchemaValidationTrait.php  # ← sudah support: relation, date, tags, text, enum options, etc.
    composer.json                 # TIDAK perlu herdianrony/bangrondb
    .env.example
    storage/data/
    seed.php
  frontend/
    src/
      Components/
        SchemaForm.vue            # form auto dari schema
        SchemaBuilder.vue         # ← visual schema builder
      Pages/
        Dashboard/Index.vue
        Databases/Index.vue
        Collections/Index.vue
        Documents/Index.vue       # table auto columns + sort + filter + populate
        Query/Index.vue
        Encryption/Index.vue
        Schema/Index.vue          # Builder / JSON / Validate tabs
        ...
      Layouts/AppLayout.vue
```

**composer.json backend sekarang:**

```json
{
  "require": {
    "php": ">=8.1",
    "mikecao/flight": "^3.13",
    "ext-pdo_sqlite": "*",
    "ext-openssl": "*",
    "ext-json": "*"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/",
      "BangronDB\\": "lib/BangronDB/src/"
    }
  }
}
```

Tidak perlu:
```json
"herdianrony/bangrondb": "dev-master"
```
karena library sudah di `backend/lib/BangronDB` (hasil clone repo kamu, sudah di-patch enhanced types).

---

## Install

Backend:
```bash
cd backend
cp .env.example .env
composer install
# atau: composer dump-autoload
# BangronDB sudah ada di ./lib/BangronDB
# sudah ter-patch: validateType support relation,date,tags,text,enum options, dll
php seed.php
php -S localhost:8000 -t public
```

Frontend:
```bash
cd frontend
npm install
npm run dev
```

Buka http://localhost:8000

---

## Schema Enhanced – Native di BangronDB

Contoh schema Task – **langsung simpan via POST /api/{db}/{col}/schema**, tidak perlu layer SSOT:

```php
[
  'projectId' => [
    'type' => 'relation',
    'label' => 'Project',
    'required' => true,
    'relation' => ['db'=>'projects','collection'=>'projects','field'=>'_id','display'=>'name'],
    'filterable' => true
  ],
  'title' => [
    'type' => 'string',
    'label' => 'Task Title',
    'required' => true,
    'min' => 3,
    'searchable' => true,
    'sortable' => true,
    'index' => true,
    'ui' => ['placeholder'=>'Enter task title','icon'=>'list-checks']
  ],
  'priority' => [
    'type' => 'enum',
    'label' => 'Priority',
    'options' => ['low','medium','high','urgent'],
    'default' => 'medium',
    'filterable' => true,
    'ui' => ['badge'=>true,'color'=>['urgent'=>'red']]
  ],
  // ... dst
]
```

BangronDB core (`lib/BangronDB/src/Traits/SchemaValidationTrait.php`) sudah di-patch:
- `validateType()` kenal: text, email, password, url, slug, date, datetime, time, relation, tags, checkbox, switch, number, decimal, json
- `validate()` kenal `options` sebagai alias `enum`
- Extra keys (`label`, `ui`, `relation`, `filterable`, dll) disimpan apa adanya, di-ignore saat validasi → jadi **schema = SSOT**, single source of truth, dinamis

Jadi alur:
1. **Schema Builder UI** → tambah field, pilih type, isi label, dsb → Save
2. `POST /api/app/tasks/schema {schema: {...}}`
3. Server: `$col->setSchema($enhanced)` → langsung valid, auto createIndex untuk `index|sortable|filterable`, auto searchable
4. `saveConfiguration()` → simpan ke `.bangron`
5. Documents UI langsung baca schema yang sama → render table kolom, form input, relation populate

**Tidak ada file `task_schema.php`, tidak ada `SchemaSSOT`, tidak ada `/api/.../ssot`. Semua dinamis via `/api/{db}/{col}/schema`.**

---

## API ringkas

- `GET /api/status`
- DB: `GET /api/databases`, `POST`, `DELETE`, `POST /rename`
- Collections: `GET /api/{db}/collections` …
- Documents: `GET /api/{db}/{col}/documents?filter=&sort=&limit=&skip=`, `POST`, `PUT /{id}`, `DELETE`, `POST /save`
- Query: `POST /api/{db}/{col}/query`
- Schema: **`GET /api/{db}/{col}/schema`**, **`POST /api/{db}/{col}/schema`** ← terima enhanced schema langsung, **`POST /validate`**
- Encryption, SoftDeletes, Hooks, Populate, Indexes, Health, Config, Transaction → lihat `backend/public/index.php` (± 280 baris, clean, no SSOT routes)

---

## Lisensi

- BangronDB – MIT – herdianrony – now embedded di `backend/lib/BangronDB` (patched v1.3-enhanced, lihat `CHANGELOG_v1.3_LOCAL.md`)
- Admin Studio – MIT – free to use
