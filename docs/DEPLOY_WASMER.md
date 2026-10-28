# Deployment Wasmer Edge — Harvesta

> Dokumen ini mencatat setup, konfigurasi, bug yang ditemukan beserta perbaikannya, dan cara deploy ulang Harvesta di Wasmer Edge.
>
> Status: **production** di <https://harvesta.wasmer.app> (disepakati tim sebagai platform deploy utama, menggantikan Azure).

---

## 1. Ringkasan Arsitektur

| Komponen | Detail |
|---|---|
| Platform | Wasmer Edge (WebAssembly runtime, bukan container Docker biasa) |
| Interpreter PHP | `php/php` versi `8.3.4` (resmi dari Wasmer registry) |
| Laravel version | 13 (**tidak didowngrade** — lihat catatan di bagian 6) |
| Database | PostgreSQL bawaan Wasmer (bukan Supabase) |
| Nama app | `harvesta` (namespace `hilmyfausta`) |
| URL live | `https://harvesta.wasmer.app` |
| Dashboard manage | `https://wasmer.io/apps/hilmyfausta/harvesta` |

---

## 2. Install & Setup Wasmer CLI (di laptop, bukan di server)

Wasmer CLI adalah tool development yang di-install **lokal di laptop**, dipakai untuk build/test/deploy — sepenuhnya terpisah dari server.

```bash
curl https://get.wasmer.io -sSfL | sh
source ~/.bashrc   # atau ~/.zshrc, sesuai shell yang dipakai
wasmer --version   # pastikan terpasang
wasmer login       # sambungkan ke akun Wasmer
```

---

## 3. Isi `wasmer.toml` (final, yang sudah terbukti jalan)

File ini ada di root project, sejajar dengan `artisan`, `composer.json`, dll.

```toml
[package]
name = "hilmyfausta/harvesta"
version = "0.1.8"
description = "Harvesta - Tree & Orchard Management System"

[dependencies]
"php/php" = "=8.3.4"

[fs]
"/app" = "."

[[command]]
name = "start"
module = "php/php:php"
runner = "wasi"

[command.annotations.wasi]
main-args = ["-c", "/app/php.ini", "-t", "/app/public", "-S", "0.0.0.0:8080", "/app/wasmer-router.php"]
```

Penjelasan tiap bagian:
- `[dependencies] "php/php" = "=8.3.4"` — nentuin interpreter PHP yang jalanin kode di runtime. **Ini terpisah total dari versi PHP yang dipakai buat `composer install`** (lihat bagian 6, ini penting).
- `[fs] "/app" = "."` — mount seluruh folder project lokal (termasuk `vendor/` hasil `composer install` lokal) ke path `/app` di sandbox WASI. Wasmer **tidak** menjalankan `composer install` atau build step apapun sendiri — semua file yang dibutuhkan harus sudah ada duluan di folder lokal sebelum `wasmer deploy`.
- `main-args` — argumen buat PHP built-in server. Urutan pentingnya: `-c` (php.ini custom), `-t` (document root = `public/`), `-S` (host:port), lalu **path router script** di paling akhir (lihat bagian 5, ini kunci dari bug UI nggak muncul).

### Versi naik otomatis tiap deploy
Setiap `wasmer deploy`, kalau isi file berubah tapi `version` di `[package]` nggak diubah manual, Wasmer bakal nawarin bump otomatis (`0.1.7 -> 0.1.8`, dst) — tinggal jawab `y`. Nggak wajib diubah manual, tapi nomor versi di file ini akan ketinggalan dari versi yang beneran ke-deploy (cek versi aktual di dashboard atau `wasmer app list`/riwayat `Releases`).

---

## 4. Database: PostgreSQL bawaan Wasmer

Database ini di-provision otomatis sama Wasmer saat app pertama kali dibuat, nggak perlu setup manual dari awal.

Cara lihat kredensialnya: buka dashboard app → tab **Databases** (`https://wasmer.io/apps/hilmyfausta/harvesta/databases`).

| Field | Nilai |
|---|---|
| Host | `psql.fr-roub1.<...>.wasmernet.com` (lihat dashboard) |
| Port | lihat dashboard |
| Database name | lihat dashboard (bukan `postgres`, lihat 5.1) |
| Username | lihat dashboard |
| Password | tombol mata di dashboard, atau `wasmer app secrets reveal DB_PASSWORD` |

Nilai aslinya sengaja tidak ditulis di sini karena repo ini publik.

**Lokasi fisik database:** `fr-roub1` = Roubaix, Prancis (konvensi penamaan data center ala OVHcloud). Ini jauh dari Indonesia — berpengaruh ke kecepatan kalau akses langsung dari laptop (lihat bagian 8).

