# Panduan Deploy: Wasmer Edge + Supabase (Gratis)

> **Arsitektur deployment alternatif untuk Harvesta menggunakan Wasmer Edge**

## Arsitektur

```
Developer ──► Wasmer CLI (deploy) ──► Wasmer Edge (Global CDN)
                                         │
                                         ├──► Supabase PostgreSQL (data)
                                         └──► Supabase Storage S3 (foto pohon)
```

**Keuntungan:**
- ✅ 100% gratis untuk kedua service (Wasmer Free tier + Supabase Free tier)
- ✅ Tidak butuh kartu kredit
- ✅ Global CDN dengan edge computing
- ✅ Deploy langsung dari CLI, tanpa GitHub Actions
- ✅ Data demo dengan 3 akun: Admin (full access), Staff (field operations), Viewer (read-only untuk demo)

**Keterbatasan:**
- ⚠️ Wasmer hanya menyediakan PHP 8.3.4 (bukan 8.4), butuh downgrade composer dependencies
- ⚠️ Region PostgreSQL terbatas ke France atau Canada (bukan Singapore)
- ⚠️ Cold start bisa 5-10 detik untuk request pertama

---

## Persiapan

**Yang dibutuhkan:**
1. Akun GitHub (untuk login Supabase)
2. Akun Wasmer (gratis, sign up dengan GitHub)
3. Wasmer CLI terinstall di laptop
4. Akses internet stabil (upload ±50 MB saat deploy pertama)

**Estimasi waktu:** 30-45 menit untuk setup pertama kali.

---

## Bagian A — Supabase (Database + Storage Foto)

### A1. Buat Project Supabase

1. Buka <https://supabase.com> → **Sign in with GitHub**
2. Klik **New project**:
   - **Organization**: pilih/buat organization Anda
   - **Name**: `harvesta`
   - **Database Password**: klik **Generate a password** → 📋 **SIMPAN** password ini (Anda akan butuh nanti sebagai `DB_PASSWORD`)
   - **Region**: **Southeast Asia (Singapore)** (untuk latency optimal)
   - **Plan**: Free (1 project, 500 MB database, 1 GB storage)
3. Klik **Create new project** → tunggu ±2 menit sampai status "Healthy"

### A2. Ambil Data Koneksi Database

1. Di dashboard project, klik tombol **Connect** (pojok kanan atas)
2. Pilih **Session pooler** (BUKAN "Direct connection")
   - Wasmer Edge butuh IPv4, jadi gunakan pooler
3. 📋 Catat informasi berikut dari connection string:
   ```
   postgresql://postgres.[PROJECT-REF]:[PASSWORD]@[HOST]:5432/postgres
   ```
   - `HOST` → `DB_HOST` (contoh: `aws-0-ap-southeast-1.pooler.supabase.com`)
   - `postgres.[PROJECT-REF]` → `DB_USERNAME` (dengan titik dan kode project, contoh: `postgres.abcdefghijklmnop`)
   - Password yang Anda generate tadi → `DB_PASSWORD`
   - Port: `5432` (default)
   - Database: `postgres` (default)

4. 📋 Catat juga **Project Reference Code** (kode acak di URL):
   ```
   https://supabase.com/dashboard/project/[PROJECT-REF]
   ```
   Simpan `[PROJECT-REF]` ini, butuh untuk storage S3.

### A3. Buat Bucket Storage untuk Foto

1. Di menu kiri, klik **Storage**
2. Klik **New bucket**:
   - **Name**: `harvesta-photos` (harus persis seperti ini)
   - **Public bucket**: ✅ **AKTIFKAN** (agar foto bisa diakses publik)
   - **Restrict file size**: `2 MB` (opsional tapi disarankan)
   - **Allowed MIME types**: `image/jpeg, image/png, image/webp` (opsional)
3. Klik **Save**

### A4. Buat S3 Access Keys untuk Storage

1. Klik **Project Settings** (ikon gear di kiri bawah) → tab **Storage**
2. Scroll ke bagian **S3 Connection**
3. Pastikan toggle **Enable connection via S3 protocol** dalam keadaan **ON**
4. 📋 Catat informasi berikut:
   - **Endpoint**: `https://[PROJECT-REF].storage.supabase.co/storage/v1/s3` → `PHOTO_S3_ENDPOINT`
   - **Region**: `ap-southeast-1` (biasanya) → `PHOTO_S3_REGION`

