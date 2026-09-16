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
4. **Persetujuan petugas**: Petugas memeriksa kelengkapan dan menyetujui atau menolak permintaan
5. Setelah disetujui, **petugas berwenang mengupload file hasil olahan data** secara manual ke portal (bukan proses otomatis dari database, karena belum ada integrasi data terintegrasi)
6. Sistem mengirim **notifikasi email** ke pemohon bahwa data sudah siap
7. Pemohon login/verifikasi lalu mengunduh file dari portal
8. Jika ditolak pada tahap manapun, pemohon menerima notifikasi email dengan alasan penolakan

### 4.3 Tracking Status
- Pemohon dapat mengecek status permintaan menggunakan nomor tiket + nomor HP
- Status: Diajukan → Disetujui Petugas → Data Siap Diunduh → Selesai / Ditolak

### 4.4 Akun Pemohon Publik (Tambahan 19 Agustus 2026)

1. Pemohon baru memilih **Daftar**, mengisi profil dan nomor HP, lalu memverifikasi OTP yang dikirim melalui SMS.
2. Setelah OTP valid, data pemohon dibuat dan sesi login publik diaktifkan.
3. Pemohon yang sudah terdaftar memilih **Masuk**, memasukkan nomor HP, lalu memverifikasi OTP SMS tanpa password.
4. Pemohon dapat **Keluar** untuk mengakhiri sesi publik.
5. Setelah masuk, pemohon dapat mengajukan permintaan dan mengunduh hasil miliknya tanpa mengisi ulang profil.
6. Halaman **Permintaan Saya** menampilkan riwayat pengajuan milik akun yang sedang aktif dan akses unduh ketika hasil tersedia.

### 4.5 Permintaan Informasi Tambahan (Tambahan 25 Agustus 2026)

1. Pada tahap Staf, Kasi, atau Kabid, approver dapat memilih **Minta Info Tambahan** dan wajib menulis pertanyaan yang dapat dilihat pemohon.
2. Status berubah menjadi **Menunggu Info Pemohon** dan seluruh aksi approval ditutup sementara sampai jawaban diterima.
3. Pemohon pemilik tiket menjawab dari halaman detail **Permintaan Saya** setelah masuk; akun lain tidak dapat mengirim atau mengubah jawaban.
4. Setelah dijawab, tiket kembali ke tahap Petugas yaitu `diajukan`.
5. Setiap putaran pertanyaan dan jawaban disimpan sebagai riwayat yang tidak menimpa approval sebelumnya.
6. Catatan internal petugas disimpan terpisah dan tidak ditampilkan pada halaman atau email pemohon.
7. Versi awal menerima jawaban teks. Lampiran pemohon ditunda sampai kebijakan tipe file, pemindaian malware, dan retensi dokumen ditetapkan.
8. Setelah pemohon menjawab, petugas yang meminta informasi menerima notifikasi email tanpa memuat isi jawaban; tiket juga naik ke urutan teratas daftar berdasarkan aktivitas terbaru.

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
- Persetujuan petugas dengan role & permission
- Upload file hasil oleh petugas setelah disetujui
- Riwayat/log setiap perubahan status (audit trail)

### 5.3 Modul Notifikasi
- Notifikasi email pada setiap perubahan status penting (disetujui, ditolak, data siap)
- OTP nomor HP dikirim melalui SMS menggunakan Verihubs SMS OTP V2; keputusan WhatsApp awal tetap dicatat sebagai riwayat pada bagian 9.1

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
- Akun pemohon menggunakan autentikasi passwordless melalui OTP SMS; password hanya digunakan oleh pengguna internal.
- Pendaftaran dengan nomor yang sudah tercatat memakai kembali identitas yang sama dan tidak membuat duplikat.
- Sesi pemohon harus dirotasi setelah verifikasi berhasil dan saat Keluar.

## 6. Kebutuhan Non-Fungsional

- **Hosting**: Shared hosting cPanel (DomaiNesia) — perlu dipastikan tersedia akses SSH/Composer atau alternatif deploy manual
- **Skalabilitas**: dirancang untuk beban BPS kabupaten (bukan skala nasional), sehingga shared hosting mencukupi untuk awal
- **Keamanan**: file data khusus/mikro tidak boleh dapat diakses langsung via URL publik tanpa melalui proses autentikasi/otorisasi
- **Reliabilitas notifikasi**: pastikan pengiriman email tidak masuk folder spam (perlu konfigurasi SPF/DKIM di domain)
- **Zona waktu layanan (tambahan 20 Agustus 2026)**: seluruh proses bisnis, nomor tiket, scheduler, dan tampilan waktu menggunakan `Asia/Jakarta` (WIB)
- **Integritas pengajuan (tambahan 20 Agustus 2026)**: pembuatan tiket wajib tahan terhadap klik/kirim ulang melalui token idempotensi unik, session lock, serta rate limit terpisah per pemohon dan alamat IP

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
- `permintaan_data.idempotensi_hash` (tambahan 20 Agustus 2026) — digest token unik untuk menjamin satu pengiriman ulang tidak membuat tiket kedua; baris lama boleh bernilai kosong
- `permintaan_approval_log` — riwayat approval per tahap (staf/kasi/kabid, keputusan, catatan, timestamp)
- `permintaan_klarifikasi` (tambahan 25 Agustus 2026) — riwayat pertanyaan petugas, catatan internal, jawaban pemohon, tahap asal, peminta, dan waktu jawaban
- `notifikasi_log` — riwayat notifikasi email yang terkirim