### Mengelola environment variables / secrets

Laravel baca kredensial DB dari env var standar: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Di Wasmer, env var di-set lewat `wasmer app secrets`, **bukan** file `.env` yang di-deploy (`.env` tetap dipakai lokal aja, jangan ikut ke-commit/ke-deploy).

```bash
# Lihat semua secrets (TANPA value, cuma nama + kapan terakhir update)
wasmer app secrets list

# Lihat SEMUA value sekaligus (hati-hati, ini nampilin semua secret termasuk password)
wasmer app secrets reveal --all

# Lihat satu secret spesifik
wasmer app secrets reveal DB_DATABASE

# Bikin secret BARU (kalau namanya belum pernah ada)
wasmer app secrets create NAMA_SECRET "nilainya"

# Update secret yang SUDAH ADA (pakai update, bukan create, atau bakal error)
wasmer app secrets update NAMA_SECRET "nilai_baru"

# Hapus secret
wasmer app secrets delete NAMA_SECRET

# Setelah ubah secret apapun, WAJIB redeploy biar kepakai
wasmer deploy
```

Daftar env var yang dipakai project ini (nama saja, value jangan ditaruh di dokumen ini):
```
APP_NAME, APP_ENV, APP_DEBUG, APP_KEY, APP_LOCALE, APP_FALLBACK_LOCALE, APP_TIMEZONE
DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_SSLMODE
CACHE_STORE, SESSION_DRIVER, QUEUE_CONNECTION, LOG_CHANNEL, LOG_LEVEL
PHOTO_DISK, PHOTO_DISK_ROOT, PHOTO_DISK_URL
PHOTO_S3_ACCESS_KEY_ID, PHOTO_S3_SECRET_ACCESS_KEY, PHOTO_S3_BUCKET, PHOTO_S3_ENDPOINT, PHOTO_S3_REGION, PHOTO_S3_USE_PATH_STYLE_ENDPOINT
SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD, SEED_STAFF_EMAIL, SEED_STAFF_PASSWORD, SEED_VIEWER_EMAIL, SEED_VIEWER_PASSWORD, SEED_DEMO_DATA
MAIL_MAILER, PHP_CLI_SERVER_WORKERS
```

---

## 5. Bug & Fix yang ditemukan (urut kronologis)

### 5.1 — Salah nama database (`DB_DATABASE` nyasar isinya)

**Gejala:** error 500, log nunjukin:
```
FATAL: permission denied for database "postgres"
DETAIL: User does not have CONNECT privilege.
```

**Penyebab:** waktu ganti dari eksperimen Supabase ke database bawaan Wasmer, `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` sempat diupdate ke kredensial Wasmer, tapi `DB_DATABASE` ketinggalan masih isi lama (`postgres`, gaya penamaan default Supabase) — padahal nama database yang beneran di-provision Wasmer berbeda (lihat dashboard). Selain itu sempat kebuat secret baru namanya `DB_NAME` (bukan `DB_DATABASE`) yang nggak kebaca Laravel sama sekali (`config/database.php` baca `env('DB_DATABASE')`).

**Fix:**
```bash
wasmer app secrets update DB_DATABASE <nama_database_dari_dashboard>
wasmer app secrets delete DB_NAME   # hapus yang nyasar
wasmer deploy
```

### 5.2 — Auto-migration di `bootstrap/wasmer-init.php` gagal diam-diam

**Gejala:** setelah fix 5.1, error baru:
```
SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "sessions" does not exist
```
Database kebaca sukses, tapi tabel-tabelnya nggak pernah ke-create — padahal ada script auto-migrate.

**Penyebab:** project ini punya file `bootstrap/wasmer-init.php` yang didesain buat auto-jalanin `php artisan migrate` & `db:seed` di boot pertama, lewat `exec('php artisan migrate --force ...')`. Masalahnya, `exec()` buat spawn subprocess baru **nggak bisa diandalkan di sandbox WASI** — command `php` nggak ketemu di situ, jadi `exec()` gagal total (output kosong, log cuma nunjukin `Migration output: ` tanpa isi). Ini juga konsisten sama `Instance exited with code ExitCode::127` yang muncul di log (127 = "command not found" di shell).

