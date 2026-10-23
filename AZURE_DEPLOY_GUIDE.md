# 🚀 Panduan Deploy Harvesta ke Azure App Service (Web UI)

**Estimasi waktu:** 30-40 menit
**Biaya:** ~$13/bulan (Basic B1) dari kredit student $70

---

## 📋 Informasi yang Sudah Siap

### Database Supabase (✅ Sudah Setup)
```
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.sbwgznlzluoyvguopzqu
DB_PASSWORD=FG8P0A3tu0gMAKO2
```

### Storage Supabase S3 (✅ Sudah Setup)
```
PHOTO_DISK_URL=https://sbwgznlzluoyvguopzqu.supabase.co/storage/v1/object/public/harvesta-photos
PHOTO_S3_ENDPOINT=https://sbwgznlzluoyvguopzqu.storage.supabase.co/storage/v1/s3
PHOTO_S3_REGION=ap-southeast-1
PHOTO_S3_BUCKET=harvesta-photos
PHOTO_S3_ACCESS_KEY_ID=1edb28d92c2be89b5b18baeb89611067
PHOTO_S3_SECRET_ACCESS_KEY=2042ecf8a874a4ad8edb48214f306405bfaf01d22dbca7eafb52e597595f58bd
```

### APP_KEY untuk Production
```
APP_KEY=base64:6l6p6p8YUj7oLOjLBByFeiyHYrXtkWj/pzuFdU8M6uE=
```

### Seed Passwords (✅ Dari .env)
```
SEED_ADMIN_PASSWORD=Hilmy080700!
SEED_STAFF_PASSWORD=Hilmy080700!
SEED_VIEWER_PASSWORD=password
```

---

## 🚀 STEP 1: Buat Resource Group

1. Buka **Azure Portal**: https://portal.azure.com
2. Klik **Resource groups** (atau search "Resource groups")
3. Klik **+ Create**
4. Isi form:
   - **Subscription:** Azure for Students
   - **Resource group:** `harvesta-rg`
   - **Region:** **West US** (paling stabil untuk student accounts)
5. Klik **Review + create** → **Create**

---

## 🚀 STEP 2: Buat Container Registry (ACR)

**Kenapa perlu ACR?** Docker image kita akan di-build dan disimpan di sini, lalu Azure Web App akan pull dari sini.

1. Di Azure Portal, search **"Container registries"**
2. Klik **+ Create**
3. Isi form:
   - **Subscription:** Azure for Students
   - **Resource group:** `harvesta-rg`
   - **Registry name:** `harvestaacr` (harus unique, lowercase, no dash)
   - **Location:** **West US**
   - **SKU:** **Basic** (cukup untuk production, ~$5/bulan)
4. Klik **Review + create** → **Create**
5. Tunggu deployment selesai (~1-2 menit)

### Enable Admin Access

6. Setelah selesai, klik **Go to resource**
7. Menu kiri → **Settings** → **Access keys**
8. **Enable** toggle "Admin user"
9. 📋 **CATAT** (akan dipakai nanti):
   - **Login server:** `harvestaacr.azurecr.io`
   - **Username:** `harvestaacr`
   - **Password:** (copy salah satu password)

---

## 🚀 STEP 3: Build Docker Image di ACR (Cloud Build)

**Ini akan build image di cloud tanpa perlu build lokal!**

Jalankan di terminal lokal:

```bash
cd /home/hilmyfausta/kuliah/semester_3/pemrograman_web/ets_webpro/harvesta

# Login ke ACR (gunakan password dari Step 2)
az acr login --name harvestaacr

# Build image di cloud (akan upload code dan build di Azure)
az acr build --registry harvestaacr --image harvesta:latest --image harvesta:v1.0.0 .
```

**Output yang diharapkan:**
```
Packing source code into tar to upload...
Uploading archived source code from '/tmp/...'
...
Run ID: ca1 was successful after 5m30s
```

**Waktu estimasi:** 5-10 menit (tergantung internet upload speed)

**Jika ada error "Authorization failed":** Kita bisa skip CLI dan upload via ZIP di portal:
1. ACR → **Tasks** → **Quick Task**
2. Upload Dockerfile + source code sebagai ZIP
3. Start build

---

## 🚀 STEP 4: Buat App Service Plan (B1)

