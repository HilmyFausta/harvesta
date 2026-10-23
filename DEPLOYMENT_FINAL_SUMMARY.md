# 🎯 HARVESTA DEPLOYMENT - FINAL SUMMARY & ACTION PLAN

**Status:** ✅ Semua persiapan selesai, siap deploy!  
**Tanggal:** 23 Oktober 2026, 16:37 WIB  
**Estimasi Total Waktu:** 30-40 menit  
**Biaya:** ~$18/bulan dari kredit $70 (cukup untuk 3-4 bulan)

---

## ✅ YANG SUDAH SELESAI (By AI Assistant)

### 1. **Database & Storage Setup** ✅
- Database Supabase PostgreSQL: **READY**
- Storage Supabase S3: **READY**  
- Bucket `harvesta-photos`: **PUBLIC & CONFIGURED**
- Semua credentials tersimpan dan ter-validasi

### 2. **Application Keys** ✅
- Production APP_KEY generated: `base64:6l6p6p8YUj7oLOjLBByFeiyHYrXtkWj/pzuFdU8M6uE=`
- Seed passwords configured (Admin, Staff, Viewer)

### 3. **Docker Setup** ✅
- Dockerfile production-ready (multi-stage build)
- Start script optimized untuk Azure (`docker/start.sh`)
- Build strategy: ACR Cloud Build (no local build needed)

### 4. **Documentation** ✅
- `AZURE_DEPLOY_GUIDE.md`: Panduan lengkap 400+ baris
- `azure-deploy-simple.sh`: Helper script dengan checklist
- Environment variables JSON ready to paste

### 5. **Git Repository** ✅
- All changes committed & pushed to GitHub
- Clean working tree, no conflicts
- Latest commit: `140a82a` (deployment scripts)

---

## 🚀 ACTION PLAN UNTUK KAMU (30-40 MENIT)

### 📍 **FASE 1: Setup Azure Resources (15-20 menit)**

#### Step 1.1: Buka Azure Portal
```
URL: https://portal.azure.com
Login: 5025251052@student.its.ac.id
```

#### Step 1.2: Create Resource Group
1. Search **"Resource groups"** → **+ Create**
2. Settings:
   - **Resource group:** `harvesta-rg`
   - **Region:** **West US** (penting!)
3. **Review + create** → **Create**

**⏱️ Estimasi: 2 menit**

---

#### Step 1.3: Create Container Registry (ACR)
1. Search **"Container registries"** → **+ Create**
2. Settings:
   - **Registry name:** `harvestaacr`
   - **Resource group:** `harvesta-rg`
   - **Location:** **West US**
   - **SKU:** **Basic** (~$5/month)
3. **Review + create** → **Create**
4. Wait deployment (~2 menit)
5. **Go to resource**
6. Menu kiri: **Settings** → **Access keys**
7. ✅ **Enable** toggle "Admin user"
8. 📋 **CATAT:**
   - Username: `harvestaacr`
   - Password: (copy password atau password2)

**⏱️ Estimasi: 5 menit**

---

### 📍 **FASE 2: Build Docker Image (10 menit)**

**Kembali ke terminal di laptop:**

```bash
cd /home/hilmyfausta/kuliah/semester_3/pemrograman_web/ets_webpro/harvesta

# Login ke ACR (paste password dari Step 1.3)
az acr login --name harvestaacr

# Build image di Azure (cloud build - no local build!)
az acr build --registry harvestaacr --image harvesta:latest --image harvesta:v1.0.0 .
```

**Output yang diharapkan:**
```
Packing source code into tar to upload...
Uploading archived source code from '/tmp/build_archive_xxx.tar.gz'...
Sending context (XXX MB) to registry: harvestaacr...
...
Step 1/X : FROM composer:2 AS vendor
...
Successfully built image: harvesta:latest
Successfully built image: harvesta:v1.0.0
Run ID: ca1 was successful after Xm Ys
```

**⏱️ Estimasi: 8-10 menit** (tergantung internet upload + Azure build time)

**🆘 Jika error "authorization failed":**
- Pastikan `az acr login` berhasil dulu
- Cek password yang di-paste benar
- Atau tunggu 1-2 menit (ACR baru selesai provisioning)

