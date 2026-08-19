# Rules — Sistem Permintaan Data BPS Kabupaten Padang Lawas

Dokumen ini merangkum aturan bisnis (business rules) yang berlaku dalam sistem, sebagai acuan implementasi validasi dan logika alur.

## 1. Klasifikasi Data

- **Data terbuka**: data yang boleh diakses/diunduh siapa saja tanpa approval. Contoh: publikasi rutin, tabel statistik umum yang sudah dirilis resmi.
- **Data khusus/mikro**: data yang memerlukan permintaan resmi dan approval berjenjang. Contoh: data mikro survei, data yang belum dipublikasikan, data dengan agregasi/potongan khusus sesuai kebutuhan pemohon.
- Penentuan suatu data masuk kategori terbuka atau khusus dilakukan oleh staf subject matter saat upload/verifikasi awal permintaan.

## 2. Aturan Upload Data Terbuka

- Hanya staf/petugas subject matter dan admin yang dapat mengupload ke katalog data terbuka
- Upload data terbuka **tidak memerlukan approval** — langsung publish setelah upload
- Setiap dataset wajib memiliki: judul, kategori/sektor, periode data, deskripsi singkat, file
- Jika dataset direvisi, versi lama ditandai sebagai "digantikan" (bukan dihapus), versi baru menjadi yang aktif

## 3. Aturan Permintaan Data Khusus

- Permintaan hanya dapat diajukan setelah nomor HP pemohon terverifikasi via OTP
- Setiap permintaan wajib mencantumkan: jenis data yang diminta, tujuan penggunaan, periode data
- Setiap permintaan mendapat nomor tiket unik otomatis saat submit

## 4. Aturan Approval Berjenjang

- Urutan approval: **Staf → Kasi → Kabid**
- Permintaan hanya dapat lanjut ke tahap berikutnya jika tahap sebelumnya menyetujui
- Setiap approver dapat: **Setujui**, **Tolak** (dengan alasan wajib diisi), atau **Minta info tambahan** (mengembalikan ke pemohon)
- Jika ditolak pada tahap manapun, permintaan berstatus "Ditolak" dan proses berhenti — pemohon mendapat notifikasi email berisi alasan penolakan
- Approval bersifat berurutan (sekuensial), bukan paralel — satu tahap harus selesai sebelum masuk tahap berikutnya
- Kabid adalah approval final; setelah disetujui Kabid, status berubah menjadi "Menunggu Upload Data" dan diteruskan ke petugas untuk upload file hasil

## 5. Aturan Upload Hasil Data (Pasca-Approval)

- Hanya petugas yang ditunjuk/berwenang yang dapat mengupload file hasil untuk permintaan yang telah disetujui Kabid
- File yang diupload otomatis terhubung ke nomor tiket permintaan terkait
- Setelah file diupload, status otomatis berubah menjadi "Data Siap Diunduh" dan notifikasi email terkirim ke pemohon

## 6. Aturan Notifikasi

- Notifikasi email dikirim otomatis pada setiap perubahan status berikut:
  - Permintaan diterima (submit berhasil, berisi nomor tiket)
  - Disetujui/ditolak oleh Staf
  - Disetujui/ditolak oleh Kasi
  - Disetujui/ditolak oleh Kabid
  - Data siap diunduh
- Setiap notifikasi tercatat dalam log (untuk audit/troubleshooting jika email gagal terkirim)

## 7. Aturan Akses & Unduhan

- Data terbuka dapat diunduh oleh siapa saja tanpa login
- Data hasil permintaan khusus hanya dapat diunduh oleh pemohon terkait, dengan verifikasi nomor tiket + nomor HP yang digunakan saat pengajuan
- Setiap unduhan (baik data terbuka maupun data khusus) tercatat dalam log unduhan untuk kebutuhan laporan

## 8. Aturan Retensi & Penghapusan (perlu konfirmasi lebih lanjut)

- File hasil permintaan khusus disimpan minimal selama [perlu ditentukan] sebelum dapat dihapus/diarsipkan
- Dataset di katalog data terbuka tidak dihapus, hanya dapat digantikan versi barunya

## 9. Aturan Role & Kewenangan

| Role | Kewenangan |
|---|---|
| Pemohon | Ajukan permintaan, cek status, unduh data (sesuai kepemilikan) |
| Staf | Verifikasi kelengkapan permintaan, upload/kelola katalog data terbuka, upload hasil (jika ditunjuk) |
| Kasi | Approval tahap 2, dapat menolak/meminta info tambahan |
| Kabid | Approval final, dapat menolak/meminta info tambahan |
| Admin | Kelola user & role, akses penuh ke seluruh data & laporan |

## 10. Aturan Akun Pemohon Publik (Tambahan 19 Agustus 2026)

- Pemohon mendaftar dan masuk tanpa password dengan OTP yang dikirim ke nomor WhatsApp.
- Profil pemohon baru hanya dibuat setelah nomor WhatsApp berhasil diverifikasi.
- Sistem tidak boleh memberi respons berbeda untuk nomor terdaftar dan belum terdaftar sebelum OTP valid.
- Satu nomor WhatsApp kanonis hanya boleh terhubung ke satu identitas pemohon. Konflik data lama harus gagal tertutup dan ditinjau admin, bukan dipilih otomatis.
- Pemohon yang sudah masuk hanya boleh melihat halaman selesai, riwayat, dan file hasil milik `pemohon_id` pada sesinya.
- Cek status menggunakan nomor tiket dan nomor WhatsApp tetap tersedia tanpa login, tetapi unduhan hasil khusus tetap mewajibkan akun pemilik.
- Keluar wajib menggunakan request `POST` dengan CSRF dan membersihkan seluruh state autentikasi serta challenge OTP pemohon.
