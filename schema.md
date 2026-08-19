# Schema — Sistem Permintaan Data BPS Kabupaten Padang Lawas

Skema database detail (MySQL/MariaDB), mengacu pada garis besar di PRD bagian 8.

## 1. Tabel `users` (pengguna internal: staf, kasi, kabid, admin)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| name | VARCHAR(255) | |
| email | VARCHAR(255), unique | |
| password | VARCHAR(255) | hashed |
| role_id | BIGINT, FK → roles.id | |
| is_active | BOOLEAN | default true |
| created_at, updated_at | TIMESTAMP | |

## 2. Tabel `roles` & `permissions` (via Spatie Laravel Permission)

- `roles`: id, name (staf, kasi, kabid, admin)
- `permissions`: id, name (misal: `approve-level-1`, `approve-level-2`, `upload-dataset`, `manage-users`)
- `role_has_permissions`: role_id, permission_id (pivot)
- `model_has_roles`: model_id, model_type, role_id (pivot ke users)

## 3. Tabel `pemohon` (pemohon publik/instansi)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| nama | VARCHAR(255) | |
| no_hp | VARCHAR(20), unique | terverifikasi via OTP |
| email | VARCHAR(255), nullable | untuk notifikasi |
| jenis_pemohon | ENUM('publik','instansi') | |
| nama_instansi | VARCHAR(255), nullable | diisi jika jenis_pemohon = instansi |
| no_hp_verified_at | TIMESTAMP, nullable | |
| created_at, updated_at | TIMESTAMP | |

## 4. Tabel `otp_verifications`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| no_hp | VARCHAR(20) | |
| kode_otp | VARCHAR(255) | hash bcrypt; kode asli tidak disimpan |
| expired_at | TIMESTAMP | |
| verified_at | TIMESTAMP, nullable | |
| attempt_count | TINYINT | untuk rate limiting |
| created_at | TIMESTAMP | |

Indeks `otp_active_lookup_index` pada `(no_hp, verified_at, created_at)` mempercepat pencarian OTP aktif. Record yang telah kedaluwarsa melewati masa retensi (default 7 hari) dihapus oleh scheduler.

## 5. Tabel `kategori_data` (untuk katalog & permintaan)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| nama | VARCHAR(255) | misal: Sosial, Ekonomi, Kependudukan |
| deskripsi | TEXT, nullable | |

## 6. Tabel `dataset_terbuka` (katalog data terbuka)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| judul | VARCHAR(255) | |
| deskripsi | TEXT, nullable | |
| kategori_id | BIGINT, FK → kategori_data.id | |
| periode | VARCHAR(50) | misal "2025", "Triwulan I 2025" |
| file_path | VARCHAR(255) | lokasi file di storage |
| ukuran_file | BIGINT | dalam bytes |
| versi | INT | default 1 |
| dataset_induk_id | BIGINT, FK → dataset_terbuka.id, nullable | mengacu ke versi sebelumnya jika revisi |
| status | ENUM('aktif','digantikan') | |
| uploaded_by | BIGINT, FK → users.id | |
| published_at | TIMESTAMP | |
| created_at, updated_at | TIMESTAMP | |

## 7. Tabel `unduhan_log`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| dataset_terbuka_id | BIGINT, FK, nullable | jika unduhan dari katalog terbuka |
| permintaan_data_id | BIGINT, FK, nullable | jika unduhan dari hasil permintaan khusus |
| pemohon_id | BIGINT, FK → pemohon.id, nullable | null jika anonim (data terbuka tanpa login) |
| ip_address | VARCHAR(45), nullable | |
| downloaded_at | TIMESTAMP | |

## 8. Tabel `permintaan_data` (permintaan data khusus)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| nomor_tiket | VARCHAR(30), unique | auto-generated |
| pemohon_id | BIGINT, FK → pemohon.id | |
| kategori_id | BIGINT, FK → kategori_data.id, nullable | |
| jenis_data | VARCHAR(255) | misal "KCDA 2025" |
| tujuan_penggunaan | TEXT | |
| periode_data | VARCHAR(50), nullable | |
| status | ENUM('diajukan','diverifikasi_staf','disetujui_kasi','disetujui_kabid','ditolak','data_siap','selesai') | |
| file_hasil_path | VARCHAR(255), nullable | diisi setelah petugas upload |
| uploaded_by | BIGINT, FK → users.id, nullable | petugas yang upload hasil |
| created_at, updated_at | TIMESTAMP | |

## 9. Tabel `permintaan_approval_log`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| permintaan_data_id | BIGINT, FK → permintaan_data.id | |
| tahap | ENUM('staf','kasi','kabid') | |
| approver_id | BIGINT, FK → users.id | |
| keputusan | ENUM('setuju','tolak','minta_info') | |
| catatan | TEXT, nullable | wajib diisi jika tolak |
| created_at | TIMESTAMP | |

## 10. Tabel `notifikasi_log`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT, PK | |
| permintaan_data_id | BIGINT, FK, nullable | |
| tujuan_email | VARCHAR(255) | |
| jenis_notifikasi | VARCHAR(100) | misal "permintaan_diterima", "data_siap", "ditolak" |
| status_kirim | ENUM('berhasil','gagal') | |
| sent_at | TIMESTAMP, nullable | |
| created_at | TIMESTAMP | |

## 11. Relasi Antar Tabel (Ringkasan)

```
users ──< permintaan_approval_log
users ──< dataset_terbuka (uploaded_by)
users ──< permintaan_data (uploaded_by, hasil)

pemohon ──< permintaan_data
pemohon ──< unduhan_log

kategori_data ──< dataset_terbuka
kategori_data ──< permintaan_data

dataset_terbuka ──< unduhan_log
dataset_terbuka ──self-reference (dataset_induk_id, untuk versioning)

permintaan_data ──< permintaan_approval_log
permintaan_data ──< unduhan_log
permintaan_data ──< notifikasi_log
```

## 12. Indeks yang Disarankan

- `pemohon.no_hp` — unique index (dipakai untuk login/tracking)
- `permintaan_data.nomor_tiket` — unique index (dipakai untuk tracking status)
- `permintaan_data.status` — index (untuk filter dashboard/daftar permintaan)
- `dataset_terbuka.kategori_id`, `dataset_terbuka.status` — index (untuk filter katalog)