---

### 📍 **FASE 3: Create App Service (5 menit)**

#### Step 3.1: Create App Service Plan
1. Search **"App Service plans"** → **+ Create**
2. Settings:
   - **Name:** `harvesta-plan`
   - **Resource group:** `harvesta-rg`
   - **Operating System:** **Linux**
   - **Region:** **West US**
   - **Pricing tier:** Klik **Explore pricing plans** → **Production** tab → **Basic B1** (1.75 GB RAM, ~$13/month)
3. **Review + create** → **Create**

**⏱️ Estimasi: 2 menit**

---

#### Step 3.2: Create Web App
1. Search **"App Services"** → **+ Create** → **Web App**
2. **Tab Basics:**
   - **Name:** `harvesta-its` (atau nama unique, akan jadi `https://harvesta-its.azurewebsites.net`)
   - **Publish:** **Container**
   - **Operating System:** **Linux**
   - **Region:** **West US**
   - **Linux Plan:** `harvesta-plan`
   
3. **Tab Container:**
   - **Image Source:** **Azure Container Registry**
   - **Registry:** `harvestaacr`
   - **Image:** `harvesta`
   - **Tag:** `latest`
   - **Startup Command:** (kosongkan)

4. **Tab Monitoring:**
   - **Enable Application Insights:** **No**

5. **Review + create** → **Create**
6. Wait (~2 menit) → **Go to resource**

**⏱️ Estimasi: 3 menit**

---

### 📍 **FASE 4: Configure Environment (5 menit)**

#### Step 4.1: Set Environment Variables

1. Di Web App → Menu kiri: **Settings** → **Environment variables**
2. Klik tab **App settings**
3. Klik **Advanced edit** (di atas list)
4. **DELETE** existing content `[]`
5. **PASTE** JSON berikut (sudah complete dengan semua values):

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

6. Klik **OK**
7. Klik **Apply** (di atas)
8. Klik **Confirm**
9. **Web App akan restart otomatis**

**⏱️ Estimasi: 5 menit**

---

### 📍 **FASE 5: Monitor & Verify (5-10 menit)**

#### Step 5.1: Monitor Startup Logs

1. Menu kiri: **Monitoring** → **Log stream**
2. Tunggu logs muncul (~1-2 menit)

**Logs yang diharapkan:**
```
INFO  - Pulling image: harvestaacr.azurecr.io/harvesta:latest
INFO  - Successfully pulled image
INFO  - Starting container for site
INFO  - Container start-up and configuration completed successfully
...
Running migrations...
INFO  Migration table created successfully.
Seeding database...
INFO  UserSeeder completed
INFO  MasterDataSeeder completed
INFO  DemoDataSeeder completed
Server running on [http://0.0.0.0:8080]
```

**⏱️ First boot: 3-5 menit** (migration + seed + upload 32 foto ke Supabase)

---

#### Step 5.2: Test Aplikasi

1. Web App → **Overview** (menu kiri)
2. Copy **Default domain:** `https://harvesta-its.azurewebsites.net`
3. **Buka di browser**

**Yang diharapkan:**
- ✅ Redirect ke `/login`
- ✅ Login form muncul dengan styling (Flux UI)

**Login Test:**

```
Admin (Full Access):
  Email: admin@example.com
  Password: Hilmy080700!

Staff (Field Operations):
  Email: staff@example.com
  Password: Hilmy080700!

Viewer (Read-Only untuk Demo):
  Email: viewer@example.com
  Password: password
```

---

#### Step 5.3: Verification Checklist

Login sebagai **Admin** dan verifikasi:

- [ ] **Dashboard muncul** dengan statistik (total trees, harvests, revenue)
- [ ] **Trees page** menampilkan 40 pohon dengan thumbnail foto
- [ ] **Tree detail** bisa dibuka, ada photo, care logs, harvest history
- [ ] **Upload foto baru** (Edit tree → upload foto) → foto muncul
- [ ] **Supabase Storage** → bucket `harvesta-photos` → ada file baru yang di-upload
- [ ] **Finance pages** (Expenses, Sales) accessible
- [ ] **Reports** bisa generate dengan filter
- [ ] **User Management** accessible