**Fix:** migrate manual **dari laptop langsung ke database remote** (database Wasmer bisa diakses dari luar, nggak perlu lewat sandbox):
```bash
# 1. Backup .env lokal dulu
cp .env .env.local.backup   # INGAT: file ini JANGAN sampai ke-commit ke git (ada secret di dalamnya)

# 2. Ganti sementara DB_* di .env ke kredensial database Wasmer (lihat bagian 4)

# 3. Migrate & seed langsung dari laptop (native PHP, bukan exec() dari sandbox)
php artisan migrate --force
php artisan db:seed --force

# 4. Kembalikan .env lokal ke semula
cp .env.local.backup .env
rm .env.local.backup   # hapus setelah dipakai, JANGAN biarkan nyangkut
```
Catatan: proses ini **lambat** (migrate bisa beberapa menit) karena laptop di Indonesia connect langsung ke database di Prancis (`fr-roub1`), beda dari saat aplikasi jalan normal yang query-nya server-ke-server. Ini wajar, bukan tanda error.

Setelah tabel `migrations` ada, `wasmer-init.php` otomatis skip proses auto-migrate di boot berikutnya (dia cek `to_regclass('public.migrations')` dulu sebelum coba `exec()`), jadi masalah ini nggak keulang terus selama tabel nggak ke-drop.

### 5.3 — UI nggak ke-load (404 di asset milik package, bukan asset statis biasa)

**Gejala:** backend jalan (login, CRUD berhasil, redirect bener), tapi tampilan mentah tanpa CSS/JS. Log nunjukin:
```
[404]: GET /flux/flux.min.js?id=... - No such file or directory
[404]: GET /livewire-xxxxxxxx/livewire.min.js?id=... - No such file or directory
```

**Penyebab:** `/flux/flux.min.js` dan `/livewire-{hash}/livewire.min.js` itu **route virtual** yang didaftarkan oleh package Livewire & Flux sendiri (nge-stream file dari dalam `vendor/`, bukan file fisik di `public/`). PHP built-in server (`php -S`), kalau dijalankan **tanpa router script**, punya perilaku: request yang punya ekstensi dikenal (`.js`, `.css`, dll) langsung dicek keberadaan file fisiknya — kalau nggak ketemu, langsung 404 duluan **tanpa pernah manggil `index.php`**. Request tanpa ekstensi (`/dashboard`, `/login`) selalu fallback ke `index.php`, makanya itu kelihatan jalan normal.

**Fix langkah 1 (gagal):** nyoba pakai router bawaan Laravel (`vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`) — tapi script ini pakai `getcwd()` buat nebak lokasi `public/`, dan ternyata **`getcwd()` di sandbox WASI Wasmer nggak reliable** (balikin `/` bukan `/app/public`), jadi malah fatal error `require_once(//index.php)`.

**Fix final:** bikin router script sendiri, `wasmer-router.php` di root project, pakai path **absolut** (bukan `getcwd()`):
```php
<?php

$publicPath = '/app/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

require_once $publicPath.'/index.php';
```
Lalu daftarkan di `wasmer.toml` sebagai argumen terakhir `main-args` (lihat bagian 3). Setelah ini, request yang nggak ketemu file fisiknya tetap di-fallback ke `index.php`, jadi route virtual Livewire/Flux bisa kehandle normal.

---

## 6. Soal versi PHP: kenapa Laravel 13 (butuh PHP ≥8.4) bisa jalan di interpreter 8.3.4

Ini sempat jadi concern besar di awal eksperimen, tapi ternyata **non-issue**, karena cara kerja Wasmer:

Wasmer **tidak** menjalankan `composer install` di dalam sandbox-nya sama sekali. `wasmer.toml` cuma mounting folder project apa adanya (`[fs] "/app" = "."`), termasuk folder `vendor/` yang sudah di-build duluan **di laptop** pakai PHP lokal (PHP 8.4). Dependency `"php/php" = "8.3.4"` cuma nentuin interpreter yang menjalankan kode PHP di runtime — sama sekali terpisah dari versi PHP yang dipakai buat resolve dependency Composer.

Karena PHP itu bahasa interpreted (bukan dicompile), kode yang sudah berhasil di-resolve Composer tetap bisa dijalankan interpreter versi lain, **selama nggak ada syntax yang benar-benar eksklusif ke versi lebih baru** (contoh: property hooks PHP 8.4) yang kepake di code path yang dieksekusi. Untuk fitur-fitur yang dipakai project ini, ternyata aman.

**Implikasi:** `vendor/` yang di-mount ke Wasmer **harus** hasil `composer install` dari laptop sendiri (PHP 8.4 lokal), bukan dari dalam sandbox. Setiap kali ada perubahan dependency, jalankan `composer install`/`composer update` lokal dulu sebelum `wasmer deploy`.

---

## 7. Cara deploy ulang (update rutin)

### 7.1 Otomatis lewat GitHub Actions (cara utama)

