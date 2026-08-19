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

1. Salin `.env.example` menjadi `.env`, lalu isi koneksi database dan konfigurasi mail.
2. Isi `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, dan `INITIAL_ADMIN_PASSWORD`. Password awal harus minimal 12 karakter serta mengandung huruf besar, huruf kecil, dan angka.
3. Jalankan `composer install`, `php artisan key:generate`, `php artisan migrate --seed`, `npm install`, dan `npm run build`.
4. Jalankan pengembangan lokal dengan `composer run dev`.

### Akun pemohon publik

Pemohon dari masyarakat atau instansi memakai akun tanpa password berbasis nomor WhatsApp:

- Pemohon baru membuka `/daftar`, mengisi profil, lalu memverifikasi OTP WhatsApp.
- Pemohon yang sudah terdaftar membuka `/masuk` dan cukup memasukkan nomor WhatsApp serta OTP.
- Jika nomor yang belum terdaftar masuk dari halaman Masuk, profil baru diminta setelah nomor berhasil diverifikasi. Sistem tidak mengungkap keberadaan akun sebelum OTP valid.
- Setelah masuk, pemohon dapat membuka `/akun/permintaan` untuk melihat permintaan miliknya, mengajukan permintaan baru, dan mengunduh hasil yang sudah tersedia.
- Tombol Keluar mengakhiri sesi pemohon melalui request `POST`; akun internal petugas tetap menggunakan `/internal/login` dengan email dan password.

Identitas publik tetap memakai tabel `pemohon`; tidak diperlukan password atau akun tambahan pada tabel `users`. Nomor disimpan dalam format kanonis `628...`, sementara format `08...`, `62...`, dan `+62...` tetap diterima saat input.

### OTP WhatsApp

Pada environment `local`, gunakan `OTP_DRIVER=log` agar OTP hanya ditulis ke log lokal. Driver ini otomatis ditolak pada production.

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