## 9. Hal yang Masih Perlu Diputuskan

- ~~Provider SMS/WA gateway untuk OTP~~ **Diputuskan 24 Agustus 2026:** Verihubs SMS OTP V2; aktivasi akun, Sender ID, dan biaya operasional masih perlu diselesaikan
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
- Pendaftaran dan masuk dipisahkan secara visual, tetapi berbagi mesin OTP nomor HP, rate limit, dan normalisasi nomor yang sama.
- Keberadaan akun tidak diperiksa atau diungkap sebelum OTP valid. Nomor baru dari alur Masuk melengkapi profil setelah verifikasi tanpa OTP kedua.
- Kepemilikan permintaan dan hasil unduhan ditentukan dari `pemohon_id` sesi yang tervalidasi, bukan dari nomor HP yang dikirim ulang oleh browser.
- Logout pemohon memakai metode `POST`, membersihkan state OTP, dan merotasi session tanpa mengakhiri sesi petugas internal.

### 9.3 Keputusan Navigasi Autentikasi Publik (20 Agustus 2026)

- Navbar guest menampilkan satu kontrol **Masuk** yang membuka pilihan **Masuk sebagai Pemohon** dan **Masuk sebagai Petugas** agar navigasi tetap ringkas.
- **Daftar Pemohon** tetap menjadi CTA terpisah; tidak tersedia pendaftaran publik untuk akun petugas.
- Penggabungan hanya berlaku pada pintu navigasi. Form, route, metode autentikasi, dan guard tetap terpisah: pemohon memakai OTP SMS, sedangkan petugas memakai email dan password.

### 9.4 Perubahan Kanal OTP ke SMS (24 Agustus 2026)

- Keputusan ini **menggantikan kanal operasional awal pada bagian 9.1**: OTP pemohon dikirim melalui **Verihubs SMS OTP V2**, bukan Meta WhatsApp Cloud API.
- Nomor HP tetap menjadi identitas akun publik dan disimpan dalam format kanonis `628...`; format input `08...`, `62...`, serta `+62...` tetap diterima.
- Aplikasi tetap membuat kode acak 6 digit, hanya menyimpan hash, membatasi masa berlaku maksimal 5 menit dan percobaan verifikasi maksimal 5 kali, serta menerapkan rate limit dan lock pengiriman yang sudah ada.
- Integrasi Verihubs memakai kode yang dibuat aplikasi melalui parameter `otp`, template SMS bermerek SIPERMINDA, dan nomor tujuan berformat internasional tanpa tanda tambah.
- Verifikasi hash OTP tetap dilakukan oleh aplikasi sebagai sumber kebenaran autentikasi. Karena endpoint Verify OTP Verihubs tidak dipanggil, status transaksi provider dapat terlihat **Not Verified** meskipun login lokal berhasil.
- Driver `log` hanya boleh digunakan pada development/testing. Production wajib memakai `OTP_DRIVER=sms` dan gagal tertutup jika konfigurasi Verihubs belum lengkap.
- Driver WhatsApp dipertahankan sementara sebagai rollback teknis, tetapi tidak menjadi kanal utama dan tidak boleh menjadi fallback otomatis karena dapat menggandakan pesan berbayar.
- Pengiriman SMS tidak dicoba ulang secara otomatis saat timeout karena hasil pengiriman dapat ambigu. Delivery callback ditunda ke fase operasional berikutnya.
- Aktivasi akun Verihubs, pengajuan Sender ID, sandbox, tarif, dan kredensial production wajib diselesaikan sebelum go-live; rahasia hanya disimpan pada environment production.

### 9.5 Fonnte untuk Demo Terbatas (25 Agustus 2026)

