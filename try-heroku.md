# Heroku Deploy – Catatan Error & Solusi + Panduan Deploy Aman

Dokumen ini merangkum masalah yang kita temui saat deploy aplikasi Laravel ke Heroku, solusi yang diambil, dan langkah-langkah deploy agar tidak mengulang error yang sama.

> Penting: Jangan pernah commit file .env atau menaruh secrets (API keys, DB password, dsb) ke repo. Semua secrets wajib diatur sebagai Heroku Config Vars.

---

## Ringkasan Masalah & Solusi

### 1) 500 Error karena DB connect ke 127.0.0.1 (bukan Heroku Postgres)
- Gejala:
  - `SQLSTATE[08006] [7] connection to server at "127.0.0.1", port 5432 failed: Connection refused`
- Akar masalah:
  - `heroku-postbuild.sh` menjalankan `php artisan config:cache` saat build. Ini “membakar” nilai default ke dalam cache (mis. DB_HOST=127.0.0.1) sebelum Config Vars Heroku runtime tersedia.
- Solusi yang diterapkan:
  - Hapus `config:cache` dan `route:cache` dari heroku-postbuild (hanya `view:cache`).
  - Ubah `Procfile` untuk membersihkan cache saat dyno start:
    - `web: sh -lc 'php artisan config:clear || true; php artisan route:clear || true; vendor/bin/heroku-php-apache2 public/'`
  - Pastikan env DB dipakai runtime:
    - Gunakan `DATABASE_URL`/`DB_URL` Heroku atau set `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` via Config Vars.

### 2) Gagal migrasi karena urutan dependensi tabel
- Gejala:
  - Migration awal (2024) mereferensikan tabel `osce_cases`/`osce_sessions` yang baru dibuat di migration 2025, sehingga error “Undefined table”.
- Solusi yang diterapkan:
  - Jalankan migration yang membuat tabel osce lebih dulu:
    - `2025_08_16_023044_create_osce_cases_table.php`
    - `2025_08_16_023052_create_osce_sessions_table.php`
  - Setelah itu `php artisan migrate --force` berhasil menyelesaikan sisanya.
  - Rekomendasi jangka panjang: perbaiki timestamp urutan file migration agar dependensi natural.

### 3) Error Redis (TLS verification / auth) memblokir request
- Gejala (contoh log):
  - `SSL routines::certificate verify failed` (Predis/PhpRedis)
  - `read error on connection ... auth()` (PhpRedis)
- Solusi yang diterapkan:
  - Tambahkan `ext-redis` di composer agar Heroku menginstal ekstensi native.
  - Set sementara agar aplikasi tidak blok oleh Redis:
    - `SESSION_DRIVER=file` dan `CACHE_STORE=file` di Config Vars (aplikasi langsung 200 OK).
  - Siapkan dukungan TLS saat nanti kembali pakai Redis:
    - Di `config/database.php`, tambahkan context TLS di `redis.options.context.ssl` dengan `verify_peer`/`verify_peer_name` dapat diatur dari env.
    - Gunakan `REDIS_URL=rediss://...` (TLS). Jika perlu, set `REDIS_VERIFY_PEER=false` dan `REDIS_VERIFY_PEER_NAME=false`.
  - Catatan: Untuk production yang lebih strict, siapkan CA bundle yang valid dan aktifkan verifikasi sertifikat.

### 4) Headless login Heroku (tanpa browser) & penyimpanan kredensial
- Karena MFA aktif, gunakan API key (HEROKU_API_KEY) atau file `~/.netrc` untuk otomatisasi CLI. Jangan menaruh key di repo.

---

## Checklist Konfigurasi Heroku (Config Vars)
Set variabel berikut di Heroku (Dashboard → Settings → Config Vars). Pakai nilai Anda sendiri; jangan menaruh secrets di repo.

- Aplikasi Laravel
  - `APP_ENV=production`
  - `APP_DEBUG=false` (aktifkan true sementara untuk debug)
  - `APP_KEY={{APP_KEY}}` (gunakan `php artisan key:generate` lokal, lalu set ke Heroku)
  - `APP_URL=https://{{APP_NAME}}.herokuapp.com`

- Database (pilih salah satu skema)
  - Skema A: `DATABASE_URL=postgres://...` (disediakan Heroku Postgres)
  - Skema B: `DB_URL=postgres://...` atau set individual:
    - `DB_CONNECTION=pgsql`
    - `DB_HOST={{DB_HOST}}`
    - `DB_PORT=5432`
    - `DB_DATABASE={{DB_NAME}}`
    - `DB_USERNAME={{DB_USER}}`
    - `DB_PASSWORD={{DB_PASS}}`

- Redis (opsional; bila ingin aktif kembali)
  - `REDIS_URL=rediss://:{{REDIS_PASSWORD}}@{{REDIS_HOST}}:{{REDIS_PORT}}`
  - `REDIS_CLIENT=phpredis`
  - `REDIS_VERIFY_PEER=false` (atau true + siapkan CA)
  - `REDIS_VERIFY_PEER_NAME=false`
  - Jika Redis belum stabil, gunakan dulu:
    - `SESSION_DRIVER=file`
    - `CACHE_STORE=file`

- Lainnya (sesuai kebutuhan aplikasi)
  - API keys pihak ketiga, storage, dsb. Pastikan semua via Config Vars, bukan di repo.

---

## Panduan Deploy Aman (Step by Step)

1) Pastikan repo bersih dan dependency terkunci
- Jangan ada file `.env` ter-commit.
- Jika mengubah `composer.json`, selalu jalankan di lokal: `composer update` (boleh `--ignore-platform-req=ext-redis` bila ekstensi tidak ada di lokal), lalu commit **keduanya**: `composer.json` dan `composer.lock`.

