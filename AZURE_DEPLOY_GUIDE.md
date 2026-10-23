# 🎯 HARVESTA DEPLOYMENT - FINAL & VERIFIED ACTION PLAN

**Status:** ✅ DEPLOYED & RUNNING SUCCESSFULLY  
**Tanggal:** 23 Oktober 2026  
**Estimasi Total Waktu:** 30-45 menit  
**Biaya:** ~$18/bulan dari kredit Azure for Students (cukup untuk 5+ bulan)

---

## ⚠️ PENTING: Pelajaran dari Proses Deploy
Akun **Azure for Students** memiliki kebijakan pembatasan region yang ketat. 
- **JANGAN** memaksa menggunakan `West US` atau `Indonesia Central` jika ditolak oleh kebijakan.
- **Gunakan `Japan East`** (atau region lain yang tersedia di dropdown Azure Portal). Region ini diizinkan, stabil, dan latensinya ke Supabase (`ap-southeast-1`) sangat baik (~50ms).
- **Cloud Build (`az acr build`) sering diblokir** oleh kebijakan pelajar. Kita akan menggunakan **Local Build & Push** yang 100% lebih andal.

---

##  ACTION PLAN UNTUK DEPLOYMENT PERTAMA

###  FASE 1: Setup Azure Resources (10-15 menit)

#### Step 1.1: Create Resource Group
1. Buka https://portal.azure.com
2. Search **"Resource groups"** → **+ Create**
3. Settings:
   - **Resource group:** `harvesta-rg`
   - **Region:** **Japan East** *(Pilih ini, atau region lain yang tersedia di dropdown akunmu)*
4. **Review + create** → **Create**

#### Step 1.2: Create Container Registry (ACR)
1. Search **"Container registries"** → **+ Create**
2. Settings:
   - **Registry name:** `harvestaacr2` *(Harus unik global. Jika error, coba `harvestaitsacr`)*
   - **Resource group:** `harvesta-rg`
   - **Location:** **Japan East** *(WAJIB sama dengan Resource Group)*
   - **SKU:** **Basic**
3. **Review + create** → **Create**
4. Setelah selesai, klik **Go to resource**.
5. Menu kiri: **Settings** → **Access keys**
6. ✅ **Enable** toggle "Admin user" → **Save**
7. 📋 **CATAT** `Username` dan salah satu `password`.

---

### 📍 FASE 2: Build & Push Docker Image (10-15 menit)

*Kita menggunakan Local Build karena lebih stabil untuk akun pelajar.*

Buka terminal di laptop, pastikan berada di folder root proyek (`harvesta`):

# 1. Login ke ACR (paste password dari Step 1.2)
az acr login --name harvestaacr2

# 2. Build image secara lokal dan beri tag sesuai ACR
docker build -t harvestaacr2.azurecr.io/harvesta:latest .

# 3. Push image ke Azure Container Registry
docker push harvestaacr2.azurecr.io/harvesta:latest

*Tunggu hingga proses push selesai (muncul digest: sha256:...).*

---

### 📍 FASE 3: Create App Service (5-10 menit)

#### Step 3.1: Create App Service Plan
1. Search **"App Service plans"** → **+ Create**
2. Settings:
   - **Name:** `harvesta-plan`
   - **Resource group:** `harvesta-rg`
   - **Operating System:** **Linux**
   - **Region:** **Japan East** *(WAJIB sama)*
   - **Pricing plan:** **Basic B1** (1.75 GB RAM)
3. **Review + create** → **Create**

#### Step 3.2: Create Web App
1. Search **"App Services"** → **+ Create** → **Web App**
2. **Tab Basics:**
   - **Name:** `harvesta-its` *(Azure mungkin menambahkan suffix unik, itu normal)*
   - **Publish:** **Container**
   - **Operating System:** **Linux**
   - **Region:** **Japan East**
   - **Linux Plan:** `harvesta-plan`
3. **Tab Container:**
   - **Image Source:** **Azure Container Registry**
   - **Registry:** `harvestaacr2`
   - **Authentication:** **Admin credentials**
   - **Image:** `harvesta`
   - **Tag:** `latest`
   - **Port:** **8080** *(Sangat Penting!)*
   - **Startup Command:** *(Kosongkan)*
4. **Tab Monitoring:** Enable Application Insights: **No**
5. **Review + create** → **Create**

---

### 📍 FASE 4: Configure Environment Variables (5 menit)