5. Scroll ke bagian **S3 Access Keys** → klik **New access key**:
   - **Description**: `wasmer-edge`
   - Klik **Create**
   - 📋 **SEGERA SIMPAN** kedua value ini (hanya ditampilkan sekali):
     - **Access key ID** → `PHOTO_S3_ACCESS_KEY_ID`
     - **Secret access key** → `PHOTO_S3_SECRET_ACCESS_KEY`

6. 📋 Susun URL publik bucket (untuk akses foto):
   ```
   https://[PROJECT-REF].supabase.co/storage/v1/object/public/harvesta-photos
   ```
   Ganti `[PROJECT-REF]` dengan kode project Anda → simpan sebagai `PHOTO_DISK_URL`

**Checklist Supabase:**
- ✅ Project created
- ✅ DB_HOST, DB_USERNAME, DB_PASSWORD tersimpan
- ✅ Bucket `harvesta-photos` dibuat (public)
- ✅ S3 credentials (access key + secret) tersimpan
- ✅ PHOTO_DISK_URL tersusun

---

## Bagian B — Setup Wasmer CLI

### B1. Install Wasmer CLI

**Linux / macOS:**
```bash
curl https://get.wasmer.io -sSfL | sh
```

**Windows (PowerShell sebagai Administrator):**
```powershell
iwr https://win.wasmer.io -useb | iex
```

**Verifikasi:**
```bash
wasmer --version
# Output: wasmer 5.x.x (atau lebih tinggi)
```

### B2. Login ke Wasmer

```bash
wasmer login
```

- Browser akan terbuka → login dengan GitHub
- Setelah berhasil, CLI akan menyimpan token otomatis
- Tes: `wasmer whoami` (harus muncul username Anda)

---

## Bagian C — Persiapan Kode untuk Deploy

### C1. Generate APP_KEY

Di folder project Harvesta:

```bash
php artisan key:generate --show
```

📋 Salin output lengkapnya (format: `base64:...`) → simpan sebagai `APP_KEY`

**PENTING:** Gunakan `--show` agar tidak mengubah file `.env` lokal Anda.

### C2. Tentukan Password Seeder

Aplikasi akan membuat 3 akun otomatis saat pertama kali deploy:

1. **Admin** (`admin@example.com`) — akses penuh ke semua fitur
2. **Staff** (`staff@example.com`) — field operations (CRUD trees, care logs, harvests)
3. **Viewer** (`viewer@example.com`) — **read-only** untuk demo ke penilai/dosen

📋 Buat 3 password yang **BERBEDA dan AMAN** (minimal 8 karakter):
- `SEED_ADMIN_PASSWORD` (contoh: `Admin2024!Secure`)
- `SEED_STAFF_PASSWORD` (contoh: `Staff2024!Field`)
- `SEED_VIEWER_PASSWORD` (contoh: `Viewer2024!Demo`)

⚠️ **Jangan gunakan password default "password" di production!**

### C3. Simpan Semua Secrets ke Wasmer

Sekarang kita akan memasukkan semua nilai rahasia (secrets) ke Wasmer. Copy-paste command berikut **satu per satu**, ganti `<nilai>` dengan data Anda:

```bash
# APP_KEY
wasmer app secrets create APP_KEY "<base64:xxx dari C1>"

# Database credentials (dari A2)
wasmer app secrets create DB_HOST "<dari A2>"
wasmer app secrets create DB_PORT "5432"
wasmer app secrets create DB_DATABASE "postgres"
wasmer app secrets create DB_USERNAME "<postgres.xxx dari A2>"
wasmer app secrets create DB_PASSWORD "<password dari A1>"

# Photo storage S3 (dari A4)
wasmer app secrets create PHOTO_S3_ENDPOINT "<dari A4>"
wasmer app secrets create PHOTO_S3_REGION "<dari A4>"
wasmer app secrets create PHOTO_DISK_URL "<dari A4 langkah 6>"
wasmer app secrets create PHOTO_S3_ACCESS_KEY_ID "<dari A4>"
wasmer app secrets create PHOTO_S3_SECRET_ACCESS_KEY "<dari A4>"

# Seeder passwords (dari C2)
wasmer app secrets create SEED_ADMIN_PASSWORD "<password admin Anda>"
wasmer app secrets create SEED_STAFF_PASSWORD "<password staff Anda>"
wasmer app secrets create SEED_VIEWER_PASSWORD "<password viewer Anda>"
```

**Verifikasi:**
```bash
wasmer app secrets list
```

Anda harus melihat 13 secrets (nilai actual tidak ditampilkan untuk keamanan).

---

## Bagian D — Deploy Aplikasi

