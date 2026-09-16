# Target terbaru: Supabase Storage (14 September 2026)

Target Preview sekarang memakai bucket privat Supabase dan database Neon Preview.
Isi credential S3 Supabase, nama bucket, region project, serta endpoint S3 lengkap
(termasuk `/storage/v1/s3`) pada `.env.preview` yang diabaikan Git.
Gunakan `AWS_USE_PATH_STYLE_ENDPOINT=true`, `DOCUMENTS_DISK=s3`, dan
`DOCUMENTS_DIRECT_UPLOAD=true`. Template tersedia pada `.env.production.example`.
Konfigurasi `.env` lokal tetap terpisah.

Presigned PUT dan otorisasi unduhan telah diuji menggunakan SDK dengan transport
tiruan untuk endpoint Supabase. Upload, finalisasi/copy, unduhan pada bucket nyata,
serta pembersihan staging masih perlu diverifikasi; jangan menerapkan petunjuk
lifecycle R2 di bawah sebagai konfigurasi Supabase.

Catatan R2 dan AWS berikut dipertahankan sebagai riwayat konfigurasi sebelumnya.

# Upload Cloudflare R2 sampai 50 MB

Konfigurasi aktif mulai 14 September 2026 memakai presigned PUT, menggantikan
POST policy AWS pada catatan riwayat di bawah. Driver Laravel tetap `s3`.

```dotenv
DOCUMENTS_DISK=s3
DOCUMENTS_DIRECT_UPLOAD=true
AWS_DEFAULT_REGION=auto
AWS_BUCKET=
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Isi Account ID, nama bucket, serta credential R2 privat. `AWS_ACL` tidak digunakan;
SDK menghapus ACL otomatis Flysystem. Biarkan public access/r2.dev nonaktif dan
batasi credential Object Read & Write ke bucket aplikasi.

Pasang CORS berikut pada bucket R2; ganti domain dengan origin aplikasi yang tepat:

```json
[{"AllowedOrigins":["https://DOMAIN-APLIKASI"],"AllowedMethods":["PUT"],"AllowedHeaders":["Content-Type"],"MaxAgeSeconds":300}]
```

- URL upload berlaku 5 menit, terikat key `pending_uploads/` dan Content-Type.
  Secret key tetap di server; Access Key ID terdapat dalam parameter signature
  standar S3. Perlakukan URL bertanda tangan sebagai akses sementara yang privat.
- PUT mengirim isi file langsung, tanpa FormData. MIME browser kosong memakai
  `application/octet-stream`; MIME sebenarnya tetap diperiksa dari isi file.
- Batas 50 MB diperiksa browser, penerbitan URL, dan HeadObject saat finalisasi.
  PUT tidak memiliki policy batas ukuran: object terlalu besar bisa masuk staging,
  tetapi tidak akan disimpan sebagai dokumen final. Atur lifecycle `pending_uploads/`
  selama 1 hari untuk membersihkan upload gagal/terbengkalai.
- Token finalisasi tetap 15 menit, sekali pakai, terikat user/aksi/record.
  GetObject dengan If-Match dan CopyObject dengan CopySourceIfMatch mempertahankan
  pemeriksaan MIME/ukuran serta mencegah perubahan object di antara pemeriksaan/copy.
- HeadObject, GetObject, PutObject, CopyObject dan DeleteObject sesuai API R2.
  Unduhan tetap memakai presigned GET setelah otorisasi, tanpa melewati payload Vercel.
  Referensi: [kompatibilitas R2](https://developers.cloudflare.com/r2/api/s3/api/)
  dan [presigned URLs](https://developers.cloudflare.com/r2/api/s3/presigned-urls/).
- Test memakai SDK dan signer aplikasi dengan transport tiruan. Uji bucket R2 nyata,
  CORS browser dan upload/download 50 MB tetap diperlukan sebelum production.

## Riwayat AWS S3 — telah digantikan konfigurasi R2 di atas

Implementasi ini menggantikan catatan lama bahwa direct upload belum tersedia.
Gunakan `DOCUMENTS_DISK=s3`, `DOCUMENTS_DIRECT_UPLOAD=true` dan
`AWS_ACL=bucket-owner-full-control` untuk AWS S3. Credential/region/bucket diisi
melalui environment privat, bukan Git. Provider lain harus mendukung S3 POST policy.

- Bucket tetap privat; aktifkan Block Public Access. Bucket owner enforced didukung.
- Credential aplikasi membutuhkan ListBucket, GetObject, PutObject dan DeleteObject
  pada bucket/prefix dokumen terkait. Copy memakai izin baca sumber dan tulis tujuan.
- Atur CORS bucket dengan origin HTTPS aplikasi yang tepat (pisahkan Preview/Production):

```json
[{"AllowedOrigins":["https://DOMAIN-APLIKASI"],"AllowedMethods":["POST"],"AllowedHeaders":["*"],"MaxAgeSeconds":300}]
```

- Tambahkan lifecycle untuk menghapus prefix `pending_uploads/` setelah 1 hari.
  Jika versioning aktif, atur juga penghapusan versi lama staging. Jangan terapkan
  aturan staging pada `dataset_terbuka/` atau `hasil_permintaan/`.
- Browser mengirim file ke S3 memakai policy 5 menit dengan key dan ukuran tepat.
  Token finalisasi berlaku 15 menit, terikat petugas/aksi/record, sekali pakai.
- Server memeriksa ukuran dan MIME aktual melalui file sementara, lalu menyalin
  object dengan syarat ETag ke key final yang tidak dapat ditulis browser.
  Tidak diperlukan migration baru untuk token; cache database existing digunakan.
- Unduhan S3 menggunakan URL bertanda tangan 5 menit setelah otorisasi controller.
  File lokal tetap memakai upload/download existing.

Sebelum production: uji browser dengan file 50 MB pada bucket asli, termasuk CORS,
izin, durasi verifikasi server dan unduhan. Test SDK/JavaScript lokal memakai
transport tiruan; belum membuktikan koneksi bucket atau runtime Vercel nyata.
