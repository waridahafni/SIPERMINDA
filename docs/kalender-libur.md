# Kalender libur untuk batas layanan

`PermintaanData::batasLayanan()` menghitung hari setelah tanggal pengajuan,
mempertahankan jam pengajuan, dan melewati Sabtu, Minggu, serta tanggal dalam
`config/hari_libur.php`. Detail permintaan dan pengingat dashboard memakai metode
yang sama. Cuti bersama dan waktu menunggu jawaban pemohon tetap dihitung.

Kalender bawaan memuat libur nasional 2026 dan 2027 berdasarkan publikasi resmi:

- https://www.setneg.go.id/baca/index/inilah_skb_3_menteri_libur_nasional_dan_cuti_bersama_2026
- https://setneg.go.id/baca/index/inilah_skb_3_menteri_libur_nasional_dan_cuti_bersama_2027

Tidak ada panggilan API saat menghitung tenggat. Saat SKB berubah atau kalender
tahun berikutnya terbit, perbarui daftar tanggal `YYYY-MM-DD` di konfigurasi,
termasuk tanggal libur yang jatuh pada akhir pekan. Jalankan `php artisan
config:cache` jika deployment menggunakan cache konfigurasi, atau `php artisan
config:clear` untuk pengembangan lokal. Perubahan berlaku untuk perhitungan
permintaan yang sudah ada juga, karena tenggat dihitung saat halaman dibuka.

Tahun yang belum terdaftar hanya mengecualikan akhir pekan. Tambahkan kalender
tahun tersebut sebelum digunakan untuk menghitung libur nasional. Daftar tahun
yang tersedia ditampilkan di dashboard petugas.