Login sebagai **Staff** dan verifikasi:
- [ ] **Finance & User menu TIDAK muncul** (role restriction works)
- [ ] **Trees, Harvests accessible**

Login sebagai **Viewer** dan verifikasi:
- [ ] **Semua page accessible (read-only)**
- [ ] **Tombol Create/Edit/Delete TIDAK muncul** (view-only mode)

**⏱️ Estimasi: 5 menit**

---

## 🎉 DEPLOYMENT SUCCESS!

Kalau semua checklist di atas ✅, **deployment berhasil!**

**Production URL:** `https://harvesta-its.azurewebsites.net`

**Untuk Laporan/Presentasi:**

```
Aplikasi: Harvesta - Tree & Orchard Management System
URL: https://harvesta-its.azurewebsites.net

Akun Demo (Dosen/Penilai):
  Email: viewer@example.com
  Password: password
  (Read-only, tidak bisa edit/delete)

Akun Admin (Full Demo):
  Email: admin@example.com
  Password: Hilmy080700!
  (Full access, bisa demo semua fitur)

Tech Stack:
  - Backend: Laravel 13 + PHP 8.4
  - Frontend: Livewire 4 + Flux UI
  - Database: Supabase PostgreSQL
  - Storage: Supabase S3
  - Infrastructure: Azure App Service (Basic B1) + ACR
  - CI/CD: GitHub Actions (ready, bisa diaktifkan)

Biaya: ~$18/month (dari kredit Azure for Students $70)
```

---

## 🆘 TROUBLESHOOTING

### ❌ Container tidak start / terus restart

**Cek Log stream, cari error message:**

1. **"APP_KEY is not set"**
   - Fix: Pastikan APP_KEY di environment variables benar
   - Re-apply environment variables