1. Di Azure Portal, search **"App Service plans"**
2. Klik **+ Create**
3. Isi form:
   - **Subscription:** Azure for Students
   - **Resource group:** `harvesta-rg`
   - **Name:** `harvesta-plan`
   - **Operating System:** **Linux**
   - **Region:** **West US**
   - **Pricing tier:** Klik **Explore pricing plans** → pilih **Basic B1** (1 core, 1.75 GB RAM, ~$13/month)
4. Klik **Review + create** → **Create**

---

## 🚀 STEP 5: Buat Web App (Container)

1. Di Azure Portal, search **"App Services"**
2. Klik **+ Create** → **Web App**
3. Tab **Basics:**
   - **Subscription:** Azure for Students
   - **Resource group:** `harvesta-rg`
   - **Name:** `harvesta-its` (atau nama unik lain, akan jadi URL: `https://harvesta-its.azurewebsites.net`)
   - **Publish:** **Container**
   - **Operating System:** **Linux**
   - **Region:** **West US**
   - **Linux Plan:** `harvesta-plan` (yang baru dibuat di Step 4)

4. Tab **Container:**
   - **Image Source:** **Azure Container Registry**
   - **Registry:** `harvestaacr`
   - **Image:** `harvesta`
   - **Tag:** `latest`
   - **Startup Command:** (kosongkan)

5. Tab **Monitoring:**
   - **Enable Application Insights:** **No** (hemat biaya)

6. Klik **Review + create** → **Create**
7. Tunggu deployment (~2-3 menit)

---

## 🚀 STEP 6: Configure Environment Variables

Ini bagian PENTING! Tanpa ini aplikasi tidak akan jalan.

1. Setelah Web App dibuat, klik **Go to resource**
2. Menu kiri → **Settings** → **Environment variables**
3. Klik **+ Add** untuk setiap variable berikut (atau gunakan **Advanced edit** untuk paste JSON)

### Option A: Add Satu-satu

Klik **+ Add** untuk setiap variable:

| Name | Value |
|------|-------|
| `WEBSITES_PORT` | `8080` |
| `WEBSITES_CONTAINER_START_TIME_LIMIT` | `600` |
| `APP_NAME` | `Harvesta` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:6l6p6p8YUj7oLOjLBByFeiyHYrXtkWj/pzuFdU8M6uE=` |
| `APP_TIMEZONE` | `Asia/Jakarta` |
| `APP_LOCALE` | `en` |
| `APP_FALLBACK_LOCALE` | `en` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` |
| `PHP_CLI_SERVER_WORKERS` | `4` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `aws-0-ap-southeast-1.pooler.supabase.com` |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `postgres` |
| `DB_USERNAME` | `postgres.sbwgznlzluoyvguopzqu` |
| `DB_PASSWORD` | `FG8P0A3tu0gMAKO2` |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `MAIL_MAILER` | `log` |
| `PHOTO_DISK` | `s3` |
| `PHOTO_DISK_ROOT` | `photos` |
| `PHOTO_DISK_URL` | `https://sbwgznlzluoyvguopzqu.supabase.co/storage/v1/object/public/harvesta-photos` |
| `PHOTO_S3_ENDPOINT` | `https://sbwgznlzluoyvguopzqu.storage.supabase.co/storage/v1/s3` |
| `PHOTO_S3_REGION` | `ap-southeast-1` |
| `PHOTO_S3_BUCKET` | `harvesta-photos` |
| `PHOTO_S3_ACCESS_KEY_ID` | `1edb28d92c2be89b5b18baeb89611067` |
| `PHOTO_S3_SECRET_ACCESS_KEY` | `2042ecf8a874a4ad8edb48214f306405bfaf01d22dbca7eafb52e597595f58bd` |
| `PHOTO_S3_USE_PATH_STYLE_ENDPOINT` | `true` |
| `SEED_ADMIN_EMAIL` | `admin@example.com` |
| `SEED_ADMIN_PASSWORD` | `Hilmy080700!` |
| `SEED_STAFF_EMAIL` | `staff@example.com` |
| `SEED_STAFF_PASSWORD` | `Hilmy080700!` |
| `SEED_VIEWER_EMAIL` | `viewer@example.com` |
| `SEED_VIEWER_PASSWORD` | `password` |
| `SEED_DEMO_DATA` | `true` |

### Option B: Advanced Edit (Lebih Cepat)

Klik **Advanced edit**, paste JSON ini (ganti nilai yang sudah ada):

