# PRD — Sistem Permintaan Data BPS Kabupaten Padang Lawas

## 1. Latar Belakang

BPS Kabupaten Padang Lawas membutuhkan sebuah sistem untuk mengelola permintaan data statistik, baik dari masyarakat umum maupun instansi pemerintah lain. Saat ini belum ada sistem terintegrasi, sehingga permintaan data masih ditangani secara manual.

BPS Padang Lawas belum memiliki server sendiri, namun berlangganan **shared hosting cPanel di DomaiNesia**, sehingga desain sistem harus menyesuaikan keterbatasan tersebut.

## 2. Tujuan

- Mempermudah masyarakat/instansi dalam mengajukan dan memperoleh data statistik dari BPS Padang Lawas
- Mempercepat akses data terbuka melalui katalog self-service
- Menyediakan alur approval yang terstruktur untuk data mikro/khusus yang memerlukan review berjenjang
- Memberikan visibilitas kinerja layanan permintaan data kepada manajemen BPS

## 3. Target Pengguna

| Peran | Deskripsi |
|---|---|
| Pemohon publik | Masyarakat umum yang ingin mengunduh/meminta data |
| Pemohon instansi | Instansi pemerintah/lembaga lain yang meminta data |
| Staf BPS | Petugas subject matter yang memproses permintaan & mengelola katalog data terbuka |
| Kasi (Kepala Seksi) | Approval level 1 untuk permintaan data khusus |
| Kabid (Kepala Bidang) | Approval level 2 (final) untuk permintaan data khusus |
| Admin sistem | Mengelola user, role, dan konfigurasi sistem |

## 4. Alur Utama (User Flow)

### 4.1 Jalur Data Terbuka (Self-Service)
1. Pemohon membuka katalog data di portal
2. Mencari/filter data berdasarkan kategori (sektor, periode, dsb)
3. Jika data tersedia → unduh langsung (tercatat untuk laporan, tanpa approval)
4. Jika data **tidak tersedia** → lanjut ke jalur permintaan khusus (lihat 4.2)

Upload data ke katalog data terbuka dilakukan langsung oleh staf/petugas subject matter **tanpa approval**.

### 4.2 Jalur Permintaan Data Khusus
1. Pemohon mengisi form permintaan (verifikasi nomor HP via OTP)
2. Pemohon mengisi detail: jenis data, tujuan penggunaan, periode data
3. Sistem mencatat permintaan dengan nomor tiket
4. **Approval berjenjang**: Staf (verifikasi kelengkapan) → Kasi → Kabid
5. Setelah disetujui, **petugas berwenang mengupload file hasil olahan data** secara manual ke portal (bukan proses otomatis dari database, karena belum ada integrasi data terintegrasi)
6. Sistem mengirim **notifikasi email** ke pemohon bahwa data sudah siap
7. Pemohon login/verifikasi lalu mengunduh file dari portal
8. Jika ditolak pada tahap manapun, pemohon menerima notifikasi email dengan alasan penolakan

### 4.3 Tracking Status
- Pemohon dapat mengecek status permintaan menggunakan nomor tiket + nomor HP
- Status: Diajukan → Diverifikasi Staf → Disetujui Kasi → Disetujui Kabid → Data Siap Diunduh → Selesai / Ditolak

### 4.4 Akun Pemohon Publik (Tambahan 19 Agustus 2026)

1. Pemohon baru memilih **Daftar**, mengisi profil dan nomor WhatsApp, lalu memverifikasi OTP.
2. Setelah OTP valid, data pemohon dibuat dan sesi login publik diaktifkan.
3. Pemohon yang sudah terdaftar memilih **Masuk**, memasukkan nomor WhatsApp, lalu memverifikasi OTP tanpa password.
4. Pemohon dapat **Keluar** untuk mengakhiri sesi publik.
5. Setelah masuk, pemohon dapat mengajukan permintaan dan mengunduh hasil miliknya tanpa mengisi ulang profil.
6. Halaman **Permintaan Saya** menampilkan riwayat pengajuan milik akun yang sedang aktif dan akses unduh ketika hasil tersedia.

## 5. Fitur & Modul

### 5.1 Modul Katalog Data Terbuka
- Upload & publish data (staf, tanpa approval)
- Kategorisasi (sektor: sosial, ekonomi, kependudukan, dll; per periode/tahun)
- Search & filter
- Unduhan langsung + pencatatan log unduhan
- Versioning sederhana (menandai data revisi dari data lama)

### 5.2 Modul Permintaan Data Khusus
- Form pengajuan dengan verifikasi OTP nomor HP
- Nomor tiket otomatis
- Alur approval berjenjang (staf → kasi → kabid) dengan role & permission
- Upload file hasil oleh petugas setelah disetujui
- Riwayat/log setiap perubahan status (audit trail)

### 5.3 Modul Notifikasi
- Notifikasi email pada setiap perubahan status penting (disetujui, ditolak, data siap)
- OTP nomor HP dikirim melalui Meta WhatsApp Cloud API menggunakan template kategori `AUTHENTICATION` yang telah disetujui

### 5.4 Modul Dashboard & Laporan
- Jumlah permintaan (per periode, per jenis data, per status)
- Tren permintaan dari waktu ke waktu
- Statistik kinerja layanan (rata-rata waktu proses per tahap)
- Data yang paling banyak diminta/diunduh

