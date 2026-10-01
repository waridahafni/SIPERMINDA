# Login pemohon dengan password

- Pendaftaran: isi profil, verifikasi OTP WhatsApp, lalu buat password.
- Login berikutnya: nomor HP dan password, tanpa mengirim OTP.
- Akun lama tanpa password: pilih tautan pemulihan pada halaman masuk, verifikasi
  OTP sekali, lalu buat password. Identitas dan riwayat akun tetap dipertahankan.
- Lupa password: gunakan alur pemulihan yang sama. OTP tetap diperlukan untuk
  membuktikan kepemilikan nomor sebelum mengganti password.

Password disimpan sebagai hash Laravel dan tidak disertakan dalam serialisasi
model. Izin penggantian password dari OTP berlaku 10 menit dan hanya sekali pakai.
Penggantian password meningkatkan versi autentikasi sehingga sesi lama ditolak
pada akses berikutnya. Percobaan login dibatasi per nomor dan per alamat IP.

Jalankan `php artisan migrate` saat deployment untuk menambahkan kolom `password`
dan `auth_version`. Tidak ada password bawaan untuk akun lama.
