# Panduan Deploy: Azure App Service + Supabase (gratis)

Arsitektur (PRD 10.1):

```
push ke main ─► GitHub Actions: tests ─► deploy (build image Docker)
                                            │
                                            ▼
                         GitHub Container Registry (ghcr.io/hilmyfausta/harvesta)
                                            │ di-pull
                                            ▼
Browser ──► Azure App Service (Linux container, Azure for Students)
              ├──► Supabase PostgreSQL  (data)
              └──► Supabase Storage     (foto pohon, bucket publik)
```

- Tidak butuh kartu kredit: Azure memakai akun **Azure for Students** (login email ITS), Supabase dan GitHub gratis.
- Dikerjakan sekali oleh pemilik repo (akun GitHub `HilmyFausta`). Total ±30–45 menit.
- Siapkan satu file catatan pribadi (jangan di repo) untuk menyimpan nilai bertanda 📋.

---

## Bagian A — Supabase (database + foto)

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
2. Pilih **Session pooler** (BUKAN "Direct connection", karena Azure App Service hanya keluar lewat IPv4).
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
5. Bagian **S3 Access Keys** → **New access key** → deskripsi `azure` → **Create**.
6. 📋 Catat **Access key ID** → `PHOTO_S3_ACCESS_KEY_ID` dan **Secret access key** → `PHOTO_S3_SECRET_ACCESS_KEY`. Secret hanya ditampilkan sekali.
7. 📋 Susun `PHOTO_DISK_URL`:
   ```
   https://<project-ref>.supabase.co/storage/v1/object/public/harvesta-photos
   ```

---

## Bagian B — Image Docker di GitHub

Workflow `.github/workflows/deploy.yml` otomatis mem-build image setiap `tests` sukses di `main`.

### B1. Pastikan image pertama sudah ada
1. Pastikan semua kode sudah di-push ke `main`.
2. GitHub repo → tab **Actions** → tunggu workflow **tests** lalu **deploy** selesai hijau (±5–10 menit).
   - Bila **deploy** tidak jalan sendiri: klik workflow **deploy** → **Run workflow** → branch `main`.
   - Langkah "Deploy to Azure App Service" memang **skipped** pada tahap ini (belum dikonfigurasi).
3. Cek di halaman repo → bagian kanan **Packages** → ada `harvesta`.

### B2. Buat token agar Azure bisa mengambil image
1. GitHub → foto profil → **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)** → **Generate new token (classic)**.
2. Note: `azure-ghcr`, Expiration: 90 days, centang **hanya** `read:packages`.
3. **Generate token** → 📋 simpan sebagai `GHCR_TOKEN` (hanya ditampilkan sekali).

### B3. Siapkan `APP_KEY`
Di laptop (folder proyek):
```bash
php artisan key:generate --show
```
📋 Salin hasilnya (`base64:...`) → `APP_KEY`. Wajib pakai `--show` (tanpa itu `.env` lokal ikut berubah).

---

## Bagian C — Azure App Service

### C1. Aktifkan Azure for Students
1. Buka <https://azure.microsoft.com/free/students> → login dengan email ITS.
2. Setelah aktif, buka <https://portal.azure.com>. Subscription bernama **Azure for Students**.

### C2. Buat Web App
1. Portal → **Create a resource** → **Web App** → **Create**.
2. Tab **Basics**:
   - Subscription: **Azure for Students**
   - Resource Group: **Create new** → `harvesta-rg`
   - Name: `harvesta-its` (harus unik global; URL jadi `https://<name>.azurewebsites.net`) → 📋 catat sebagai `AZURE_WEBAPP_NAME`
   - Publish: **Container**
   - Operating System: **Linux**
   - Region: **Southeast Asia**. Bila muncul error *RequestDisallowedByAzure* / region tidak diizinkan, coba **East Asia**, lalu **Central India** (akun student dibatasi beberapa region).
   - Pricing plan: **Create new** → pilih **Free F1**. (Bila F1 tidak tersedia atau aplikasi terlalu lambat, pilih **Basic B1**: ±US$13/bulan, dipotong dari kredit student US$100, bukan kartu.)
3. Tab **Container**:
   - Image Source: **Other container registries**
   - Access Type: **Private**
   - Registry server URL: `https://ghcr.io`
   - Username: username GitHub pemilik repo (`HilmyFausta`)
   - Password: `GHCR_TOKEN` dari B2
   - Image and tag: `hilmyfausta/harvesta:latest`
   - Port: `8080`
   - Startup command: kosongkan
4. Tab **Monitoring** (bila ada): Application Insights **No**.
5. **Review + create** → **Create**. Tunggu ±2 menit → **Go to resource**.

