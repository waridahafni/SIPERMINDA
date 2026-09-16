# Verifikasi dan panduan rilis SIPERMINDA

Dokumen ini melanjutkan project existing per 12 September 2026. Push, deployment,
pengiriman WhatsApp nyata, dan perubahan database lokal belum dilakukan.

## Hasil inspeksi

| Komponen | Kondisi project |
|---|---|
| Framework | Laravel 13.23.0 pada composer.lock; requirement Laravel ^13.8 |
| PHP | Herd 8.4.24; PHP XAMPP 8.2.4 tidak memenuhi requirement ^8.3 |
| Register | GET /daftar, POST /daftar/kirim-otp, GET /otp, POST /otp/verifikasi |
| Login publik | POST /masuk/kirim-otp; passwordless OTP, middleware pemohon.otp |
| Login internal | Laravel session guard, email/password dan Spatie Permission |
| users | id, name, email unik, email_verified_at, password, is_active, remember_token, timestamps, role_id |
| pemohon | id, nama, no_hp unik, email, jenis_pemohon, nama_instansi, no_hp_verified_at, timestamps |
| OTP | otp_verifications menyimpan hash, expiry, verified_at, attempt_count |
| Database lokal | SQLite; tidak diganti atau dimigrasikan dalam verifikasi ini |
| Session/cache | Database; test memakai array serta SQLite in-memory |
| Dokumen lokal | storage/app/private, melalui controller yang memeriksa izin |
| Deployment | Docker/FrankenPHP PHP 8.4, Vercel Services; belum dirilis |

Profil pendaftaran disimpan sebagai state pending di session. Baris pemohon baru
dibuat setelah OTP benar. Pendekatan existing ini mencegah aktivasi akun sebelum
verifikasi dan mempertahankan pemisahan akun petugas/pemohon. Tidak diperlukan
kolom nomor telepon baru di users. Nomor existing tidak dibuat ulang dan profil
akun existing tidak ditimpa oleh formulir pendaftaran lain.

## Perubahan yang ditinjau

| Kelompok | File dan perilaku |
|---|---|
| OTP | OtpController, OtpService, VerifikasiOtpRequest, OtpVerification: hash, transaksi konsumsi, 5 percobaan, cooldown semua pintu kirim, resend setelah pengiriman gagal |
| Meta | PengirimOtpWhatsApp, WhatsAppClient, WhatsAppTemplate: endpoint tetap, bearer token environment, template authentication, tanpa retry otomatis |
| Status pesan | WhatsAppWebhookController, WhatsAppMessage, migration whatsapp_messages: signature HMAC, WABA/nomor pengirim, status tidak mundur, nomor log terenkripsi |
| HTTP | routes/api.php, bootstrap/app.php: webhook tanpa session/CSRF; OTP tidak masuk flashed input; trusted proxy memakai konfigurasi eksplisit |
| Storage | DokumenStorage, config/filesystems.php, empat controller katalog/permintaan: disk configurable dan streaming setelah otorisasi |
| Notifikasi | notifikasi_log email dipertahankan; error SMTP dicatat sebagai jenis exception, bukan teks respons provider |
| Pemangkasan | PruneMetadataController, routes/console.php, config/services.php: retensi OTP/WhatsApp, cron bearer secret dan lock |
| Environment | .env.example, .env.production.example, config/otp.php, config/trustedproxy.php |
| Build | Dockerfile.vercel, Caddyfile, vercel.json, .dockerignore, .gitignore, composer.json/lock; adapter S3 ditambahkan |
| Tampilan | Layout existing dipertahankan; keterangan OTP disesuaikan agar tidak mengklaim pesan terkirim saat provider gagal |
| Test | Test WhatsApp, storage production, normalisasi, autentikasi, rate limit, navigasi dan regresi alur permintaan |

Working tree juga memuat perubahan yang sudah ada sebelum pekerjaan WhatsApp/Vercel,
antara lain persetujuan petugas, informasi tambahan, navigasi, serta driver SMS/Fonnte.
Perubahan tersebut tidak dibatalkan. Angka total file pada git diff mencakup keduanya,
bukan hanya file integrasi ini. Lihat juga laporan hasil test di akhir dokumen.

Migration baru: `2026_09_12_000001_create_whatsapp_messages_table.php`.
Tidak ada migration baru untuk users/pemohon. Migration ini masih pending di lokal.

## Checklist environment

Gunakan `.env.production.example` sebagai daftar nama variable. Isi nilainya langsung
di Vercel Settings → Environment Variables. Preview dan Production harus memakai
database, bucket, kunci dan credential masing-masing. Jangan menyalin `.env` lokal
ke Git atau image Docker.

