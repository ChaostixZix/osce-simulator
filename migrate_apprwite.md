# Appwrite TablesDB Migration Guide

Panduan ini menjelaskan langkah yang perlu kamu lakukan untuk menyalakan integrasi Appwrite TablesDB yang baru dan menjalankan migrasi awal. Ikuti urutan berikut agar koneksi ke Appwrite tersetup dengan benar dan koleksi dasar tercipta.

## 1. Persiapan Awal
- Pastikan dependensi PHP sudah ter-install (`composer install`). Paket `appwrite/appwrite` v15.0.0 sekarang menjadi bagian dari `composer.json`.
- Pastikan kamu punya kredensial Appwrite berikut (dari Appwrite Console → Project Settings → API Keys):
  - Endpoint: `https://syd.cloud.appwrite.io/v1`
  - Project ID: `project-syd-68bdb4430034982b7654`
  - API Key: kunci API standar dengan scope database (`standard_3956…`).
- Siapkan ID database dan koleksi yang ingin kamu gunakan. Secara default konfigurasi aplikasi mengandaikan:
  - Database ID: `vibe-primary`
  - Database Name: `Vibe Kanban Tables`
  - Koleksi migrasi: `appwrite_migrations`

> Kamu bisa mengganti nilai default di `.env` jika ingin memakai ID lain, asalkan konsisten antara environment dan Appwrite Console.

## 2. Konfigurasi Environment
Tambahkan/ubah variabel berikut di `.env` (nilai contoh gunakan kredensial milikmu):

```dotenv
APPWRITE_ENABLED=true
APPWRITE_ENDPOINT=https://syd.cloud.appwrite.io/v1
APPWRITE_PROJECT_ID=project-syd-68bdb4430034982b7654
APPWRITE_API_KEY=standard_39563704fd49885fb39759e2c23b4792b7c55b35c52903d6529cf3ba7b81ecf08e8b6a62d90537824485529369fa1a131571fadfded7358599f685462a1002d6bc78fd5d9673f8cce83fd4940f01b1496933d1376b4b549cef3d5b1403f34aadf33e13346a81aab297b66e9bc2d24aabffd83f67834086c2cec62f3c6fcf2519
APPWRITE_DATABASE_ID=vibe-primary
APPWRITE_DATABASE_NAME="Vibe Kanban Tables"
APPWRITE_MIGRATIONS_COLLECTION_ID=appwrite_migrations
APPWRITE_MIGRATIONS_COLLECTION_NAME="Appwrite Migrations"
APPWRITE_COLLECTION_READ_ROLES=role:all
APPWRITE_COLLECTION_CREATE_ROLES=role:all
APPWRITE_COLLECTION_UPDATE_ROLES=role:all
APPWRITE_COLLECTION_DELETE_ROLES=role:all
APPWRITE_SELF_SIGNED=false
```

> Jangan simpan API key asli di repo publik. Untuk produksi gunakan secret manager atau environment variable di platform hostingmu.

## 3. Jalankan Pemeriksaan Konektivitas
Gunakan artisan command yang baru untuk memastikan kredensial valid dan koneksi Appwrite bisa dicapai.

```bash
php artisan appwrite:migrate test
```

Output akan menampilkan informasi total database di project, ID database, dan jumlah record migrasi yang tersimpan. Jika gagal, cek kembali endpoint, project ID, dan API key.

## 4. Eksekusi Migrasi Appwrite
Setelah koneksi terverifikasi, jalankan migrasi Appwrite seperti berikut:

```bash
php artisan appwrite:migrate up
```

Command ini akan:
1. Membuat database `vibe-primary` jika belum ada.
2. Membuat koleksi `appwrite_migrations` + atribut `migration`, `batch`, `ran_at` untuk pencatatan migrasi.
3. Menjalankan migrasi Appwrite yang ada di `database/migrations/appwrite/`. Saat ini termasuk migrasi baseline `Health Checks` yang membuat koleksi contoh `health_checks`.

Kamu bisa gunakan opsi tambahan:
- `php artisan appwrite:migrate status` → melihat migrasi yang sudah jalan dan timestamp-nya.
- `php artisan appwrite:migrate down --steps=1` → rollback migrasi terakhir (akan memanggil `down()` di file migrasi jika tersedia).
- `php artisan appwrite:migrate refresh` → rollback semua migrasi lalu menjalankannya lagi.
- Tambahkan `--dry-run` untuk simulasi tanpa menulis perubahan ke Appwrite.

## 5. Menambahkan Migrasi Baru
- Letakkan file baru di `database/migrations/appwrite/` dengan format timestamp yang sama seperti migrasi Laravel, misal: `2025_03_01_120000_create_patient_notes_table.php`.
- File harus `return new class extends App\Appwrite\Migrations\Migration { ... }` dan implementasi `up(AppwriteService $appwrite)` untuk mendefinisikan koleksi/atribut/index.
- Jalankan `php artisan appwrite:migrate up` setelah menambahkan file agar migrasi tersimpan di log Appwrite.

## 6. Troubleshooting
| Masalah | Penyebab Umum | Solusi |
| --- | --- | --- |
| `Appwrite integration is disabled` | `APPWRITE_ENABLED` masih `false` atau tidak diset | Set jadi `true` dan ulangi command |
| Error 401/Forbidden | API Key tidak punya scope database | Buat API key baru dengan akses database penuh |
| Error 404 saat create collection | Database ID tidak ditemukan | Pastikan ID di `.env` sama dengan yang ada di Appwrite, atau biarkan command menciptakannya |
| Migrasi tidak muncul di `status` | Migrasi tidak dijalankan atau `markMigrationRan` gagal | Cek log Appwrite (`appwrite:migrate up`), pastikan command selesai tanpa error |

## 7. Langkah Setelah Migrasi
- Commit perubahan `.env.example` saja (jangan `.env`).
- Jika environment produksi berbeda, isi env variable di platform masing-masing.
- Jalankan kembali tes lokal terkait Appwrite (opsional) untuk memastikan perintah minimal berhasil:
  ```bash
  php artisan test --filter=Appwrite
  ```
- Dokumentasikan koleksi baru atau schema tambahan yang kamu buat agar tim lain bisa mengikuti.

---
Jika kamu butuh membuat koleksi baru atau skema lebih kompleks, gunakan helper di `App\Services\AppwriteService` seperti `ensureStringAttribute`, `ensureIndex`, dll. Mereka akan idempotent sehingga aman dipanggil berulang kali.