### C3. Isi variabel environment
1. Web App → menu **Settings** → **Environment variables** → tab **App settings** → **Advanced edit**.
2. Di dalam kurung siku `[...]` yang sudah ada, **tambahkan** blok berikut (jangan hapus entri `DOCKER_REGISTRY_*` bawaan; beri koma pemisah), lalu ganti semua `<...>`:

```json
  { "name": "WEBSITES_PORT", "value": "8080", "slotSetting": false },
  { "name": "WEBSITES_CONTAINER_START_TIME_LIMIT", "value": "600", "slotSetting": false },
  { "name": "APP_NAME", "value": "Harvesta", "slotSetting": false },
  { "name": "APP_ENV", "value": "production", "slotSetting": false },
  { "name": "APP_DEBUG", "value": "false", "slotSetting": false },
  { "name": "APP_KEY", "value": "<dari B3>", "slotSetting": false },
  { "name": "APP_TIMEZONE", "value": "Asia/Jakarta", "slotSetting": false },
  { "name": "APP_LOCALE", "value": "en", "slotSetting": false },
  { "name": "APP_FALLBACK_LOCALE", "value": "en", "slotSetting": false },
  { "name": "LOG_CHANNEL", "value": "stderr", "slotSetting": false },
  { "name": "LOG_LEVEL", "value": "warning", "slotSetting": false },
  { "name": "PHP_CLI_SERVER_WORKERS", "value": "4", "slotSetting": false },
  { "name": "DB_CONNECTION", "value": "pgsql", "slotSetting": false },
  { "name": "DB_HOST", "value": "<dari A2>", "slotSetting": false },
  { "name": "DB_PORT", "value": "5432", "slotSetting": false },
  { "name": "DB_DATABASE", "value": "postgres", "slotSetting": false },
  { "name": "DB_USERNAME", "value": "<dari A2, postgres.xxxx>", "slotSetting": false },
  { "name": "DB_PASSWORD", "value": "<dari A1>", "slotSetting": false },
  { "name": "DB_SSLMODE", "value": "require", "slotSetting": false },
  { "name": "SESSION_DRIVER", "value": "database", "slotSetting": false },
  { "name": "CACHE_STORE", "value": "database", "slotSetting": false },
  { "name": "QUEUE_CONNECTION", "value": "sync", "slotSetting": false },
  { "name": "MAIL_MAILER", "value": "log", "slotSetting": false },
  { "name": "PHOTO_DISK", "value": "s3", "slotSetting": false },
  { "name": "PHOTO_DISK_ROOT", "value": "photos", "slotSetting": false },
  { "name": "PHOTO_DISK_URL", "value": "<dari A4 langkah 7>", "slotSetting": false },
  { "name": "PHOTO_S3_ENDPOINT", "value": "<dari A4>", "slotSetting": false },
  { "name": "PHOTO_S3_REGION", "value": "<dari A4>", "slotSetting": false },
  { "name": "PHOTO_S3_BUCKET", "value": "harvesta-photos", "slotSetting": false },
  { "name": "PHOTO_S3_ACCESS_KEY_ID", "value": "<dari A4>", "slotSetting": false },
  { "name": "PHOTO_S3_SECRET_ACCESS_KEY", "value": "<dari A4>", "slotSetting": false },
  { "name": "PHOTO_S3_USE_PATH_STYLE_ENDPOINT", "value": "true", "slotSetting": false },
  { "name": "SEED_ADMIN_EMAIL", "value": "admin@example.com", "slotSetting": false },
  { "name": "SEED_ADMIN_PASSWORD", "value": "<buat sendiri, bukan 'password'>", "slotSetting": false },
  { "name": "SEED_STAFF_EMAIL", "value": "staff@example.com", "slotSetting": false },
  { "name": "SEED_STAFF_PASSWORD", "value": "<buat sendiri>", "slotSetting": false },
  { "name": "SEED_DEMO_DATA", "value": "true", "slotSetting": false }
```

3. **OK** → **Apply** → **Confirm**. App akan restart otomatis.

### C4. Aktifkan log dan health check
1. **Monitoring** → **App Service logs** → Application logging: **File System**, Retention: 1 hari → **Save**.
2. **Monitoring** → **Health check** → Enable, path `/up` → **Save** (bila menu tersedia di plan Anda).

### C5. Pantau start pertama
1. **Monitoring** → **Log stream**. Start pertama ±3–5 menit (pull image + migrate + seed + upload 32 foto).
2. Log harus menunjukkan berurutan: `Running migrations` → `Seeding database` (`UserSeeder`, `MasterDataSeeder`, `DemoDataSeeder`) → `Server running on [http://0.0.0.0:8080]`.
3. Buka `https://<AZURE_WEBAPP_NAME>.azurewebsites.net`.

