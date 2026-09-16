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

## 10. Aturan Akun Pemohon Publik (Keputusan Awal 19 Agustus 2026)

- Pemohon mendaftar dan masuk tanpa password dengan OTP yang dikirim ke nomor WhatsApp.
- Profil pemohon baru hanya dibuat setelah nomor WhatsApp berhasil diverifikasi.
- Sistem tidak boleh memberi respons berbeda untuk nomor terdaftar dan belum terdaftar sebelum OTP valid.
- Satu nomor WhatsApp kanonis hanya boleh terhubung ke satu identitas pemohon. Konflik data lama harus gagal tertutup dan ditinjau admin, bukan dipilih otomatis.
- Pemohon yang sudah masuk hanya boleh melihat halaman selesai, riwayat, dan file hasil milik `pemohon_id` pada sesinya.
- Cek status menggunakan nomor tiket dan nomor WhatsApp tetap tersedia tanpa login, tetapi unduhan hasil khusus tetap mewajibkan akun pemilik.
- Keluar wajib menggunakan request `POST` dengan CSRF dan membersihkan seluruh state autentikasi serta challenge OTP pemohon.

## 11. Perubahan Kanal Verifikasi Pemohon (Tambahan 24 Agustus 2026)

- Aturan kanal WhatsApp pada bagian 10 digantikan secara operasional oleh OTP melalui SMS; identitas akun tetap nomor HP yang sama.
- Kode OTP SMS berlaku maksimal 5 menit, maksimal 5 percobaan, dan tidak boleh disimpan atau dicatat dalam bentuk teks biasa di database/log production.
- Production wajib gagal tertutup apabila provider SMS belum dikonfigurasi atau menolak pengiriman; tidak boleh beralih otomatis ke log atau WhatsApp.
- Satu nomor HP kanonis tetap hanya boleh terhubung ke satu identitas pemohon dan seluruh aturan anti-enumerasi, kepemilikan, serta logout pada bagian 10 tetap berlaku.
- Keberhasilan autentikasi ditentukan oleh verifikasi hash OTP di aplikasi. Status transaksi pada dashboard provider bukan sumber kebenaran login pemohon.

## 12. Aturan Informasi Tambahan (Tambahan 25 Agustus 2026)

- Minta info hanya dapat dilakukan oleh approver yang berwenang pada tahap aktif dan pertanyaan kepada pemohon wajib diisi.
- Selama status `menunggu_info_pemohon`, keputusan approval, upload hasil, dan penutupan permintaan tidak dapat dilakukan.
- Tahap asal ditentukan dan disimpan oleh server, bukan berasal dari input browser.
- Hanya akun pemohon pemilik tiket yang dapat menjawab. Akun lain harus menerima respons tidak ditemukan agar keberadaan tiket tidak bocor.
- Setelah jawaban diterima, permintaan kembali ke status tahap yang meminta dan dapat dinilai ulang oleh approver pada tahap tersebut.
- Satu permintaan hanya boleh memiliki satu pertanyaan terbuka. Jawaban yang sudah dikirim tidak dapat ditimpa; putaran baru dibuat sebagai riwayat baru.
- Pertanyaan dan jawaban terlihat oleh pemohon, sedangkan catatan internal hanya terlihat oleh petugas.
- Jawaban versi awal berbentuk teks. Dukungan lampiran ditunda sampai aturan keamanan dan retensinya disepakati.

## 13. Aturan Fonnte untuk Demo (Tambahan 25 Agustus 2026)

- Fonnte hanya boleh dipakai ketika `APP_ENV=local` atau `testing` untuk demo. Environment lainnya, khususnya production BPS, dilarang menggunakan `OTP_DRIVER=fonnte` dan wajib gagal tertutup jika driver tersebut dipilih.
- Fonnte tidak boleh menjadi fallback otomatis ketika Verihubs SMS atau Meta WhatsApp gagal, dan kegagalan Fonnte juga tidak boleh dialihkan otomatis ke provider lain.
- Nomor pengirim harus merupakan nomor khusus demo yang penggunaannya telah diizinkan, bukan nomor pribadi petugas. Perangkat harus diputuskan ketika demo berakhir jika tidak lagi diperlukan.
- Token Fonnte wajib disimpan sebagai rahasia environment, tidak boleh masuk Git, chat, screenshot, atau log, dan harus dirotasi jika terpapar.
- Masa berlaku, hash, batas percobaan, rate limit, lock pengiriman, serta konsumsi atomik OTP tetap mengikuti aturan autentikasi aplikasi. Respons sukses provider tidak membuktikan pesan telah diterima perangkat.