### D1. Cek Konfigurasi

Pastikan file-file ini sudah ada dan benar:

**File: `app.yaml`** (sudah ada di root project)
```yaml
kind: wasmer.io/App.v0
name: harvesta
owner: hilmyfausta  # ⚠️ GANTI dengan username Wasmer Anda!
package: .
# ... (sisanya sudah dikonfigurasi)
```

⚠️ **PENTING:** Edit `app.yaml` dan ganti `owner: hilmyfausta` dengan username Wasmer Anda!

**File: `wasmer.toml`** (sudah ada di root project)
```toml
[package]
name = "hilmyfausta/harvesta"  # ⚠️ GANTI username!
# ... (sisanya sudah dikonfigurasi)
```

⚠️ **PENTING:** Edit `wasmer.toml` dan ganti `hilmyfausta/harvesta` dengan `<username-anda>/harvesta`!

### D2. Deploy ke Wasmer Edge

Di folder root project:

```bash
wasmer deploy
```

**Proses yang terjadi:**
1. ✅ Wasmer membaca `app.yaml` dan `wasmer.toml`
2. ✅ Meng-package seluruh folder project (±50 MB)
3. ✅ Upload ke Wasmer Edge
4. ✅ Deploy ke region yang dipilih (France)
5. ✅ Menjalankan PHP built-in server
6. ✅ Memberikan URL publik (contoh: `https://harvesta-hilmyfausta.wasmer.app`)

**Output sukses:**
```
✅ App deployed successfully!
🌍 Available at: https://harvesta-<username>.wasmer.app
```

📋 Simpan URL ini untuk akses aplikasi.

### D3. Jalankan Migrasi Database (Pertama Kali)

⚠️ **Wasmer tidak menjalankan migrasi otomatis**, jadi kita harus trigger manual.

**Cara 1: SSH ke container (recommended):**
```bash
wasmer ssh harvesta
# Di dalam container:
cd /app
php artisan migrate --force
php artisan db:seed --force
exit
```

**Cara 2: Buat script helper (jika SSH tidak tersedia):**

Buat file `routes/web.php` tambahan route admin untuk trigger seeder (hanya sekali):

```php
// TEMPORARY - hapus setelah selesai!
Route::get('/admin-setup-db', function () {
    if (app()->environment('production') && request()->ip() !== 'YOUR_IP') {
        abort(403);
    }
    
    \Artisan::call('migrate', ['--force' => true]);
    \Artisan::call('db:seed', ['--force' => true]);
    
    return 'Database setup complete!';
})->middleware('throttle:1,60'); // max 1 request per jam
```

Akses: `https://harvesta-<username>.wasmer.app/admin-setup-db`

⚠️ **HAPUS route ini segera setelah setup selesai!**

---

## Bagian E — Verifikasi & Testing

### E1. Cek Aplikasi Berjalan

1. Buka URL aplikasi: `https://harvesta-<username>.wasmer.app`
2. Anda akan diredirect ke halaman login

### E2. Login dengan 3 Akun Seeder

**1. Akun Admin (Full Access):**
```
Email: admin@example.com
Password: <SEED_ADMIN_PASSWORD Anda>
```

Setelah login:
- ✅ Dashboard menampilkan statistik lengkap (termasuk finance)
- ✅ Menu sidebar lengkap: Trees, Care Logs, Harvests, Master Data, Finance, Reports, Users
- ✅ Bisa CRUD semua data

**2. Akun Staff (Field Operations):**
```
Email: staff@example.com
Password: <SEED_STAFF_PASSWORD Anda>
```

Setelah login:
- ✅ Dashboard menampilkan statistik (tanpa finance)
- ✅ Menu sidebar: Trees, Care Logs, Harvests, Master Data (tanpa Finance, Reports, Users)
- ✅ Bisa create/edit trees, care logs, harvests
- ✅ Tidak bisa delete atau akses finance/users

**3. Akun Viewer (Read-Only untuk Demo):**
```
Email: viewer@example.com
Password: <SEED_VIEWER_PASSWORD Anda>
```

Setelah login:
- ✅ Dashboard menampilkan statistik (tanpa finance)
- ✅ Menu sidebar: Trees, Care Logs, Harvests, Master Data
- ✅ **HANYA BISA MELIHAT** (tombol Create/Edit/Delete tidak muncul)
- ✅ Cocok untuk demo ke penilai/dosen tanpa khawatir data berubah

### E3. Tes Upload Foto Pohon

