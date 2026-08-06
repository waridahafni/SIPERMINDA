# Architecture — Sistem Permintaan Data BPS Kabupaten Padang Lawas

## 1. Gambaran Umum

Sistem dibangun sebagai aplikasi monolitik berbasis **Laravel (PHP)**, dirancang untuk berjalan di **shared hosting cPanel (DomaiNesia)** tanpa memerlukan infrastruktur server terpisah, container, atau microservices.

## 2. Diagram Komponen (Level Tinggi)

```
┌─────────────────────────────────────────────────────────┐
│                     Pengguna (Browser)                    │
│        Pemohon Publik/Instansi   |   Staf/Kasi/Kabid       │
└───────────────────────┬─────────────────────────────────┘
                         │ HTTPS
┌───────────────────────▼─────────────────────────────────┐
│              Web Server (Apache - cPanel)                 │
├─────────────────────────────────────────────────────────┤
│                  Aplikasi Laravel (PHP)                   │
│  ┌───────────────┬───────────────┬──────────────────┐    │
│  │  Modul Katalog│ Modul         │ Modul Notifikasi  │    │
│  │  Data Terbuka │ Permintaan &  │ (Mail)            │    │
│  │               │ Approval      │                   │    │
│  ├───────────────┼───────────────┼──────────────────┤    │
│  │  Modul Auth   │ Modul         │ Modul             │    │
│  │  (OTP HP /    │ Dashboard &   │ Manajemen User    │    │
│  │  Internal)    │ Laporan       │ & Role            │    │
│  └───────────────┴───────────────┴──────────────────┘    │
└─────────┬──────────────────┬──────────────────┬──────────┘
          │                  │                  │
┌─────────▼──────┐  ┌────────▼────────┐  ┌──────▼─────────┐
│  MySQL/MariaDB │  │  File Storage   │  │  SMTP (Email)  │
│  (Database)    │  │  (server lokal, │  │  Provider      │
│                │  │  luar public)   │  │                │
└────────────────┘  └─────────────────┘  └────────────────┘
                              │
                     ┌────────▼────────┐
                     │  SMS/WA Gateway │
                     │  (OTP pihak ke-3)│
                     └─────────────────┘
```

## 3. Lapisan Aplikasi (Application Layers)

- **Presentation layer**: Blade templates (server-rendered) — dipilih karena kompatibel penuh dengan shared hosting tanpa perlu build step SPA (React/Vue butuh Node.js build, tidak selalu tersedia di shared hosting)
- **Application layer**: Controllers, Form Requests (validasi), Policies (otorisasi berbasis role)
- **Domain/business logic**: Services untuk alur approval berjenjang, klasifikasi data, dan notifikasi
- **Data layer**: Eloquent ORM ke MySQL/MariaDB

## 4. Alur Deployment

1. **Development** — lokal menggunakan Laragon (Apache/PHP/MySQL setara environment cPanel)
2. **Build** — `composer install --optimize-autoloader --no-dev` dijalankan lokal (karena kemungkinan tidak ada akses Composer/SSH di shared hosting), hasil `vendor/` diikutsertakan saat upload
3. **Deploy** — upload seluruh project via File Manager/FTP cPanel; folder `public/` diarahkan sebagai document root
4. **Database** — export `.sql` dari lokal, import ke database cPanel via phpMyAdmin
5. **Konfigurasi** — `.env` disesuaikan (DB credentials, SMTP, APP_URL, APP_KEY)

## 5. Integrasi Eksternal

| Integrasi | Tujuan | Catatan |
|---|---|---|
| SMTP (email) | Notifikasi status permintaan | Gunakan SMTP yang disediakan cPanel/domain BPS, atau layanan pihak ketiga (misal SendGrid/Mailgun) untuk deliverability lebih baik |
| SMS/WA Gateway | OTP verifikasi nomor HP pemohon | Perlu dipilih provider (ada biaya per pesan) |

## 6. Keamanan

- File dataset khusus/mikro disimpan **di luar folder `public/`**, diakses hanya lewat controller yang memverifikasi otorisasi pemohon (signed URL / temporary link)
- Password internal (staf/kasi/kabid/admin) di-hash menggunakan bcrypt (default Laravel)
- OTP nomor HP memiliki masa berlaku singkat (misal 5 menit) dan rate limit percobaan
- HTTPS wajib diaktifkan (SSL gratis biasanya tersedia di cPanel via AutoSSL)
- Validasi upload file (tipe file, ukuran maksimum) untuk mencegah upload berbahaya

## 7. Keterbatasan & Pertimbangan Shared Hosting

- Tidak ada queue worker background berjalan terus-menerus (tidak seperti VPS) — proses seperti pengiriman email dijalankan secara sinkron atau memanfaatkan cron job cPanel untuk menjalankan `schedule:run` Laravel secara berkala
- Cron job cPanel digunakan untuk tugas terjadwal (misal reminder permintaan yang lama belum diproses)
- Kapasitas storage & bandwidth terbatas sesuai paket hosting — perlu dipantau terutama jika ukuran dataset besar

## 8. Arsitektur 13-Layer

Beberapa layer di bawah tidak sepenuhnya tersedia di shared hosting cPanel (misal cloud compute, load balancer, auto-scaling). Untuk layer tersebut, tabel mencantumkan **kondisi saat ini** (sesuai shared hosting DomaiNesia) dan **jalur upgrade** jika ke depan BPS Padang Lawas pindah ke VPS/cloud.

### 8.1 Frontend
- Server-rendered **Blade templates** (Laravel) — tanpa build step Node.js agar tidak bergantung pada SSH/Composer di hosting
- Styling: Tailwind CSS via CDN (bukan build lokal) atau CSS custom ringan
- Interaktivitas: **Alpine.js** (ringan, tanpa build step) untuk komponen dinamis (OTP form, filter katalog, stepper status)