1. Di Web App → Menu kiri: **Settings** → **Environment variables**
2. Klik tab **App settings** → **Advanced edit**
3. **Hapus** isi yang ada, lalu **Paste** JSON berikut:

[
  { "name": "WEBSITES_PORT", "value": "8080", "slotSetting": false },
  { "name": "WEBSITES_CONTAINER_START_TIME_LIMIT", "value": "1800", "slotSetting": false },
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

4. Klik **OK** → **Apply** → **Confirm**.

---

### 📍 FASE 5: Monitor & Verify (5-10 menit)

1. Menu kiri: **Monitoring** → **App Service logs**
   - Set **Application Logging:** `File System`
   - Set **Container logging:** `On` *(Wajib!)*
   - Klik **Save**
2. Klik **Log stream** (di bawah App Service logs). Biarkan terbuka.
3. Kembali ke **Overview** → Klik **Restart**.
4. **Tunggu di Log stream** sampai muncul baris:  
   `INFO Server running on [http://0.0.0.0:8080]`
5. Setelah itu, buka URL aplikasimu di **Incognito Mode**.
6. Login: `admin@example.com` / `Hilmy080700!`

---

## 🆘 TROUBLESHOOTING KHUSUS (BERDASARKAN PENGALAMAN NYATA)

### ❌ Error: "Undefined table: relation 'users' does not exist" saat Seeding
**Penyebab:** Tabel `migrations` di Supabase masih menyimpan catatan lama, sehingga Laravel melewatkan proses pembuatan tabel (`Nothing to migrate`), tapi tabel aslinya sudah terhapus, menyebabkan seeding crash.

**Solusi Pasti:**
1. Buka **Supabase Dashboard** → **SQL Editor**.
2. Jalankan query ini untuk mereset total:

DROP TABLE IF EXISTS migrations CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS trees CASCADE;
-- (tambahkan tabel lain seperti care_logs, harvests, dll jika perlu)

3. Kembali ke Azure Portal → **Overview** → **Restart** Web App.
4. Laravel akan dipaksa membuat ulang semua tabel dan seeding dari awal dengan benar.

### ❌ Web Stuck Loading / 503 Service Unavailable
**Penyebab:** Proses seeding (terutama upload foto) memakan waktu lebih dari batas default Azure (230 detik), sehingga Azure mematikan container sebelum web server sempat menyala.
**Solusi:** Pastikan `WEBSITES_CONTAINER_START_TIME_LIMIT` di Environment Variables sudah diset ke **`1800`**.

---

## 🔄 CARA UPDATE DEPLOYMENT (SETELAH ADA PERUBAHAN KODE)

Setiap kali kamu melakukan `git push` perubahan kode baru, ikuti 3 langkah ini di terminal laptop:

# 1. Pastikan kode terbaru sudah di-pull
git pull origin main

# 2. Build ulang image dengan tag yang sama (akan menimpa yang lama)
docker build -t harvestaacr2.azurecr.io/harvesta:latest .

# 3. Push ke Azure
docker push harvestaacr2.azurecr.io/harvesta:latest

# 4. Restart Web App agar menarik image terbaru
az webapp restart --name harvesta-its --resource-group harvesta-rg

*Catatan: Script `docker/start.sh` sudah dikonfigurasi untuk otomatis menjalankan `php artisan migrate --force` setiap kali restart, jadi perubahan database akan aman.*

---

## 💡 REKOMENDASI JANGKA PANJANG: CI/CD OTOMATIS
Agar tidak perlu menjalankan perintah di atas secara manual, kamu bisa mengaktifkan **GitHub Actions**. 
1. Buat file `.github/workflows/deploy.yml` di repository.
2. Simpan password ACR di **GitHub Repository Secrets** dengan nama `ACR_PASSWORD`.
3. Setiap kali `git push`, GitHub akan otomatis build, push ke ACR, dan restart Azure dalam 3-5 menit.

---

## 💰 COST MANAGEMENT
- **Total:** ~$18/bulan (ACR Basic $5 + App Service B1 $13).
- **Hemat Biaya:** Setelah presentasi, kamu bisa **Stop** Web App via Azure Portal (hemat $13/bulan, data tetap aman di Supabase & ACR). Nyalakan kembali (`Start`) hanya saat butuh demo.

---

**🚀 Selamat! Dokumentasi ini sekarang 100% akurat dan sudah terverifikasi berhasil.**