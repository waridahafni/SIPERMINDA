<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## SIPERMINDA

SIPERMINDA adalah Sistem Permintaan Data BPS Kabupaten Padang Lawas. Aplikasi ini menyediakan katalog data terbuka, pengajuan permintaan khusus, verifikasi OTP, approval berjenjang, serta laporan layanan.

### Kebutuhan lokal

- PHP 8.3 atau lebih baru beserta Composer
- Node.js 20.19+ atau 22.12+ beserta npm
- SQLite untuk pengembangan lokal dan test; MySQL/MariaDB direkomendasikan untuk production

### Instalasi

1. Salin `.env.example` menjadi `.env`, lalu isi koneksi database dan konfigurasi mail. Pertahankan `APP_TIMEZONE=Asia/Jakarta` agar nomor tiket, scheduler, dan waktu layanan mengikuti WIB.
2. Isi `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, dan `INITIAL_ADMIN_PASSWORD`. Password awal harus minimal 12 karakter serta mengandung huruf besar, huruf kecil, dan angka.
3. Jalankan `composer install`, `php artisan key:generate`, `php artisan migrate --seed`, `npm install`, dan `npm run build`.
4. Jalankan pengembangan lokal dengan `composer run dev`.

### Akun pemohon publik

Pemohon dari masyarakat atau instansi memakai akun tanpa password berbasis nomor HP:

- Pemohon baru membuka `/daftar`, mengisi profil, lalu memverifikasi OTP SMS.
- Pemohon yang sudah terdaftar membuka `/masuk` dan cukup memasukkan nomor HP serta OTP SMS.
- Jika nomor yang belum terdaftar masuk dari halaman Masuk, profil baru diminta setelah nomor berhasil diverifikasi. Sistem tidak mengungkap keberadaan akun sebelum OTP valid.
- Setelah masuk, pemohon dapat membuka `/akun/permintaan` untuk melihat permintaan miliknya, mengajukan permintaan baru, dan mengunduh hasil yang sudah tersedia.
- Tombol Keluar mengakhiri sesi pemohon melalui request `POST`; akun internal petugas tetap menggunakan `/internal/login` dengan email dan password.

Identitas publik tetap memakai tabel `pemohon`; tidak diperlukan password atau akun tambahan pada tabel `users`. Nomor disimpan dalam format kanonis `628...`, sementara format `08...`, `62...`, dan `+62...` tetap diterima saat input.

### Informasi tambahan permintaan

Pada setiap tahap approval, petugas yang berwenang dapat meminta informasi tambahan. Tiket akan berstatus **Menunggu Info Pemohon** sampai pemohon pemiliknya membuka detail pada `/akun/permintaan`, mengirim jawaban, dan tiket kembali otomatis ke tahap yang meminta. Pertanyaan, jawaban, peminta, dan waktunya disimpan pada `permintaan_klarifikasi`; catatan internal tidak pernah ditampilkan kepada pemohon.

Versi awal menerima jawaban teks tanpa lampiran. Ini disengaja sampai allowlist tipe file, pemindaian malware, kuota, dan retensi dokumen pendukung ditetapkan.

### OTP SMS

Pada environment `local`, gunakan `OTP_DRIVER=log` agar OTP hanya ditulis ke log lokal. Driver ini otomatis ditolak pada production.

Kanal production aktif menggunakan Verihubs SMS OTP V2. Integrasi mempertahankan kode 6 digit, hash, TTL maksimal 5 menit, rate limit, dan verifikasi atomik yang dikelola aplikasi.

1. Buat dan aktifkan akun Verihubs, buat aplikasi, lalu siapkan layanan SMS OTP dan Sender ID **SIPERMINDA**. Sender ID harus disetujui operator sebelum go-live.
2. Salin konfigurasi SMS dari `.env.example`, atur `OTP_DRIVER=sms`, lalu isi `VERIHUBS_APP_ID` serta `VERIHUBS_API_KEY` langsung pada `.env` production.
3. Pertahankan `OTP_EXPIRES_MINUTES=5` dan template yang memuat nama SIPERMINDA serta variabel literal `$OTP`. Alamat API Verihubs dikunci di kode agar kredensial tidak terkirim ke host lain akibat salah konfigurasi.
4. Gunakan `VERIHUBS_SANDBOX=true` hanya untuk skenario nomor uji resmi Verihubs. Gunakan `false` untuk pengiriman nyata setelah akun aktif.
5. Jalankan `php artisan config:clear` atau bangun ulang cache konfigurasi setelah `.env` berubah, lalu uji satu nomor internal sebelum rilis.
6. Aktifkan cron cPanel untuk menjalankan `php artisan schedule:run` setiap menit. Scheduler menghapus metadata OTP yang telah kedaluwarsa lebih dari `OTP_RETENTION_DAYS` (default 7 hari).

Jangan menyimpan App ID/API key di Git atau mengirimkannya melalui chat. Aplikasi tidak melakukan retry otomatis saat timeout agar tidak menggandakan SMS berbayar. Pengiriman baru dianggap diterima bila provider membalas `201` dengan `session_id`, nomor tujuan, dan OTP yang cocok. Status sampai ke perangkat tetap perlu dipantau dari dashboard/callback provider.

Aplikasi sengaja memverifikasi hash OTP secara lokal agar aturan percobaan, kedaluwarsa, dan konsumsi atomik tetap berada dalam satu sumber kebenaran. Karena endpoint Verify OTP Verihubs tidak dipanggil, transaksi dapat terlihat **Not Verified** di dashboard Verihubs meskipun autentikasi lokal berhasil; gunakan log audit aplikasi sebagai acuan keberhasilan login.

Referensi: [Send OTP V2](https://docs.verihubs.com/reference/send_otp_post_v2), [sandbox SMS OTP](https://docs.verihubs.com/reference/sandbox_send_otp_post_v2), dan [panduan SMS OTP](https://docs.verihubs.com/docs/sms-otp).

### OTP WhatsApp Fonnte (khusus demo)

Fonnte tersedia hanya untuk menguji alur OTP WhatsApp ketika `APP_ENV=local` atau `testing`. Integrasi ini memakai sesi perangkat WhatsApp yang ditautkan melalui QR, sehingga **dilarang digunakan pada production resmi BPS**. Keputusan kanal production tetap Verihubs SMS OTP V2; Fonnte bukan fallback otomatis saat provider lain gagal.

1. Buat perangkat khusus demo di dashboard Fonnte, lalu tautkan nomor WhatsApp yang telah diizinkan melalui menu perangkat tertaut/QR. Hindari memakai nomor pribadi petugas.
2. Salin konfigurasi Fonnte dari `.env.example`, pastikan `APP_ENV=local`, atur `OTP_DRIVER=fonnte`, lalu isi `FONNTE_TOKEN` langsung pada `.env` lokal. Pertahankan template dengan variabel literal `$OTP` dan timeout yang pendek.
3. Jalankan `php artisan config:clear`, kemudian uji satu nomor internal. Halaman verifikasi akan menampilkan kanal **WhatsApp (demo)** agar tidak tertukar dengan Meta WhatsApp Cloud API.
4. Setelah demo selesai, kembalikan driver sesuai environment, putuskan perangkat jika tidak lagi diperlukan, dan rotasi token yang pernah terpapar.

Token Fonnte dapat digunakan untuk mengirim pesan dari perangkat yang terhubung, sehingga tidak boleh disimpan di Git, dikirim melalui chat, ditampilkan pada screenshot, atau dicatat ke log. Endpoint API dikunci di konfigurasi aplikasi dan pengiriman tidak dicoba ulang otomatis agar timeout tidak menghasilkan pesan ganda. Respons sukses provider bukan bukti delivery; hash OTP dan konsumsi atomik di aplikasi tetap menjadi sumber kebenaran autentikasi.

Referensi: [cara menghubungkan perangkat](https://docs.fonnte.com/how-to-connect/), [token API](https://docs.fonnte.com/token-api-key/), dan [API pengiriman pesan](https://docs.fonnte.com/api-send-message/).

### OTP WhatsApp (legacy/rollback)

Adapter Meta WhatsApp tetap tersedia sementara untuk rollback terkontrol, tetapi bukan kanal production utama sejak keputusan 24 Agustus 2026.

Untuk mengaktifkan pengiriman nyata melalui Meta WhatsApp Cloud API:

1. Siapkan WhatsApp Business Account, nomor pengirim, dan System User access token dengan izin `whatsapp_business_messaging` serta `whatsapp_business_management`.
2. Buat template kategori **AUTHENTICATION** bernama `siperminda_kode_otp`, tambahkan rekomendasi keamanan, `code_expiration_minutes=5`, `message_send_ttl_seconds` maksimal 300 detik, serta tombol **COPY_CODE**, lalu tunggu statusnya `APPROVED`.
3. Atur `OTP_DRIVER=whatsapp`, `OTP_EXPIRES_MINUTES=5`, dan isi `WHATSAPP_API_VERSION`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_OTP_TEMPLATE_NAME`, serta `WHATSAPP_OTP_TEMPLATE_LANGUAGE` pada `.env` production. Masa berlaku backend wajib sama dengan template dan dibatasi maksimal 5 menit oleh aplikasi.
4. Jalankan `php artisan config:clear` atau bangun ulang cache konfigurasi setelah `.env` berubah.
5. Aktifkan cron cPanel untuk menjalankan `php artisan schedule:run` setiap menit. Scheduler menghapus metadata OTP yang telah kedaluwarsa lebih dari `OTP_RETENTION_DAYS` (default 7 hari).