- Fonnte ditambahkan sebagai **kanal sementara khusus demo lokal/testing** agar alur OTP WhatsApp dapat diuji selama aktivasi Verihubs dan verifikasi bisnis Meta belum selesai.
- Keputusan ini tidak menggantikan bagian 9.1 maupun 9.4. Kanal production BPS tetap Verihubs SMS OTP V2 setelah persyaratan operasionalnya terpenuhi; adapter Meta tetap hanya menjadi riwayat dan opsi rollback terkontrol.
- Fonnte mengandalkan sesi perangkat WhatsApp yang ditautkan melalui QR. Ketergantungan pada sesi perangkat dan layanan pihak ketiga tersebut belum memenuhi dasar reliabilitas, kepatuhan, serta tata kelola untuk layanan resmi BPS.
- `OTP_DRIVER=fonnte` hanya boleh diaktifkan ketika `APP_ENV=local` atau `testing`. Environment lainnya, khususnya production, wajib menolak driver ini dan gagal tertutup; tidak boleh ada fallback otomatis ke Fonnte saat SMS atau Meta gagal.
- Demo wajib menggunakan nomor WhatsApp khusus yang telah diizinkan, bukan nomor pribadi petugas. Token hanya disimpan di environment, tidak boleh masuk Git, chat, screenshot, log, atau dokumentasi, dan harus dirotasi apabila terpapar.
- Aplikasi tetap menjadi sumber kebenaran autentikasi: kode dibuat acak, disimpan sebagai hash, berlaku maksimal 5 menit, dibatasi maksimal 5 percobaan, serta dikonsumsi secara atomik. Respons sukses Fonnte hanya berarti permintaan diterima API, bukan jaminan pesan sudah sampai.

### 9.6 Penyederhanaan Navigasi Pendaftaran Pemohon (26 Agustus 2026)

- Keputusan ini memperbarui bagian 9.3: navbar publik untuk pengguna yang belum masuk hanya menampilkan satu kontrol **Masuk** dengan pilihan Pemohon dan Petugas; CTA **Daftar Pemohon** tidak lagi ditampilkan terpisah di navbar desktop maupun seluler.
- Jalur pendaftaran publik tetap tersedia dari halaman **Masuk Pemohon**. Route, form, verifikasi OTP, dan ketentuan bahwa akun petugas tidak dapat didaftarkan secara publik tidak berubah.
- Keterangan kanal pada menu Masuk menggunakan istilah netral **kode OTP** agar tetap benar saat driver lokal, Fonnte demo, Meta rollback, atau SMS production digunakan.

### Upload dokumen untuk Vercel (13 September 2026)

- Upload S3 sampai 50 MB memakai POST policy privat, token sekali pakai yang terikat petugas/aksi/record, serta pemeriksaan ukuran/MIME sebelum finalisasi. Alur lokal dipertahankan.
- Unduhan S3 memakai URL sementara setelah otorisasi. Setup bucket/CORS/lifecycle dijelaskan singkat pada docs/UPLOAD_S3.md; pengujian bucket dan Vercel nyata tetap diperlukan sebelum rilis.

### Cloudflare R2 untuk dokumen (14 September 2026)

- Target object storage berubah menjadi Cloudflare R2 melalui API kompatibel S3, region auto dan endpoint akun R2. Presigned PUT 5 menit menggantikan POST policy; header ACL tidak dikirim.
- Token finalisasi 15 menit, otorisasi, batas final 50 MB, pemeriksaan MIME/ukuran, staging, copy bersyarat ETag, serta signed download tetap dipertahankan. Alur upload lokal tidak berubah.
- CORS bucket menggunakan PUT dengan origin aplikasi yang tepat. PUT tidak membatasi ukuran melalui policy; object staging yang melampaui batas ditolak sebelum finalisasi dan dibersihkan lifecycle bucket.

### Target Preview Supabase dan Neon (14 September 2026)

- Menggantikan target R2 untuk Preview: pengguna sudah menyiapkan bucket Supabase Storage dan Neon Preview. Presigned PUT tetap memakai driver S3, endpoint Supabase lengkap, region project, dan path-style; otorisasi, validasi finalisasi, serta alur lokal dipertahankan.
- Pengguna mengonfirmasi pesan contoh dari dashboard Meta WhatsApp berhasil. Keberhasilan OTP dari aplikasi, webhook, dan log pada database Preview masih perlu diverifikasi secara terpisah.
- Credential diisi privat pada `.env.preview` yang diabaikan Git. Koneksi layanan nyata, copy/finalisasi Supabase, dan pembersihan staging belum dinyatakan lulus. Migration, push, dan deploy menunggu persetujuan pengguna.

### Identitas Visual BPS Kabupaten Padang Lawas (16 September 2026)

- Navigasi publik dan internal menggunakan palet biru BPS, dengan aksen biru terang, hijau, dan oranye seperlunya. Logo BPS ditampilkan sebagai aset SVG lokal agar tetap tersedia saat deployment.

### Penguatan Layanan Permintaan Data (16 September 2026)

- Status pemohon menampilkan timeline dari pengajuan, klarifikasi, approval, data siap, hingga selesai. Catatan internal tidak ditampilkan pada halaman status publik.
- Dashboard petugas menandai permintaan aktif yang melewati target configurable satu atau dua hari kerja. Laporan mendukung filter status, kategori, rentang tanggal, dan ekspor CSV.
- Notifikasi status WhatsApp memakai template Meta terpisah dengan dua parameter body: nomor tiket dan status layanan. Pengiriman bersifat best-effort sehingga kegagalan provider tidak membatalkan perubahan status.
- Kebijakan Privasi dan Ketentuan Layanan tersedia dari footer publik.
