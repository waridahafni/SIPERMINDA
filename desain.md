# Desain — Sistem Permintaan Data BPS Kabupaten Padang Lawas

## 1. Prinsip Desain

- **Sederhana & jelas** — pemohon publik kemungkinan besar bukan pengguna teknis, alur harus minim langkah dan mudah dipahami
- **Transparan** — status permintaan selalu terlihat jelas (progress bar/tahapan)
- **Mobile-friendly** — banyak pemohon publik kemungkinan akses via HP, jadi desain responsif jadi prioritas
- **Konsisten** — gunakan warna/identitas BPS (biru sebagai warna utama sesuai identitas visual BPS)

## 2. Peta Halaman (Sitemap)

### 2.1 Area Publik (tanpa login / dengan verifikasi HP)
- Beranda — pengantar layanan, pintasan ke katalog data & form permintaan
- Katalog Data Terbuka — daftar dataset, search & filter
- Detail Dataset — deskripsi, metadata, tombol unduh
- Form Permintaan Data Khusus — input data pemohon + verifikasi OTP
- Cek Status Permintaan — input nomor tiket + nomor HP
- Halaman Detail Status — timeline status permintaan

### 2.2 Area Internal (login staf/kasi/kabid/admin)
- Login
- Dashboard ringkasan (jumlah permintaan per status, grafik tren)
- Daftar Permintaan Masuk (dengan filter status, jenis data, tanggal)
- Detail Permintaan — form verifikasi/approval/penolakan, catatan
- Manajemen Katalog Data Terbuka — upload, edit, hapus dataset
- Manajemen User & Role (khusus admin)
- Laporan/Export (untuk kebutuhan pelaporan kinerja layanan)

## 3. Alur Layar (Screen Flow)

### 3.1 Pemohon — Data Terbuka
```
Beranda → Katalog Data → Detail Dataset → [Unduh] → Selesai
```

### 3.2 Pemohon — Data Khusus
```
Beranda → Form Permintaan → Verifikasi OTP HP → Isi Detail Permintaan
   → Konfirmasi & Submit → Nomor Tiket Diberikan
   → (menunggu proses) → Notifikasi Email "Data Siap"
   → Cek Status → Unduh Data
```

### 3.3 Staf/Kasi/Kabid — Approval
```
Login → Dashboard → Daftar Permintaan Masuk → Pilih Permintaan
   → Review Detail → [Setujui / Tolak / Minta Info Tambahan]
   → (jika disetujui final oleh Kabid) → Petugas Upload File Hasil
   → Status berubah jadi "Data Siap"
```

## 4. Komponen UI Utama

- **Tabel status permintaan** — dengan badge warna per status (kuning: diproses, hijau: disetujui, merah: ditolak, biru: selesai)
- **Timeline/stepper** — visualisasi tahapan approval (Diajukan → Staf → Kasi → Kabid → Selesai)
- **Form OTP** — input nomor HP → kirim kode → input kode 6 digit
- **Kartu dataset** di katalog — judul, kategori, tanggal update, ukuran file, tombol unduh
- **Search bar dengan filter** — kategori/sektor, tahun/periode

## 5. Catatan Aksesibilitas & Bahasa

- Seluruh antarmuka menggunakan Bahasa Indonesia
- Label form jelas, sertakan contoh pengisian (misal contoh format tujuan penggunaan data)
- Pesan error/validasi ditampilkan spesifik (bukan generik "terjadi kesalahan")