### 8.2 Layer API/Backend Logic
- Laravel Controllers + Form Requests (validasi) + Service classes (logika approval, klasifikasi data)
- Endpoint saat ini melayani halaman web (Blade), bukan REST API murni
- **Rekomendasi**: strukturkan Service layer agar mudah diekspos sebagai REST API di kemudian hari (misal jika nanti dibutuhkan aplikasi mobile)

### 8.3 Database Storage
- MySQL/MariaDB (detail skema di `schema.md`)
- Backup: `mysqldump` terjadwal via cron job cPanel, disimpan di storage terpisah (atau diunduh manual berkala)

### 8.4 Authentication & Authorization
- **Pemohon publik/instansi**: OTP nomor HP (tanpa password)
- **Internal (staf/kasi/kabid/admin)**: email + password (Laravel default auth, bcrypt)
- **Otorisasi**: Laravel Policies + `spatie/laravel-permission` berbasis role (lihat 8.8)

### 8.5 Hosting & Deployment
- Shared hosting cPanel (DomaiNesia)
- Deploy manual: build lokal (Laragon) → upload via File Manager/FTP → import database via phpMyAdmin
- Tidak ada zero-downtime deployment otomatis di shared hosting — deploy dilakukan di luar jam sibuk

### 8.6 Cloud Compute
- **Kondisi saat ini**: tidak berlaku — shared hosting bukan cloud compute (tidak ada VM/container terisolasi, resource dibagi dengan tenant lain di server yang sama)
- **Jalur upgrade**: jika kebutuhan tumbuh (traffic tinggi, butuh queue worker background, butuh SSH penuh), migrasi ke **Cloud VPS** (DomaiNesia juga menyediakan ini) atau provider lain

### 8.7 CI/CD & Version Control
- **Version control**: Git (disarankan repo privat, misal GitHub) — wajib ada terlepas dari keterbatasan hosting
- **CI/CD**: shared hosting tanpa SSH membatasi otomatisasi deploy. Opsi realistis:
  - Manual: build lokal → upload manual (paling sederhana, cocok untuk tim kecil)
  - Semi-otomatis: GitHub Actions yang men-deploy via FTP (`SamKirkland/FTP-Deploy-Action` atau sejenis) setiap push ke branch `main`
- **Jalur upgrade**: dengan Cloud VPS + SSH, CI/CD penuh (GitHub Actions → SSH deploy) menjadi mungkin

### 8.8 Role Level
- Role: **Pemohon** (bukan role sistem, entitas terpisah), **Staf**, **Kasi**, **Kabid**, **Admin**
- Permission granular per role (lihat `rules.md` bagian 9 untuk detail kewenangan)
- Diimplementasikan dengan `spatie/laravel-permission`, dicek di level route middleware & Policy per aksi

### 8.9 Rate Limiting
- Laravel `throttle` middleware pada endpoint publik rawan abuse:
  - Request OTP: dibatasi (misal maksimal 3x per nomor HP per 10 menit)
  - Percobaan input kode OTP: dibatasi (misal 5x percobaan sebelum kode expired dipaksa)
  - Form pengajuan permintaan: rate limit per nomor HP/IP untuk mencegah spam pengajuan
- Rate limiting berbasis database/cache file (default Laravel), cukup untuk skala shared hosting

### 8.10 Cache & CDN
- **Cache aplikasi**: Laravel file cache atau database cache driver (Redis umumnya tidak tersedia di shared hosting standar)
- **Cache halaman katalog**: cache hasil query katalog data terbuka (jarang berubah) untuk mengurangi beban database
- **CDN**: shared hosting tidak menyediakan CDN bawaan, tapi bisa ditambahkan gratis via **Cloudflare** (proxy DNS) untuk mempercepat aset statis (CSS/JS/gambar) dan sekaligus menambah lapisan keamanan (DDoS protection dasar)

### 8.11 Load Balancer & Scaling
- **Kondisi saat ini**: tidak ada — shared hosting = single server, tidak ada load balancer, scaling terbatas pada upgrade paket hosting (vertical scaling)
- **Jalur upgrade**: jika trafik/beban meningkat signifikan, opsi bertahap:
  1. Upgrade ke Cloud VPS (masih single server, tapi resource dedicated)
  2. Multi-server + load balancer (baru relevan jika skala jauh lebih besar dari kebutuhan BPS kabupaten saat ini)

### 8.12 Error Tracking
- Baseline: Laravel log (`storage/logs/laravel.log`), dicek manual atau via cron alert sederhana
- **Rekomendasi**: integrasi **Sentry** (tersedia free tier, kompatibel dengan shared hosting karena hanya butuh package Composer + API key, tidak butuh server tambahan) untuk pelacakan error real-time dan notifikasi

### 8.13 Availability & Recovery
- **Backup**: kombinasi backup otomatis dari cPanel (biasanya tersedia di paket DomaiNesia) + `mysqldump` terjadwal via cron sebagai lapisan kedua
- **Monitoring uptime**: layanan gratis seperti UptimeRobot untuk memantau ketersediaan situs dan mendapat alert jika down
- **Recovery plan**: dokumentasikan langkah restore (database dari backup `.sql`, file dari backup cPanel) dan uji coba restore secara berkala, bukan hanya mengandalkan backup tanpa pernah diuji
- **Keterbatasan**: shared hosting umumnya tidak punya SLA uptime tinggi seperti cloud provider besar — perlu disadari sebagai risiko yang diterima (accepted risk) pada tahap awal sistem ini