| Wajib diisi | Nilai/sumber |
|---|---|
| APP_KEY | Kunci unik dari `php artisan key:generate --show`; stabil antar-redeploy dalam environment yang sama |
| APP_URL | URL HTTPS final untuk environment tersebut |
| DB_URL **atau** DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD | Credential database eksternal; gunakan satu cara konfigurasi saja |
| WHATSAPP_ACCESS_TOKEN | Token Meta; sementara untuk pengujian, token operasional dengan izin minimum untuk production |
| WHATSAPP_PHONE_NUMBER_ID | ID nomor pengirim, bukan nomor telepon |
| WHATSAPP_WABA_ID | ID WhatsApp Business Account |
| WHATSAPP_API_VERSION | Versi Graph API aktif yang ditampilkan/didukung Meta, misalnya pola vNN.0; jangan memakai v99.0 dari test |
| WHATSAPP_WEBHOOK_VERIFY_TOKEN | Secret acak buatan Anda, sama dengan Verify Token pada dashboard Meta |
| WHATSAPP_APP_SECRET | App Secret Meta untuk memeriksa signature POST webhook; berbeda dari access token |
| WHATSAPP_OTP_TEMPLATE_NAME/LANGUAGE | Nama dan bahasa template AUTHENTICATION yang sudah disetujui |
| AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET | Credential bucket S3 privat; hak akses hanya bucket yang diperlukan |
| AWS_ENDPOINT | Isi untuk storage kompatibel S3; kosong/omit untuk AWS S3 standar |
| CRON_SECRET | Secret acak panjang untuk cron; jangan sama dengan secret webhook |
| MAIL_HOST, MAIL_PORT, MAIL_SCHEME, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS | SMTP operasional supaya notifikasi existing benar-benar terkirim |

Nilai operasional yang perlu dipertahankan:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
OTP_DRIVER=whatsapp
OTP_EXPIRES_MINUTES=5
OTP_RETENTION_DAYS=7
WHATSAPP_LOG_RETENTION_DAYS=30
WHATSAPP_TIMEOUT_SECONDS=10
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning
FILESYSTEM_DISK=s3
DOCUMENTS_DISK=s3
DB_CONNECTION=pgsql
DB_SSLMODE=require
TRUSTED_PROXIES=*
```

`TRUSTED_PROXIES=*` hanya untuk deployment dengan container di belakang edge
Vercel yang tepercaya, bukan server yang menerima request langsung dari internet.
Forwarded host tidak dipercaya; HTTPS dan IP klien mengikuti header proxy.
Jangan set SESSION_DOMAIN ke localhost. Gunakan cookie host-only (omit variable).
QUEUE_CONNECTION=sync sesuai notifikasi existing yang dijalankan dalam request;
ini bukan worker queue background. Jika kelak memakai queued jobs, perlu layanan
worker yang durable, bukan proses daemon di container Vercel.

INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL dan INITIAL_ADMIN_PASSWORD hanya diperlukan
saat bootstrap admin di database kosong. Password minimal 12 karakter, berisi huruf
besar, huruf kecil, angka. Hapus credential bootstrap setelah akun dibuat.

## Setup Meta testing secara manual

1. Buka Meta for Developers, pilih/buat aplikasi dengan WhatsApp, lalu buka API Setup.
2. Gunakan nomor pengirim testing yang disediakan Meta dan tambahkan/verifikasi nomor
   penerima milik Anda pada daftar penerima testing. Catat Phone Number ID dan WABA ID.
3. Kirim pesan contoh `hello_world` melalui dashboard Meta untuk memastikan provisioning
   nomor dan token berfungsi. Langkah ini hanya tes koneksi, bukan verifikasi OTP SIPERMINDA.
4. Siapkan template AUTHENTICATION `siperminda_kode_otp`, bahasa `id`, tombol COPY_CODE,
   expiry 5 menit. Pastikan template disetujui dan tersedia pada WABA yang dipakai.
   Jika akun testing belum menyediakan template authentication yang sesuai, pengujian
   OTP nyata menunggu kesiapan akun/template; jangan mengganti payload OTP dengan hello_world.
5. Isi WHATSAPP_* di environment lokal privat dan set OTP_DRIVER=whatsapp. Pertahankan
   APP_ENV=local serta database SQLite lokal. Jangan membagikan credential melalui chat.
6. Sebelum tes nyata, backup database lokal. Tinjau `php artisan migrate:status`, lalu
   jalankan hanya migration log baru bila Anda siap mengubah database lokal:

   ```powershell
   & 'C:\Users\warida hafni\.config\herd\bin\php84\php.exe' artisan migrate --path=database/migrations/2026_09_12_000001_create_whatsapp_messages_table.php
   & 'C:\Users\warida hafni\.config\herd\bin\php84\php.exe' artisan config:clear
   ```

7. Buka `/daftar`, isi data dan nomor penerima testing, lalu verifikasi kode yang benar-benar
   diterima di WhatsApp. Sebelum verifikasi, akses akun pemohon baru harus ditolak. Setelah
   berhasil, nomor terverifikasi dan riwayat permintaan dapat diakses. Keluar lalu uji `/masuk`.
8. Uji kode salah, tunggu lewat 5 menit untuk kedaluwarsa, resend setelah 60 detik, dan kode
   lama setelah resend. Uji token invalid: akun baru tidak aktif, pesan error aman, resend tersedia.
9. Periksa `whatsapp_messages`: accepted berarti Meta menerima request, bukan bukti delivery.
   OTP tidak berada di tabel ini; nomor terenkripsi. Jangan dump isi request/respons provider.

Referensi: [quickstart resmi Meta](https://whatsapp.github.io/WhatsApp-Nodejs-SDK/),
[API resmi Meta](https://www.postman.com/meta/whatsapp-business-platform/overview),
[template COPY_CODE](https://www.postman.com/meta/whatsapp-business-platform/request/6vkv46u/create-authentication-template-w-otp-copy-code-button).

## Setup webhook

1. Sediakan URL HTTPS yang dapat dijangkau Meta, melalui tunnel lokal tepercaya atau Preview
   khusus pengujian. Meta tidak dapat memanggil `localhost` atau domain `.test` lokal.
2. Daftarkan callback `https://DOMAIN/api/webhooks/whatsapp` dan Verify Token yang sama
   dengan WHATSAPP_WEBHOOK_VERIFY_TOKEN. GET mengembalikan challenge setelah token cocok.