2) Pastikan skrip build dan Procfile aman untuk Heroku
- Di `heroku-postbuild.sh`: jangan jalankan `php artisan config:cache` atau `php artisan route:cache`. Biarkan hanya `php artisan view:cache`.
- Di `Procfile`:
  - `web: sh -lc 'php artisan config:clear || true; php artisan route:clear || true; vendor/bin/heroku-php-apache2 public/'`
  - (Opsional) Worker untuk queue jika dipakai: `worker: php artisan queue:work ...`

3) Set Config Vars (sekali saja / saat ada perubahan)
- Set `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `APP_URL`.
- Set DB via `DATABASE_URL` atau `DB_URL` / `DB_*`.
- (Opsional) Set Redis (lihat bagian Redis di atas). Kalau ragu, pakai `SESSION_DRIVER=file` & `CACHE_STORE=file` dulu.

4) Deploy
- `git push heroku <branch-anda>:main`
- Heroku akan build Node + PHP, lalu start dyno.

5) Jalankan migrasi database
- Opsi A (manual, aman):
  - `heroku run --app {{APP_NAME}} 'php artisan migrate --force'`
- Opsi B (otomatis): tambah release phase di Procfile:
  - `release: php artisan migrate --force`
  - Catatan: Pastikan migrasi tidak lama/berisiko; kalau ya, tetap manual.

6) Verifikasi
- `curl -I https://{{APP_NAME}}.herokuapp.com/` harus `200 OK`.
- `heroku logs --app {{APP_NAME}} --source app --num=200` cek error.
- `heroku run --app {{APP_NAME}} 'php artisan config:show database.connections.pgsql'` untuk memastikan DB host/url benar.
- `heroku run --app {{APP_NAME}} 'php artisan migrate:status'` untuk memastikan migrasi up.

7) (Opsional) Aktifkan Redis kembali
- Set: `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `REDIS_CLIENT=phpredis`, `REDIS_URL=rediss://...`.
- Jika gagal verifikasi TLS, gunakan `REDIS_VERIFY_PEER=false`/`REDIS_VERIFY_PEER_NAME=false` (sementara). Jangka panjang gunakan CA yang valid.
- Pantau logs untuk error Redis.

---

## Troubleshooting Cepat
- 500 Internal Server Error (DB 127.0.0.1)
  - Pastikan tidak cache config saat build. Bersihkan cache saat start.
  - Cek `config:show database.connections.pgsql` dan environment DB.
- Migration gagal karena “Undefined table”
  - Urutkan migrasi dependensi. Jalankan manual migration untuk tabel fundamental terlebih dahulu, atau perbaiki timestamp.
- Redis error (TLS/auth) memblokir request
  - Sementara set `SESSION_DRIVER=file` & `CACHE_STORE=file` agar web tetap jalan.
  - Saat Redis siap, aktifkan kembali dengan konfigurasi TLS yang benar.

---

## Perubahan yang Sudah Dilakukan di Repo Ini
- `heroku-postbuild.sh`: hapus `config:cache` & `route:cache` (hanya `view:cache`).
- `Procfile` (web): tambahkan `php artisan config:clear` dan `php artisan route:clear` saat boot sebelum start Apache.
- `composer.json`: tambahkan `ext-redis` agar Heroku memasang ekstensi redis native.
- `config/database.php`: tambahkan TLS context opsional untuk Redis.

---

## Catatan Keamanan
- Jangan pernah menaruh API key / password di repo atau output publik.
- Semua secrets harus diset sebagai Heroku Config Vars.
- Bila menyalin perintah yang memakai secrets, gunakan placeholder seperti `{{DATABASE_URL}}`, `{{REDIS_URL}}`, `{{HEROKU_API_KEY}}` dan set nilainya via CLI/Dasbor Heroku.


---

## Update: Mixed Content di Custom Domain (HTTPS) – FIXED
- Gejala: Halaman dimuat via HTTPS, tetapi asset (CSS/JS) direquest via http:// sehingga diblokir (Mixed Content).
- Penyebab umum:
  - APP_URL/ASSET_URL tidak menggunakan skema https.
  - Laravel tidak mendeteksi HTTPS di balik proxy (Heroku), sehingga generate URL http.
  - Template menggunakan absolute http:// alih-alih helper/relative.
- Solusi yang diterapkan:
  1) Set Config Vars agar https:
     - `APP_URL=https://osce.bintangputra.my.id`
     - `ASSET_URL=https://osce.bintangputra.my.id`
  2) Paksa skema HTTPS saat production (Heroku):
     - Di `app/Providers/AppServiceProvider.php` (method `boot`):
       ```php
       use Illuminate\Support\Facades\URL;
       
       if (app()->environment('production')) {
           URL::forceScheme('https');
       }
       ```
  3) Pastikan tidak ada link http hardcoded di Blade; gunakan `@vite` atau `asset()` tanpa skema.
  4) Clear cache (sudah otomatis via Procfile pada dyno start) lalu restart: `heroku restart`.
- Verifikasi: di DevTools Network atau header `Link:` pada response, asset `build/...` sekarang served via `https://`.

Tambahan ke Checklist:
- Set `APP_URL` dan `ASSET_URL` ke URL https custom domain Anda.
- Pastikan `AppServiceProvider` memaksa HTTPS di production.

Tambahan ke Perubahan di Repo:
- `app/Providers/AppServiceProvider.php`: tambahkan `URL::forceScheme('https')` saat environment `production`.