1. Login sebagai **Admin** atau **Staff**
2. Menu **Trees** → klik salah satu pohon → **Edit**
3. Upload foto baru (max 2 MB, format JPG/PNG/WebP)
4. Klik **Save changes**
5. Foto harus muncul sebagai thumbnail di daftar dan besar di halaman detail
6. Cek di Supabase dashboard → Storage → `harvesta-photos` → folder `photos/trees/` harus ada file baru

### E4. Cek Data Demo

Seeder sudah mengisi data demo:
- ✅ 4 categories (Fruit, Timber, Ornamental, Spice)
- ✅ 10 tree types (Durian, Mango, Teak, dll.)
- ✅ 3 blocks
- ✅ 40 trees dengan foto
- ✅ 100+ care logs
- ✅ 40 harvests
- ✅ 30 expenses (hanya admin yang bisa lihat)
- ✅ 20 sales (hanya admin yang bisa lihat)
- ✅ Data tersebar 12 bulan terakhir (grafik terlihat hidup)

---

## Bagian F — Update & Re-deploy

### F1. Update Kode

Setelah ada perubahan kode di laptop:

```bash
# Test lokal dulu
composer test  # Pint + PHPStan + PHPUnit

# Deploy ulang
wasmer deploy
```

Wasmer akan:
1. ✅ Re-package project terbaru
2. ✅ Upload versi baru
3. ✅ Hot reload tanpa downtime
4. ✅ Database dan foto tetap aman (tidak berubah)

⚠️ **Migrasi database baru harus dijalankan manual via SSH.**

### F2. Update Secrets

Jika perlu ganti password atau credentials:

```bash
# Lihat daftar secrets
wasmer app secrets list

# Update secret tertentu
wasmer app secrets update SEED_ADMIN_PASSWORD "<password-baru>"

# Hapus secret (jika salah)
wasmer app secrets delete SEED_ADMIN_PASSWORD
wasmer app secrets create SEED_ADMIN_PASSWORD "<password-benar>"

# Restart app agar secrets baru dimuat
wasmer app restart harvesta
```

---

## Troubleshooting

### ❌ Error: "Connection refused" atau "Could not connect to database"

**Penyebab:**
- DB_HOST, DB_USERNAME, atau DB_PASSWORD salah
- Supabase project di-pause (free tier pause setelah 1 minggu tidak aktif)

**Solusi:**
1. Buka Supabase dashboard → pastikan project status "Healthy"
2. Jika di-pause, klik "Resume project"
3. Verifikasi secrets: `wasmer app secrets list`
4. Tes koneksi lokal dulu:
   ```bash
   psql "postgresql://postgres.xxx:password@host:5432/postgres?sslmode=require"
   ```

### ❌ Error: "Class 'League\Flysystem\AwsS3V3\AwsS3V3Adapter' not found"

**Penyebab:**
Package S3 belum terinstall.

**Solusi:**
```bash
composer require league/flysystem-aws-s3-v3
composer install --no-dev --optimize-autoloader
wasmer deploy
```

### ❌ Foto tidak muncul / Error 403 saat akses foto

**Penyebab:**
- Bucket tidak public
- PHOTO_DISK_URL salah
- S3 credentials salah

**Solusi:**
1. Supabase → Storage → `harvesta-photos` → **Make public** (jika belum)
2. Test URL manual: `https://[PROJECT-REF].supabase.co/storage/v1/object/public/harvesta-photos/test.jpg`
3. Verifikasi semua PHOTO_S3_* secrets di Wasmer

### ❌ Aplikasi lambat / cold start 10+ detik

**Penyebab:**
Free tier Wasmer sleep setelah idle.

**Solusi:**
- Normal untuk free tier
- Request pertama akan lambat (cold start)
- Request berikutnya cepat (warm)
- Untuk demo: buka aplikasi 2-3 menit sebelum presentasi

### ❌ Error: "Migration table not found"

**Penyebab:**
Migrasi belum dijalankan.

**Solusi:**
```bash
wasmer ssh harvesta
php artisan migrate --force
php artisan db:seed --force
```

### ❌ Login gagal / "These credentials do not match our records"

**Penyebab:**
- Seeder belum dijalankan
- Password secrets salah

**Solusi:**
1. Cek apakah tabel `users` ada di Supabase:
   ```sql
   SELECT email, role FROM users;
   ```
2. Jika kosong, run seeder:
   ```bash
   wasmer ssh harvesta
   php artisan db:seed --force --class=UserSeeder
   ```
3. Verifikasi password secrets: `wasmer app secrets list`