Jangan menyimpan access token WhatsApp di Git atau mengirimkannya melalui chat; masukkan langsung ke `.env` production dan rotasi bila terpapar. Respons sukses dari Meta hanya berarti pesan diterima API, belum menjamin pesan sudah sampai ke perangkat. Webhook status delivery dapat ditambahkan pada fase operasional berikutnya. Aplikasi sengaja tidak melakukan retry otomatis agar timeout tidak menggandakan pesan berbayar.

Referensi setup tersedia pada [contoh OTP resmi WhatsApp](https://github.com/WhatsApp/WhatsApp-OTP-Sample-App), [koleksi API resmi Meta](https://www.postman.com/meta/whatsapp-business-platform/overview), dan [contoh pembuatan template authentication resmi](https://www.postman.com/meta/whatsapp-business-platform/request/qzriq9r/create-authentication-template-w-otp-copy-code-button).

### Pemeriksaan kualitas

Jalankan `composer test`, `vendor/bin/pint --test`, dan `npm run build` sebelum melakukan deployment.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


## WhatsApp Meta dan Vercel ? keputusan terbaru 12 September 2026

Target saat ini adalah OTP Meta WhatsApp Cloud API serta deployment Vercel dengan Docker/FrankenPHP. Keterangan kanal SMS utama, Meta legacy dan hosting cPanel pada bagian sebelumnya dipertahankan sebagai riwayat keputusan. Set OTP_DRIVER=whatsapp hanya setelah credential dan template Meta siap.

Lihat [panduan setup Meta, checklist environment, storage dan deployment](docs/WHATSAPP_VERCEL.md), serta [.env.production.example](.env.production.example). Panduan mencantumkan hasil verifikasi dan blocker upload 50 MB pada batas payload Vercel; konfigurasi ini belum dinyatakan siap go-live penuh. Database lokal tidak dimigrasikan otomatis dan belum ada push/deploy.
