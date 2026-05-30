# Smart Agriculture API

**Platform Integrasi Data Pertanian Nasional**  
Tim gABut_BaNG · Universitas Brawijaya · GEMASTIK XVII 2026

---

## Tech Stack

- **Laravel 12** + PHP 8.2+
- **PostgreSQL** (database utama)
- **Redis** (cache dashboard, session)
- **MinIO** (S3-compatible, untuk foto lahan)
- **Laravel Sanctum** (API token auth, 8 jam expiry)
- **Laravel Queue** (email notifications)
- **DomPDF** (ekspor laporan PDF)
- **Mailtrap** (email dev/testing)

---

## Setup

```bash
# 1. Install dependencies
composer install

# 2. Copy env (sudah dikonfigurasi, sesuaikan jika perlu)
cp .env.example .env
php artisan key:generate

# 3. Jalankan migrasi + seeder
php artisan migrate --seed

# 4. Jalankan queue worker (untuk email notifications)
php artisan queue:work

# 5. Jalankan server
php artisan serve
```

---

## Demo Accounts (dari seeder)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@smartagriculture.id | Admin@12345 |
| Aparatur Desa | aparatur@smartagriculture.id | Aparatur@12345 |
| Bhabinkamtibmas | bhabinkamtibmas@smartagriculture.id | Bhabink@12345 |
| Dinas | dinas@smartagriculture.id | Dinas@12345 |
| Pemerintah Pusat | pusat@smartagriculture.id | Pusat@12345 |

---

## API Endpoints

### Auth
| Method | Endpoint | Roles | Keterangan |
|--------|----------|-------|------------|
| POST | `/api/auth/login` | Public | Login, returns Sanctum token |
| POST | `/api/auth/register` | Public/Admin | Register aparatur_desa, atau admin untuk role lain |
| GET | `/api/auth/me` | All | Data user yang sedang login |
| PUT | `/api/auth/profile` | All | Update nama/no_hp/password |
| POST | `/api/auth/logout` | All | Revoke token |
| POST | `/api/auth/refresh` | All | Re-issue token |

### Data Pertanian
| Method | Endpoint | Roles | Keterangan |
|--------|----------|-------|------------|
| GET | `/api/data-pertanian` | All | List data (RBAC filtered) |
| POST | `/api/data-pertanian` | aparatur_desa | Submit/simpan draft data baru |
| GET | `/api/data-pertanian/{id}` | All | Detail satu data |
| PUT | `/api/data-pertanian/{id}` | aparatur_desa | Update data (jika belum disetujui) |
| DELETE | `/api/data-pertanian/{id}` | aparatur_desa | Hapus draft/ditolak |
| POST | `/api/data-pertanian/{id}/submit` | aparatur_desa | Submit draft untuk validasi |

**Query params:** `?status=menunggu&komoditas=padi&wilayah_id=...&periode=2024-01&musim_tanam=MH&per_page=15`

### Validasi
| Method | Endpoint | Roles | Keterangan |
|--------|----------|-------|------------|
| GET | `/api/validasi/antrian` | bhabinkamtibmas, admin | Antrian data menunggu validasi |
| POST | `/api/validasi/{id}` | bhabinkamtibmas | Setujui atau tolak data |
| GET | `/api/validasi/riwayat` | bhabinkamtibmas, dinas, pusat, admin | Riwayat validasi |

**Body validasi:** `{ "keputusan": "ditolak", "catatan": "Data tidak sesuai kondisi lapangan..." }`

### Dashboard
| Method | Endpoint | Roles | Keterangan |
|--------|----------|-------|------------|
| GET | `/api/dashboard` | All | Stats sesuai role |
| GET | `/api/dashboard/peta` | dinas, pusat, admin | Heatmap data per wilayah |
| GET | `/api/statistik-nasional` | Public | Statistik landing page |

### Laporan
| Method | Endpoint | Roles | Keterangan |
|--------|----------|-------|------------|
| GET | `/api/laporan/export?format=csv` | dinas, pusat, admin | Export CSV/PDF |
| GET | `/api/laporan/ringkasan` | dinas, pusat, admin | Ringkasan per tahun |
| GET | `/api/laporan/audit` | admin | Audit log immutable |

### Wilayah
| Method | Endpoint | Roles | Keterangan |
|--------|----------|-------|------------|
| GET | `/api/wilayah` | All | List wilayah (filter: `?level=desa&parent_id=...`) |
| GET | `/api/wilayah/{id}` | All | Detail + hierarki wilayah |

### User Management
| Method | Endpoint | Roles | Keterangan |
|--------|----------|-------|------------|
| GET | `/api/users` | admin | List semua user |
| POST | `/api/users` | admin | Buat user baru |
| PATCH | `/api/users/{id}/toggle-active` | admin | Aktifkan/nonaktifkan akun |

---

## Alur Validasi

```
Aparatur Desa → POST /data-pertanian (is_draft=false)
               ↓ Observer dispatch job
Bhabinkamtibmas ← Email notifikasi
               ↓
Bhabinkamtibmas → POST /validasi/{id} { keputusan: "disetujui"|"ditolak" }
               ↓ Observer update status + kirim email ke aparatur
Aparatur Desa ← Email notifikasi hasil
               ↓
Dashboard ← Cache invalidated, stats updated real-time
```

---

## Keamanan

- **JWT-style via Sanctum**: token 8 jam, single-use per session
- **RBAC**: `CheckRole` middleware, setiap endpoint restricted per role
- **Rate limiting**: 10 login attempts/5 menit per IP
- **Audit log**: immutable append-only, tidak bisa dihapus/diedit siapapun
- **Anomali detection**: auto-flag data > 30% deviasi dari historis
- **Soft deletes**: data tidak benar-benar dihapus dari DB

---

## Struktur File

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── DataPertanianController.php
│   │   ├── ValidasiController.php
│   │   ├── DashboardController.php
│   │   └── LaporanController.php
│   ├── Middleware/CheckRole.php        ← RBAC
│   ├── Requests/
│   │   ├── StoreDataPertanianRequest.php
│   │   └── ValidasiRequest.php
│   └── Resources/
│       ├── DataPertanianResource.php
│       └── UserResource.php
├── Models/
│   ├── User.php, DataPertanian.php
│   ├── Validasi.php, Wilayah.php, AuditLog.php
├── Observers/ (auto-trigger jobs on model events)
├── Policies/DataPertanianPolicy.php
├── Jobs/ (queued: SendValidasiNotification, SendAnomalyAlert)
└── Mail/ (DataDiajukan, DataDisetujui, DataDitolak, AnomalyAlert)
```