### ❌ Menu Finance/Users/Reports muncul untuk Staff/Viewer

**Penyebab:**
Policy tidak diterapkan dengan benar.

**Solusi:**
Cek file `resources/views/components/layout.blade.php` atau sidebar component, pastikan ada pengecekan:
```php
@can('viewAny', App\Models\Expense::class)
    <!-- Menu Finance -->
@endcan
```

---

## Monitoring & Logs

### Melihat Logs Aplikasi

```bash
wasmer app logs harvesta --follow
```

Atau via dashboard: <https://wasmer.io/apps/harvesta> → tab **Logs**

### Melihat Metrics

Dashboard Wasmer → tab **Metrics**:
- Request count
- Response time
- Error rate
- Memory usage

### Database Monitoring

Supabase dashboard → tab **Database**:
- Disk usage (max 500 MB free tier)
- Connection pooler stats
- Slow queries

---

## Checklist Deployment

Gunakan checklist ini sebelum submit tugas:

### Supabase
- [ ] Project created dan status "Healthy"
- [ ] Database credentials tersimpan dan terverifikasi
- [ ] Bucket `harvesta-photos` public
- [ ] S3 credentials tersimpan dan terverifikasi

### Wasmer
- [ ] CLI terinstall dan login berhasil
- [ ] `app.yaml` dan `wasmer.toml` sudah diedit (ganti username)
- [ ] 13 secrets sudah dibuat via `wasmer app secrets create`
- [ ] Deploy berhasil dan URL publik tersedia

### Aplikasi
- [ ] Migrasi database dijalankan
- [ ] Seeder dijalankan (3 akun + data demo)
- [ ] Login 3 akun berhasil (admin, staff, viewer)
- [ ] Upload foto berfungsi
- [ ] Dashboard menampilkan data
- [ ] Grafik/chart muncul
- [ ] Policy authorization bekerja (viewer tidak bisa edit)

### Untuk Demo/Laporan
- [ ] URL publik dicatat di laporan
- [ ] Password 3 akun disimpan di tempat aman (jangan di repo!)
- [ ] Screenshot semua fitur diambil
- [ ] Tested di browser berbeda (Chrome, Firefox, Safari)
- [ ] Tested di mobile (responsive)

---

## Tips untuk Demo ke Penilai/Dosen

1. **Gunakan akun Viewer untuk demo:**
   - Berikan email `viewer@example.com` dan passwordnya
   - Mereka bisa explore semua fitur tanpa mengubah data

2. **Siapkan backup credentials:**
   - Print/screenshot password 3 akun
   - Jika lupa, bisa reset via SSH

3. **Warm up sebelum presentasi:**
   - Buka aplikasi 5 menit sebelum demo
   - Klik beberapa menu (agar tidak cold start saat demo)

4. **Siapkan fallback:**
   - Record video demo (jika internet down)
   - Screenshot semua halaman penting
   - Catat semua URL dan credentials di notes

5. **Untuk laporan:**
   ```
   URL Deploy: https://harvesta-<username>.wasmer.app
   
   Akun Demo (Viewer):
   Email: viewer@example.com
   Password: [tulis di laporan]
   
   Akun Admin (untuk penilai yang ingin lihat full feature):
   Email: admin@example.com
   Password: [tulis di laporan]
   ```

---

## Perbandingan: Wasmer vs Azure

| Aspek | Wasmer Edge | Azure App Service |
|-------|-------------|-------------------|
| **Biaya** | 100% gratis | Free F1 (limited) atau B1 (~$13/bulan dari kredit student) |
| **Deploy** | CLI langsung | GitHub Actions |
| **Cold Start** | 5-10 detik | 2-3 menit (F1) |
| **Region** | France/Canada | Southeast Asia |
| **PHP Version** | 8.3.4 (fixed) | 8.4+ (custom) |
| **Setup** | Lebih cepat | Lebih kompleks |
| **Production Ready** | Cocok untuk demo | Cocok untuk production |

**Rekomendasi:**
- Gunakan **Wasmer** jika: budget nol, deploy cepat, demo saja
- Gunakan **Azure** jika: perlu PHP 8.4+, production serius, ada kredit student

---

## Kontributor & Support

Dokumentasi ini dibuat untuk tugas ETS Web Programming (D) — Midterm Exam.

**Jika ada masalah:**
1. Cek Troubleshooting section di atas
2. Baca docs resmi: <https://docs.wasmer.io> dan <https://supabase.com/docs>
3. Tanya di grup tim
4. Contact asisten dosen

**Good luck! 🚀**