### 5.5 Modul Autentikasi & Manajemen User
- Autentikasi pemohon via nomor HP (OTP)
- Autentikasi internal (staf/kasi/kabid/admin) via email/password
- Role & permission (staf, kasi, kabid, admin)
- Halaman Daftar dan Masuk pemohon dibuat terpisah agar alur pengguna luar jelas.
- Akun pemohon menggunakan autentikasi passwordless melalui OTP WhatsApp; password hanya digunakan oleh pengguna internal.
- Pendaftaran dengan nomor yang sudah tercatat memakai kembali identitas yang sama dan tidak membuat duplikat.
- Sesi pemohon harus dirotasi setelah verifikasi berhasil dan saat Keluar.

## 6. Kebutuhan Non-Fungsional

- **Hosting**: Shared hosting cPanel (DomaiNesia) — perlu dipastikan tersedia akses SSH/Composer atau alternatif deploy manual
- **Skalabilitas**: dirancang untuk beban BPS kabupaten (bukan skala nasional), sehingga shared hosting mencukupi untuk awal
- **Keamanan**: file data khusus/mikro tidak boleh dapat diakses langsung via URL publik tanpa melalui proses autentikasi/otorisasi
- **Reliabilitas notifikasi**: pastikan pengiriman email tidak masuk folder spam (perlu konfigurasi SPF/DKIM di domain)

## 7. Tech Stack yang Direkomendasikan

| Komponen | Pilihan |
|---|---|
| Framework | Laravel (PHP) |
| Database | MySQL/MariaDB (tersedia default di cPanel) |
| Role & Permission | Paket `spatie/laravel-permission` |
| Local development | Laragon (Windows) |
| Hosting produksi | Shared hosting cPanel DomaiNesia |
| Notifikasi | Laravel Mail (SMTP) |
| Autentikasi pemohon | OTP nomor HP (perlu SMS/WA gateway pihak ketiga) |

**Catatan**: Perlu dicek terlebih dahulu apakah paket hosting DomaiNesia mendukung akses SSH & Composer. Jika tidak tersedia, deployment Laravel dilakukan dengan build folder `vendor/` secara lokal lalu diunggah manual via File Manager/FTP cPanel.

**Keputusan tambahan 19 Agustus 2026**: kebutuhan provider pada tabel di atas dipenuhi dengan Meta WhatsApp Cloud API resmi; rincian keputusan dicatat pada bagian 9.1.

## 8. Skema Database (Garis Besar)

- `users` — staf/kasi/kabid/admin (autentikasi internal)
- `pemohon` — data pemohon publik/instansi (nomor HP, nama, email opsional)
- `roles`, `permissions`, `role_has_permissions` (dari Spatie package)
- `dataset_terbuka` — katalog data terbuka (judul, kategori, periode, file, uploader, tanggal publish)
- `unduhan_log` — catatan siapa mengunduh dataset apa dan kapan
- `permintaan_data` — permintaan data khusus (nomor tiket, pemohon_id, jenis data, tujuan, status, timestamps tiap tahap)
- `permintaan_approval_log` — riwayat approval per tahap (staf/kasi/kabid, keputusan, catatan, timestamp)
- `notifikasi_log` — riwayat notifikasi email yang terkirim

## 9. Hal yang Masih Perlu Diputuskan

- Provider SMS/WA gateway untuk OTP (mempengaruhi biaya operasional)
- Kepastian ketersediaan SSH/Composer di paket hosting DomaiNesia yang digunakan
- Kebijakan retensi file (berapa lama file hasil permintaan disimpan sebelum dihapus)
- Format/template surat pengantar untuk permintaan data khusus (apakah perlu upload dokumen pendukung)

### 9.1 Keputusan Provider OTP (19 Agustus 2026)

- Kanal awal OTP menggunakan **Meta WhatsApp Cloud API resmi**.
- Pesan memakai template kategori `AUTHENTICATION` dengan tombol salin kode, masa berlaku kode 5 menit, dan delivery TTL maksimal 300 detik.
- Driver log hanya digunakan untuk development lokal dan testing; production wajib gagal tertutup jika konfigurasi WhatsApp belum lengkap.
- Metadata OTP kedaluwarsa disimpan maksimal 7 hari secara default lalu dipangkas oleh scheduler Laravel.
- Respons API `2xx` diperlakukan sebagai pesan diterima Meta; pemantauan status delivery melalui webhook ditunda ke fase operasional berikutnya.
- SMS sebagai kanal fallback ditunda sampai ada kebutuhan operasional dan persetujuan biaya.

### 9.2 Keputusan Akun Pemohon Publik (19 Agustus 2026)

- Akun pemohon tetap memakai entitas `pemohon`; tabel `users` dan autentikasi email/password hanya untuk petugas internal.
- Pendaftaran dan masuk dipisahkan secara visual, tetapi berbagi OTP WhatsApp, rate limit, dan normalisasi nomor yang sama.
- Keberadaan akun tidak diperiksa atau diungkap sebelum OTP valid. Nomor baru dari alur Masuk melengkapi profil setelah verifikasi tanpa OTP kedua.
- Kepemilikan permintaan dan hasil unduhan ditentukan dari `pemohon_id` sesi yang tervalidasi, bukan dari nomor HP yang dikirim ulang oleh browser.
- Logout pemohon memakai metode `POST`, membersihkan state OTP, dan merotasi session tanpa mengakhiri sesi petugas internal.
