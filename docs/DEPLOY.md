# Panduan Deploy: Render + Supabase (gratis)

Arsitektur (PRD 10.1):

```
Browser ──► Render (Docker: PHP 8.4 + Laravel, region Singapore)
              ├──► Supabase PostgreSQL  (data)
              └──► Supabase Storage     (foto pohon, bucket publik)
```

Dikerjakan sekali oleh pemilik akun GitHub repo. Total ±20–30 menit. Siapkan satu file catatan pribadi (jangan di repo) untuk menyimpan nilai-nilai bertanda 📋.

---

## Bagian A — Supabase

### A1. Buat project
1. Buka <https://supabase.com> → **Sign in with GitHub**.
2. **New project**:
   - Name: `harvesta`
   - Database Password: klik **Generate a password** → 📋 simpan sebagai `DB_PASSWORD`.
   - Region: **Southeast Asia (Singapore)**.
   - Plan: Free.
3. Tunggu ±2 menit sampai project siap.

### A2. Ambil data koneksi database
1. Klik tombol **Connect** (di bagian atas dashboard project).
2. Pilih tab/opsi **Session pooler** (BUKAN "Direct connection" — Render tidak mendukung IPv6).
3. 📋 Catat dari parameter koneksinya:
   - `host` → `DB_HOST` (contoh: `aws-0-ap-southeast-1.pooler.supabase.com`)
   - `user` → `DB_USERNAME` (formatnya `postgres.<project-ref>`, **dengan** titik dan kode project)
   - port harus `5432`, database `postgres`.
4. 📋 Catat juga **project ref**: kode acak di URL dashboard, `https://supabase.com/dashboard/project/<project-ref>`.

### A3. Buat bucket foto
1. Menu kiri **Storage** → **New bucket**.
2. Name: `harvesta-photos` (harus persis).
3. Aktifkan **Public bucket**.
4. (Opsional) Restrict file size `2 MB`, allowed MIME types `image/jpeg, image/png, image/webp`.
5. **Save**.

### A4. Buat kunci S3
1. Menu **Project Settings** → **Storage** → bagian **S3 Connection**.
2. Pastikan **Enable connection via S3 protocol** menyala.
3. 📋 Catat **Endpoint** → `PHOTO_S3_ENDPOINT` (contoh `https://<project-ref>.storage.supabase.co/storage/v1/s3`).
4. 📋 Catat **Region** → `PHOTO_S3_REGION` (contoh `ap-southeast-1`).
5. Bagian **S3 Access Keys** → **New access key** → deskripsi `render` → **Create**.
6. 📋 Catat **Access key ID** → `PHOTO_S3_ACCESS_KEY_ID` dan **Secret access key** → `PHOTO_S3_SECRET_ACCESS_KEY`. Secret hanya ditampilkan sekali.
7. 📋 Susun `PHOTO_DISK_URL`:
   ```
   https://<project-ref>.supabase.co/storage/v1/object/public/harvesta-photos
   ```

---

## Bagian B — Render (Web Service manual, tanpa kartu kredit)

> Jangan pakai **New → Blueprint**: fitur Blueprint meminta kartu kredit. Web Service gratis yang dibuat manual tidak. `render.yaml` tetap di repo sebagai referensi daftar variabel.

### B1. Siapkan `APP_KEY`
Di laptop (folder proyek), jalankan:
```bash
php artisan key:generate --show
```
📋 Salin hasilnya (`base64:...`) → `APP_KEY`. Jangan dijalankan tanpa `--show` (itu mengubah `.env` lokal).

### B2. Buat Web Service
1. Pastikan kode terbaru sudah di `main` di GitHub.
2. Buka <https://render.com> → **Sign in with GitHub** (akun pemilik repo).
3. **New +** → **Web Service** → pilih repo ini (hubungkan GitHub bila diminta).
4. Isi form:
   - Name: `harvesta`
   - Language / Runtime: **Docker**
   - Branch: `main`
   - Region: **Singapore**
   - Root Directory: kosongkan
   - Instance Type: **Free**
5. Bagian **Environment Variables** → klik **Add from .env** → tempel blok di bawah, lalu ganti semua nilai `<...>`:

```dotenv
APP_NAME=Harvesta
APP_ENV=production
APP_DEBUG=false
APP_KEY=<dari B1>
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=stderr
LOG_LEVEL=warning
PHP_CLI_SERVER_WORKERS=4

DB_CONNECTION=pgsql
DB_HOST=<dari A2>
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=<dari A2, postgres.xxxx>
DB_PASSWORD=<dari A1>
DB_SSLMODE=require

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log

PHOTO_DISK=s3
PHOTO_DISK_ROOT=photos
PHOTO_DISK_URL=<dari A4 langkah 7>
PHOTO_S3_ENDPOINT=<dari A4>
PHOTO_S3_REGION=<dari A4>
PHOTO_S3_BUCKET=harvesta-photos
PHOTO_S3_ACCESS_KEY_ID=<dari A4>
PHOTO_S3_SECRET_ACCESS_KEY=<dari A4>
PHOTO_S3_USE_PATH_STYLE_ENDPOINT=true

SEED_ADMIN_EMAIL=admin@example.com
SEED_ADMIN_PASSWORD=<buat sendiri, bukan "password">
SEED_STAFF_EMAIL=staff@example.com
SEED_STAFF_PASSWORD=<buat sendiri>
SEED_DEMO_DATA=true
```

6. Buka **Advanced** → **Health Check Path**: `/up`. **Auto-Deploy**: On Commit (default).
7. **Deploy Web Service**.

### B3. Pantau deploy pertama
1. Buka service `harvesta` → tab **Logs**. Build Docker memakan ±5–10 menit.
2. Saat container start, log harus menunjukkan urutan:
   - `Running migrations` … `DONE`
   - `Seeding database` → `UserSeeder`, `MasterDataSeeder`, `DemoDataSeeder` … `DONE`
   - `Server running on [http://0.0.0.0:...]`
3. Status berubah menjadi **Live**. URL ada di bagian atas, contoh `https://harvesta.onrender.com` (bisa berakhiran acak bila nama sudah dipakai).

### B4. Cek hasil
1. Buka URL → login `admin@example.com` + `SEED_ADMIN_PASSWORD`.
2. Menu **Trees**: thumbnail foto harus tampil.
3. Supabase → Storage → `harvesta-photos` → folder `photos/trees/` berisi 32 file `t-00xx.jpg`.
4. Coba ubah satu pohon dan unggah foto baru → muncul di daftar dan di bucket.
5. Login `staff@example.com` + `SEED_STAFF_PASSWORD` → menu Finance/Users tidak muncul.

Setelah ini, setiap push ke `main` otomatis deploy ulang. Data dan foto tetap aman karena tersimpan di Supabase; seeder tidak menduplikasi data.

---

## Troubleshooting

| Gejala di log / browser | Penyebab dan solusi |
|-------------------------|---------------------|
| `could not translate host name` / `Network is unreachable` | Memakai *Direct connection*. Ganti `DB_HOST` ke host **Session pooler** (A2). |
| `password authentication failed` / `Tenant or user not found` | `DB_USERNAME` harus `postgres.<project-ref>`, bukan `postgres` saja. Cek juga `DB_PASSWORD`. |
| `Set SEED_ADMIN_PASSWORD before seeding in production.` | `SEED_ADMIN_PASSWORD`/`SEED_STAFF_PASSWORD` belum diisi di Render → Environment. |
| `APP_KEY is not set.` / `No application encryption key` | Isi `APP_KEY` dari B1 (harus diawali `base64:`). |
| Halaman tampil tanpa CSS | Hard refresh (Ctrl+F5). Bila tetap, cek log build bagian `pnpm run build`. |
| Ikon pohon (bukan foto) di semua pohon | Upload foto saat seeding gagal (lihat log: error S3). Perbaiki variabel `PHOTO_*`, lalu reset data (bawah). |
| Gambar rusak | Bucket belum **Public**, atau `PHOTO_DISK_URL` salah (harus berakhiran `/object/public/harvesta-photos`). |
| Request pertama lama (±1 menit) | Normal: service gratis tidur setelah 15 menit idle. Buka link sebelum demo. |
| Supabase "Project paused" | Project tidak aktif ±1 minggu. Dashboard Supabase → **Restore project**. |

**Reset data demo** (menghapus semua data produksi, lalu diisi ulang otomatis):
1. Supabase → **SQL Editor** → jalankan:
   ```sql
   drop schema public cascade;
   create schema public;
   grant all on schema public to postgres, anon, authenticated, service_role;
   ```
2. Supabase → Storage → `harvesta-photos` → hapus folder `photos`.
3. Render → service `harvesta` → **Manual Deploy** → **Restart service** (atau **Deploy latest commit**).

**Ganti password demo:** ubah `SEED_*_PASSWORD` di Render → Environment → Save. Render restart otomatis dan seeder memperbarui passwordnya. Catatan: password yang diubah lewat halaman Profile akan kembali ke nilai env setiap restart.