2. **"Connection refused" (database)**
   - Fix: Cek Supabase project status (buka https://supabase.com/dashboard)
   - Jika paused, klik "Resume"
   - Verifikasi DB_HOST, DB_USERNAME, DB_PASSWORD

3. **"Port 8080 not responding"**
   - Normal di first boot (tunggu 5-10 menit)
   - Cek logs untuk migration/seeding progress
   - Pastikan WEBSITES_PORT=8080

4. **"vendor/autoload.php not found"**
   - Docker build issue
   - Rebuild: `az acr build --registry harvestaacr --image harvesta:latest .`

---

### ❌ Login page muncul tapi UI broken (no styling)

**Kemungkinan: Vite assets tidak ter-build**

Fix:
```bash
# Rebuild image (pastikan step 2 Dockerfile berhasil)
az acr build --registry harvestaacr --image harvesta:latest .
```

Cek logs build, pastikan:
```
Step XX/YY : FROM node:22-bookworm-slim AS assets
...
RUN pnpm run build
... vite build completed
```

---

### ❌ Foto tidak muncul / broken image

**Cek:**
1. Supabase bucket `harvesta-photos` harus **PUBLIC**
   - Supabase dashboard → Storage → harvesta-photos → Settings → Public bucket: ON

2. Test direct URL:
   ```
   https://sbwgznlzluoyvguopzqu.supabase.co/storage/v1/object/public/harvesta-photos/photos/trees/t-0001.jpg
   ```
   Harus bisa diakses (tidak 403/404)

3. Verifikasi environment variables:
   - PHOTO_DISK=s3
   - PHOTO_DISK_URL correct
   - PHOTO_S3_* credentials correct

---

### ❌ App sangat lambat (> 30 detik response)

**Kemungkinan:**
1. **Cold start pertama** (normal, 5-10 detik)
2. **B1 plan overloaded** (check Metrics di Portal)
   - Fix: Restart Web App
   - Atau: Consider upgrade ke S1 (tapi $70/month - too expensive)

**Tips:**
- Buka app 2-3 menit sebelum demo (warm-up)
- Basic B1 seharusnya cukup responsive untuk demo

---

### ❌ Seeder tidak jalan / database kosong

**Cek logs:**
```
Seeding database...
UserSeeder completed
MasterDataSeeder completed  
DemoDataSeeder completed
```

Jika tidak ada, manual trigger:

1. Web App → **SSH** atau **Console**
2. Jalankan:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

Atau tambahkan route temporary `/setup-db` (lihat AZURE_DEPLOY_GUIDE.md)

---

## 🔄 UPDATE DEPLOYMENT

Jika ada perubahan code di future:

```bash
cd /home/hilmyfausta/kuliah/semester_3/pemrograman_web/ets_webpro/harvesta

# 1. Commit changes
git add .
git commit -m "fix: update feature X"
git push

# 2. Rebuild image di ACR
az acr build --registry harvestaacr --image harvesta:latest --image harvesta:v1.0.1 .

# 3. Restart Web App (pull image baru)
az webapp restart --name harvesta-its --resource-group harvesta-rg

# Atau via Portal: Web App → Overview → Restart
```

**Data dan foto aman** (tidak akan hilang karena di Supabase, bukan di container)

---

## 💰 COST MANAGEMENT

**Monthly Cost Estimate:**
- Azure Container Registry (Basic): ~$5
- App Service Plan (Basic B1): ~$13
- Supabase (Free tier): $0
- **Total: ~$18/month**

**Dengan kredit $70:** Bisa jalan **3-4 bulan**

**Hemat biaya setelah presentasi/demo:**

1. **Downgrade ke Free F1** (save $13/month):
   ```bash
   az appservice plan update --name harvesta-plan --resource-group harvesta-rg --sku F1
   ```
   **Cons:** App lebih lambat, sleep setelah 20 menit inactive

2. **Stop Web App sementara** (save $13/month, ACR tetap jalan):
   ```bash
   az webapp stop --name harvesta-its --resource-group harvesta-rg
   ```
   Start lagi saat butuh:
   ```bash
   az webapp start --name harvesta-its --resource-group harvesta-rg
   ```

3. **Delete semua resources** (save $18/month):
   ```bash
   az group delete --name harvesta-rg --yes --no-wait
   ```
   **Deploy ulang kapan saja** (30 menit, ikuti guide ini lagi)

---

## 📚 DOKUMENTASI LENGKAP

Semua ada di repo:

1. **AZURE_DEPLOY_GUIDE.md**: Panduan detail 400+ baris
2. **azure-deploy-simple.sh**: Helper script (jalankan untuk checklist)
3. **docs/DEPLOY.md**: Original deployment guide (Azure + Supabase architecture)
4. **PRD.md**: Product Requirements Document lengkap
5. **Dockerfile**: Production-ready multi-stage build
6. **docker/start.sh**: Container startup script dengan migration + seed

---

## ✅ FINAL CHECKLIST

Sebelum deploy, pastikan:

- [x] Supabase database setup (PostgreSQL)
- [x] Supabase storage setup (S3 bucket public)
- [x] APP_KEY production generated
- [x] Dockerfile production-ready
- [x] Git repository clean & pushed
- [x] Azure account ready (kredit $70)
- [x] Documentation complete

**Next:** Ikuti **ACTION PLAN** di atas step-by-step!

---

## 🎯 QUICK START (TL;DR)

```bash
# 1. Run helper script untuk lihat checklist
./azure-deploy-simple.sh

# 2. Buka Azure Portal
# https://portal.azure.com

# 3. Create (via Portal):
#    - Resource Group: harvesta-rg (West US)
#    - Container Registry: harvestaacr (Basic, West US, enable Admin)

# 4. Build image (terminal):
az acr login --name harvestaacr
az acr build --registry harvestaacr --image harvesta:latest .

# 5. Create (via Portal):
#    - App Service Plan: harvesta-plan (Linux, B1, West US)
#    - Web App: harvesta-its (Container, from ACR)

# 6. Configure (via Portal):
#    - Environment variables: paste JSON dari guide

# 7. Monitor & Test:
#    - Log stream: tunggu migration + seed
#    - Test: https://harvesta-its.azurewebsites.net
#    - Login: admin@example.com / Hilmy080700!
```

---

**🚀 Good luck dengan deployment! Kamu pasti bisa! 🚀**

**Kalau ada error/stuck, share screenshot/logs dan saya bantu troubleshoot!**