### C6. Cek hasil
1. Login `admin@example.com` + `SEED_ADMIN_PASSWORD`.
2. Menu **Trees**: thumbnail foto harus tampil.
3. Supabase → Storage → `harvesta-photos` → folder `photos/trees/` berisi 32 file `t-00xx.jpg`.
4. Ubah satu pohon dan unggah foto baru → muncul di daftar dan di bucket.
5. Login `staff@example.com` + `SEED_STAFF_PASSWORD` → menu Finance/Users tidak muncul.

---

## Bagian D — Deploy otomatis setiap push (opsional tapi disarankan)

1. Azure Web App → **Settings** → **Configuration** → tab **General settings** → **SCM Basic Auth Publishing Credentials**: **On** → **Save**.
2. Web App → **Overview** → **Download publish profile** (file `.PublishSettings`).
3. GitHub repo → **Settings** → **Secrets and variables** → **Actions**:
   - Tab **Secrets** → **New repository secret** → Name `AZURE_WEBAPP_PUBLISH_PROFILE`, Value: **seluruh isi** file publish profile.
   - Tab **Variables** → **New repository variable** → Name `AZURE_WEBAPP_NAME`, Value: nama web app (C2).
4. Tes: GitHub → **Actions** → **deploy** → **Run workflow**. Langkah "Deploy to Azure App Service" sekarang harus hijau.

Mulai sekarang setiap push ke `main`: tests → build image → Azure memakai image baru. Data dan foto tetap aman di Supabase; seeder tidak menduplikasi data.

Tanpa Bagian D, deploy manual: tunggu workflow **deploy** hijau → Azure Web App → **Overview** → **Restart** (image `:latest` diambil ulang).

---

## Troubleshooting

| Gejala | Penyebab dan solusi |
|--------|---------------------|
| *RequestDisallowedByAzure* saat membuat Web App | Region tidak diizinkan akun student. Ganti region (Southeast Asia → East Asia → Central India). |
| Log: `unauthorized` / `pull access denied` untuk ghcr.io | `GHCR_TOKEN` salah/kedaluwarsa atau tanpa scope `read:packages`. Buat token baru, lalu Web App → **Deployment Center** → perbarui password registry. |
| Log: `Container didn't respond to HTTP pings on port: 8080` | `WEBSITES_PORT` belum `8080`, atau start terlalu lama: pastikan `WEBSITES_CONTAINER_START_TIME_LIMIT=600`. |
| `could not translate host name` / `Network is unreachable` | Memakai *Direct connection*. Ganti `DB_HOST` ke host **Session pooler** (A2). |
| `password authentication failed` / `Tenant or user not found` | `DB_USERNAME` harus `postgres.<project-ref>`. Cek juga `DB_PASSWORD`. |
| `APP_KEY is not set.` | Isi `APP_KEY` (B3, diawali `base64:`). |
| `Set SEED_ADMIN_PASSWORD before seeding in production.` | Isi `SEED_ADMIN_PASSWORD` dan `SEED_STAFF_PASSWORD`. |
| Site berhenti dengan pesan kuota (*Site Disabled / Quota exceeded*) | Plan Free F1 dibatasi 60 menit CPU/hari. Tunggu reset harian, atau **Scale up** ke **B1** (memakai kredit student). |
| Ikon pohon (bukan foto) di semua pohon | Upload foto saat seeding gagal (lihat Log stream: error S3). Perbaiki variabel `PHOTO_*`, lalu reset data (bawah). |
| Gambar rusak | Bucket belum **Public**, atau `PHOTO_DISK_URL` salah (harus berakhiran `/object/public/harvesta-photos`). |
| Request pertama lama | Normal: plan gratis tidur saat idle. Buka link ±2 menit sebelum demo. |
| Supabase "Project paused" | Tidak aktif ±1 minggu. Dashboard Supabase → **Restore project**. |

**Reset data demo** (menghapus semua data produksi, lalu diisi ulang otomatis):
1. Supabase → **SQL Editor** → jalankan:
   ```sql
   drop schema public cascade;
   create schema public;
   grant all on schema public to postgres, anon, authenticated, service_role;
   ```
2. Supabase → Storage → `harvesta-photos` → hapus folder `photos`.
3. Azure Web App → **Overview** → **Restart**.

**Ganti password demo:** ubah `SEED_*_PASSWORD` di Environment variables → Apply. App restart dan seeder memperbarui passwordnya. Password yang diubah lewat halaman Profile akan kembali ke nilai env setiap restart.