```json
[
  { "name": "WEBSITES_PORT", "value": "8080", "slotSetting": false },
  { "name": "WEBSITES_CONTAINER_START_TIME_LIMIT", "value": "600", "slotSetting": false },
  { "name": "APP_NAME", "value": "Harvesta", "slotSetting": false },
  { "name": "APP_ENV", "value": "production", "slotSetting": false },
  { "name": "APP_DEBUG", "value": "false", "slotSetting": false },
  { "name": "APP_KEY", "value": "base64:6l6p6p8YUj7oLOjLBByFeiyHYrXtkWj/pzuFdU8M6uE=", "slotSetting": false },
  { "name": "APP_TIMEZONE", "value": "Asia/Jakarta", "slotSetting": false },
  { "name": "APP_LOCALE", "value": "en", "slotSetting": false },
  { "name": "APP_FALLBACK_LOCALE", "value": "en", "slotSetting": false },
  { "name": "LOG_CHANNEL", "value": "stderr", "slotSetting": false },
  { "name": "LOG_LEVEL", "value": "warning", "slotSetting": false },
  { "name": "PHP_CLI_SERVER_WORKERS", "value": "4", "slotSetting": false },
  { "name": "DB_CONNECTION", "value": "pgsql", "slotSetting": false },
  { "name": "DB_HOST", "value": "aws-0-ap-southeast-1.pooler.supabase.com", "slotSetting": false },
  { "name": "DB_PORT", "value": "5432", "slotSetting": false },
  { "name": "DB_DATABASE", "value": "postgres", "slotSetting": false },
  { "name": "DB_USERNAME", "value": "postgres.sbwgznlzluoyvguopzqu", "slotSetting": false },
  { "name": "DB_PASSWORD", "value": "FG8P0A3tu0gMAKO2", "slotSetting": false },
  { "name": "DB_SSLMODE", "value": "require", "slotSetting": false },
  { "name": "SESSION_DRIVER", "value": "database", "slotSetting": false },
  { "name": "CACHE_STORE", "value": "database", "slotSetting": false },
  { "name": "QUEUE_CONNECTION", "value": "sync", "slotSetting": false },
  { "name": "MAIL_MAILER", "value": "log", "slotSetting": false },
  { "name": "PHOTO_DISK", "value": "s3", "slotSetting": false },
  { "name": "PHOTO_DISK_ROOT", "value": "photos", "slotSetting": false },
  { "name": "PHOTO_DISK_URL", "value": "https://sbwgznlzluoyvguopzqu.supabase.co/storage/v1/object/public/harvesta-photos", "slotSetting": false },
  { "name": "PHOTO_S3_ENDPOINT", "value": "https://sbwgznlzluoyvguopzqu.storage.supabase.co/storage/v1/s3", "slotSetting": false },
  { "name": "PHOTO_S3_REGION", "value": "ap-southeast-1", "slotSetting": false },
  { "name": "PHOTO_S3_BUCKET", "value": "harvesta-photos", "slotSetting": false },
  { "name": "PHOTO_S3_ACCESS_KEY_ID", "value": "1edb28d92c2be89b5b18baeb89611067", "slotSetting": false },
  { "name": "PHOTO_S3_SECRET_ACCESS_KEY", "value": "2042ecf8a874a4ad8edb48214f306405bfaf01d22dbca7eafb52e597595f58bd", "slotSetting": false },
  { "name": "PHOTO_S3_USE_PATH_STYLE_ENDPOINT", "value": "true", "slotSetting": false },
  { "name": "SEED_ADMIN_EMAIL", "value": "admin@example.com", "slotSetting": false },
  { "name": "SEED_ADMIN_PASSWORD", "value": "Hilmy080700!", "slotSetting": false },
  { "name": "SEED_STAFF_EMAIL", "value": "staff@example.com", "slotSetting": false },
  { "name": "SEED_STAFF_PASSWORD", "value": "Hilmy080700!", "slotSetting": false },
  { "name": "SEED_VIEWER_EMAIL", "value": "viewer@example.com", "slotSetting": false },
  { "name": "SEED_VIEWER_PASSWORD", "value": "password", "slotSetting": false },
  { "name": "SEED_DEMO_DATA", "value": "true", "slotSetting": false }
]
```

4. Klik **OK** → **Apply** → **Confirm**
5. Web App akan restart otomatis

---

## 🚀 STEP 7: Enable Logging & Monitor Deployment

1. Menu kiri → **Monitoring** → **App Service logs**
2. **Application Logging:** File System
3. **Retention Period:** 1 days
4. Klik **Save**

5. Menu kiri → **Monitoring** → **Log stream**
6. Tunggu dan monitor logs:

**Logs yang diharapkan (first boot ~3-5 menit):**
```
Pulling image: harvestaacr.azurecr.io/harvesta:latest
Successfully pulled image
Starting container...
Running migrations...
Seeding database...
Server running on [http://0.0.0.0:8080]
```

**Jika ada error:**
- `APP_KEY is not set` → Periksa environment variables
- `Connection refused` database → Periksa Supabase credentials
- `Port 8080 not responding` → Tunggu lebih lama (cold start pertama bisa 5-10 menit)

---

## 🚀 STEP 8: Test Aplikasi

1. Klik **Overview** di menu kiri
2. Copy **Default domain:** `https://harvesta-its.azurewebsites.net`
3. Buka di browser baru

**Jika berhasil:**
- Redirect ke `/login`
- Login dengan:
  - **Admin:** `admin@example.com` / `Hilmy080700!`
  - **Staff:** `staff@example.com` / `Hilmy080700!`
  - **Viewer:** `viewer@example.com` / `password`

### Checklist Verifikasi:

- [ ] Dashboard muncul dengan statistik
- [ ] Trees page menampilkan 40 pohon dengan foto
- [ ] Upload foto baru berhasil
- [ ] Foto tersimpan di Supabase Storage (cek bucket)
- [ ] Role-based access bekerja (staff tidak lihat Finance, viewer tidak lihat tombol edit)

---

## 🎯 Estimasi Biaya

**Per Bulan:**
- **Basic B1 App Service Plan:** ~$13.14
- **ACR Basic:** ~$5.00
- **Supabase:** $0 (free tier)
- **Total:** ~$18.14/bulan

**Dengan kredit $70:** Bisa jalan 3-4 bulan.

**Tips hemat:**
- Setelah demo/presentasi, bisa downgrade ke **Free F1** (save $13/bulan)
- Atau stop/delete resources sementara, deploy ulang saat butuh

---

## 🆘 Troubleshooting

### Container tidak start / terus restart

**Cek logs:**
1. Log stream → lihat error message
2. Common issues:
   - APP_KEY tidak diset → tambahkan di environment variables
   - Database connection failed → cek Supabase masih aktif (tidak paused)
   - Port binding error → pastikan WEBSITES_PORT = 8080

### UI tidak muncul / hanya index.html

**Ini tidak akan terjadi dengan Docker setup kita** karena:
- Vite assets sudah di-build di Dockerfile stage 2
- Public folder sudah include semua assets

Jika terjadi:
1. Cek logs untuk errors
2. Test URL: `https://harvesta-its.azurewebsites.net/up` (health check)

### Foto tidak muncul

1. Cek Supabase bucket `harvesta-photos` adalah **public**
2. Test direct URL: `https://sbwgznlzluoyvguopzqu.supabase.co/storage/v1/object/public/harvesta-photos/photos/trees/t-0001.jpg`
3. Verifikasi PHOTO_S3_* credentials benar

### App sangat lambat

Basic B1 seharusnya cukup cepat. Jika lambat:
1. Cek CPU/Memory usage di **Metrics**
2. Cold start pertama memang lambat (5-10 detik)
3. Consider upgrade ke **Standard S1** jika perlu ($70/month - terlalu mahal)

---

## 🔄 Update Deployment

Jika ada perubahan code:

### Via ACR Cloud Build:
```bash
cd /home/hilmyfausta/kuliah/semester_3/pemrograman_web/ets_webpro/harvesta

# Build image baru di ACR
az acr build --registry harvestaacr --image harvesta:latest --image harvesta:v1.0.1 .

# Restart Web App untuk pull image baru
az webapp restart --name harvesta-its --resource-group harvesta-rg
```

### Via Azure Portal:
1. Web App → **Deployment Center** → **Redeploy**
2. Atau: **Overview** → **Restart**

---

## ✅ Deployment Selesai!

**URL Production:** `https://harvesta-its.azurewebsites.net`

**Credentials untuk Demo:**
```
Admin (full access):
- Email: admin@example.com
- Password: Hilmy080700!

Staff (field operations):
- Email: staff@example.com  
- Password: Hilmy080700!

Viewer (read-only untuk dosen/penilai):
- Email: viewer@example.com
- Password: password
```

**Dokumentasi:**
- PRD lengkap: `PRD.md`
- Deployment guide: `docs/DEPLOY.md`
- This guide: `AZURE_DEPLOY_GUIDE.md`

---

**Good luck dengan deployment! 🚀**