Setiap push ke `main` menjalankan workflow `tests`. Kalau lulus, workflow `deploy` (`.github/workflows/deploy.yml`) langsung:
1. `composer install --no-dev` dan `pnpm run build` di runner GitHub;
2. mengubah `version` di `wasmer.toml` menjadi `0.2.<nomor run>` supaya setiap deploy punya versi unik tanpa perlu commit bump;
3. menjalankan `wasmer deploy --non-interactive` dengan secret `WASMER_TOKEN`.

Kalau test gagal, deploy tidak jalan. Deploy manual dari GitHub juga bisa: tab **Actions** → **deploy** → **Run workflow**.

Setup sekali (oleh pemilik repo):
- Buat token di **wasmer.io → Settings → Access tokens**.
- Simpan di GitHub: **Settings → Secrets and variables → Actions → New repository secret**, nama `WASMER_TOKEN`.
- Putuskan integrasi GitHub bawaan Wasmer (dashboard app → pengaturan Git/GitHub). Integrasi itu mencoba build dari repo yang tidak berisi `vendor/` dan `public/build/`, jadi selalu gagal dan memunculkan check merah.

Migration **tidak** ikut jalan otomatis. Kalau ada migration baru, jalankan dulu ke database Wasmer (bagian 5.2) sebelum push.

### 7.2 Manual dari laptop (cadangan)

```bash
# 1. Ambil kode terbaru
git pull

# 2. Pastikan vendor/ dan public/build/ up to date
composer install
pnpm install
pnpm run build

# 3. Matikan dev server (composer run dev / pnpm dev) dan pastikan file public/hot tidak ada.
#    Kalau ikut ter-upload, Laravel mencari CSS/JS ke localhost dan tampilan rusak.
#    (public/hot juga sudah masuk .wasmerignore sebagai pengaman.)
rm -f public/hot

# 4. Kalau ada migration baru, jalankan ke database Wasmer (lihat bagian 5.2)

# 5. Deploy
wasmer deploy
# Kalau ada warning soal version bump, jawab "y"

# 6. Tunggu sampai "deployed successfully", lalu test akses
# https://harvesta.wasmer.app
```

---

## 8. Known limitations / hal yang perlu diterima apa adanya

- **Latensi geografis:** database di Prancis (`fr-roub1`), app Wasmer Edge entah di region mana persis — kalau dirasa lemot, ini kemungkinan besar penyebabnya, bukan bug.
- **Nggak ada akses SSH/shell ke sandbox** (`wasmer app ssh` tidak tersedia di CLI versi ini) — semua debugging harus lewat `wasmer app secrets`, log dashboard, dan deploy ulang. Nggak bisa masuk terminal interaktif ke instance yang jalan.
- **`exec()`/subprocess spawning nggak reliable** di sandbox WASI — jangan desain fitur apapun ke depannya yang bergantung pada itu (termasuk kalau mau benerin `wasmer-init.php` biar auto-migrate beneran jalan — perlu pendekatan lain, bukan `exec()`).
- **Setiap migration baru** di masa depan (kalau ada field/tabel tambahan) **harus di-apply manual** dengan cara yang sama kayak bagian 5.2 — connect `.env` lokal ke database Wasmer, `php artisan migrate --force`, balikin `.env`. Nggak ada mekanisme otomatis yang bisa diandalkan saat ini.

---

## 9. Cara reset total database dari awal (kalau suatu saat perlu)

**Hati-hati — ini destruktif, drop semua data.** Hanya dilakukan kalau memang sengaja mau mulai dari nol lagi (misal demo ulang, atau data kacau).

```bash
# 1. Arahkan .env lokal ke database Wasmer (seperti langkah 5.2)
cp .env .env.local.backup
# edit .env, isi DB_* dengan kredensial Wasmer (bagian 4)

# 2. Drop semua tabel & migrate ulang dari nol
php artisan migrate:fresh --force

# 3. Seed ulang data demo
php artisan db:seed --force

# 4. Kembalikan .env lokal
cp .env.local.backup .env
rm .env.local.backup
```

Opsional: kalau foto-foto di Supabase Storage juga mau direset bareng, itu di luar jangkauan `migrate:fresh` — perlu dihapus manual lewat dashboard Supabase Storage atau lewat seeder yang upload ulang (`DemoDataSeeder`, kalau ada logic upload foto di situ).

---

## 10. Checklist sebelum demo/presentasi pakai Wasmer

- [ ] `wasmer app secrets list` — pastikan semua secret `DB_*` sudah benar (terutama `DB_DATABASE`)
- [ ] Akses `https://harvesta.wasmer.app` dari browser biasa (bukan incognito, biar nggak kena isu cache lama) beberapa menit sebelum demo — biar instance "panas" duluan
- [ ] Cek halaman login, dashboard, dan minimal satu CRUD (misal Categories) beneran nampilin styling normal