3. Subscribe field `messages` pada WABA/aplikasi yang benar. Jika Preview memakai Deployment
   Protection, pastikan callback Meta dapat melewati proteksi sesuai konfigurasi Vercel.
4. Pastikan WHATSAPP_APP_SECRET berasal dari aplikasi yang sama. POST memerlukan
   X-Hub-Signature-256 yang benar atas raw body; Verify Token tidak menggantikan signature.
5. Kirim OTP testing, lihat status log berubah accepted → sent → delivered → read (jika event
   tersedia). Event failed menyimpan kode error aman. Webhook tidak mengaktifkan akun.
6. Signature salah ditolak 403. Event WABA/nomor pengirim lain diabaikan. Pesan masuk tidak
   diproses sebagai chatbot. Status duplikat tidak membuat log baru dan status lama tidak
   memundurkan delivered/read. ID pesan yang belum dikenal diabaikan; untuk operasi produksi
   tetap lakukan rekonsiliasi ketika callback lebih cepat dari penyimpanan ID atau request timeout.

Referensi [verifikasi webhook Meta](https://whatsapp.github.io/WhatsApp-Nodejs-SDK/api-reference/webhooks/start/).

## Database dan storage production

Pilihan pertama: PostgreSQL terkelola dengan TLS dan connection pooling, misalnya Neon
atau Supabase. MySQL terkelola juga didukung konfigurasi Laravel serta extension Docker;
gunakan DB_CONNECTION=mysql, port/provider TLS yang sesuai jika memilihnya. SQLite pada
container tidak digunakan sebagai database production.

Semua instance harus berbagi database session/cache. Pisahkan Preview dari Production.
Migrasi schema tidak memindahkan data SQLite secara otomatis. Jika data lokal perlu
dibawa, lakukan ekspor/impor terencana dengan backup dan pertahankan ID foreign key.
Jangan menjalankan migrate:fresh pada database berisi data. Tinjau status lama hasil
penyederhanaan approval sebelum impor agar tiket lama tidak berhenti di tahap yang tidak aktif.

Bucket harus privat. Aktifkan backup/versioning sesuai kebutuhan operasional. Storage S3
dipilih dengan DOCUMENTS_DISK=s3; FILESYSTEM_DISK saja tidak mengubah disk dokumen.
Jangan memakai `public/storage` atau filesystem container untuk dokumen permanen.
Sebelum mengganti disk pada lingkungan yang sudah memiliki file, salin seluruh object
dari storage/app/private dengan path relatif yang sama, cocokkan jumlah, ukuran dan
checksum, kemudian uji akses unduh. Jangan hapus sumber sebelum verifikasi selesai.

**Batas yang menghalangi go-live penuh:** form existing menerima file 50 MB melalui
Laravel. Vercel Functions membatasi payload 4,5 MB dan container mengikuti limits
Functions. Pengaturan PHP/Caddy 50/55 MB tidak mengubah batas platform tersebut.
Selesaikan direct upload ke bucket memakai URL bertanda tangan beserta verifikasi
ukuran/tipe, kepemilikan dan finalisasi server sebelum mempromosikan production.
Unduhan besar juga perlu diuji pada deployment; URL unduh sementara setelah otorisasi
adalah opsi untuk menghindari proxy payload besar. Implementasi direct upload belum
termasuk perubahan ini dan batas fitur lokal tidak diturunkan diam-diam.
Referensi: [limits Functions](https://vercel.com/docs/functions/limitations),
[limits container](https://vercel.com/docs/functions/container-images).

## GitHub → Vercel, setelah review dan persetujuan rilis

1. Tinjau perubahan di branch `feat/whatsapp-vercel`. Pilih file dengan teliti karena working
   tree juga memuat fitur sebelumnya. `.env`, cookie, log, SQLite, upload, vendor, node_modules
   dan cache tidak boleh masuk commit/image. Belum ada push pada pekerjaan ini.
2. Setelah review selesai, commit perubahan yang disetujui, push branch, lalu buat PR ke
   repository `Stackpilot02/SIPERMINDA`. Jangan menjadikan main sebagai target uji pertama.
3. Pada Vercel, import repository GitHub dengan root project `.`. Pertahankan Dockerfile.vercel,
   Caddyfile dan vercel.json. Periksa akses akun terhadap fitur Container Images/Services.
4. Siapkan database/bucket khusus Preview dan isi environment sebelum build. Jangan menaruh
   secret dalam Dockerfile/build arguments. Runtime membaca environment dari Vercel.
5. Jalankan build container lokal saat Docker tersedia:

   ```sh
   docker build -f Dockerfile.vercel -t siperminda-preview .
   docker run --rm --env-file .env.preview -p 8080:80 siperminda-preview
   ```

   `.env.preview` harus berisi credential khusus pengujian dan tetap diabaikan Git. Gunakan
   APP_URL=http://localhost:8080 dan SESSION_SECURE_COOKIE=false hanya untuk smoke test Docker
   lokal HTTP; pengaturan deployment HTTPS tetap true. Uji `/up`, `/daftar`, `/masuk`, login
   petugas, OTP, upload kecil, dan unduh milik pemohon. Restart container dan periksa file
   serta session tetap dapat digunakan dari storage/database eksternal.
6. Jalankan migration production/preview sebagai langkah rilis terpisah dari mesin/CI yang
   memiliki koneksi aman ke database tujuan. Periksa host/database sebelum menjalankan:

   ```sh
   php artisan migrate --force
   php artisan db:seed --class=RolePermissionSeeder --force
   php artisan db:seed --class=KategoriSeeder --force
   php artisan db:seed --class=UserSeeder --force
   ```

   Seeder ini untuk database baru; jangan menimpa konfigurasi role/data operasional yang
   sudah ada tanpa review. Migrasi tidak dijalankan di Docker build atau container startup.
7. Buat Preview setelah izin deployment diberikan. Pastikan route `/up` 200, lalu smoke-test
   workflow dengan database sungguhan; health endpoint saja tidak membuktikan DB/Meta/S3 sehat.
8. Set callback Meta ke URL Preview yang stabil, lakukan pengiriman testing, pastikan status
   delivery tercatat. Isi SMTP dan verifikasi notifikasi email serta notifikasi_log.
9. Cron Vercel memanggil `/api/maintenance/prune` setiap hari pukul 19:00 UTC (02:00 WIB), dengan
   Authorization Bearer CRON_SECRET. Test cron secara manual di Preview; jadwal otomatis
   Vercel dijalankan pada Production. Retensi: OTP 7 hari, log WhatsApp 30 hari secara default.
10. Setelah masalah upload besar, build Docker, uji DB/S3/Meta nyata, serta review terselesaikan,
    isi environment Production terpisah, merge PR sesuai persetujuan, dan lakukan rilis.
    Simpan deployment sebelumnya untuk rollback kode; jangan rollback database secara otomatis.

Docker resmi dipilih karena ada panduan Laravel/FrankenPHP dari Vercel. Runtime PHP komunitas
memerlukan ketergantungan tambahan pada pemelihara pihak ketiga dan bukan pilihan awal.
Konfigurasi tidak membangun cache environment saat image dibuat; secret baru tersedia saat
runtime. Referensi [Laravel Docker di Vercel](https://vercel.com/kb/guide/laravel-php-with-docker).

## Hasil verifikasi lokal

Hasil final dan batas verifikasi dicatat setelah seluruh pemeriksaan selesai.
