# 🚀 DEPLOY HARVESTA KE WASMER - INSTRUKSI CEPAT

> **Status:** Ready to deploy! Semua perbaikan sudah diterapkan.
> **Estimasi waktu:** 10-15 menit

---

## ✅ Yang Sudah Dikerjakan

1. **Fix `.wasmerignore`** - Vendor TIDAK di-ignore lagi
2. **Auto-migration script** - `bootstrap/wasmer-init.php` otomatis migrate + seed pada first boot
3. **Backup manual trigger** - Route `/setup-db` jika auto-init gagal
4. **Update `app.yaml`** - Semua env variables lengkap (termasuk viewer)
5. **Update `public/index.php`** - Load wasmer-init sebelum Laravel boot
6. **Dokumentasi lengkap** - `docs/DEPLOY_WASMER.md` (cara manage tanpa SSH)

---

## 📋 Checklist Pre-Deploy

Pastikan ini sudah dilakukan:

### ✅ Supabase Setup
- [ ] Project created di https://supabase.com
- [ ] Database credentials tersimpan (DB_HOST, DB_USERNAME, DB_PASSWORD)
- [ ] Bucket `harvesta-photos` created (public)
- [ ] S3 credentials tersimpan (access key + secret)

### ✅ Wasmer Secrets (14 secrets)
Run command ini **satu per satu** (ganti `<nilai>`):

```bash
# App
wasmer app secrets create APP_KEY "base64:..."

# Database (dari Supabase)
wasmer app secrets create DB_HOST "aws-0-ap-southeast-1.pooler.supabase.com"
wasmer app secrets create DB_PORT "5432"
wasmer app secrets create DB_DATABASE "postgres"
wasmer app secrets create DB_USERNAME "postgres.xxxxx"
wasmer app secrets create DB_PASSWORD "your-db-password"

# Photo Storage S3 (dari Supabase)
wasmer app secrets create PHOTO_DISK_ROOT "photos"
wasmer app secrets create PHOTO_DISK_URL "https://xxx.supabase.co/storage/v1/object/public/harvesta-photos"
wasmer app secrets create PHOTO_S3_ENDPOINT "https://xxx.storage.supabase.co/storage/v1/s3"
wasmer app secrets create PHOTO_S3_REGION "ap-southeast-1"
wasmer app secrets create PHOTO_S3_ACCESS_KEY_ID "your-access-key"
wasmer app secrets create PHOTO_S3_SECRET_ACCESS_KEY "your-secret-key"

# Seeder Passwords (buat password kuat!)
wasmer app secrets create SEED_ADMIN_PASSWORD "Admin2024!Secure"
wasmer app secrets create SEED_STAFF_PASSWORD "Staff2024!Field"
wasmer app secrets create SEED_VIEWER_PASSWORD "Viewer2024!Demo"
```

**Verifikasi:**
```bash
wasmer app secrets list
# Harus ada 14 secrets
```

---

## 🚀 Deploy Sekarang!

### 1. Deploy ke Wasmer

```bash
cd /home/hilmyfausta/kuliah/semester_3/pemrograman_web/ets_webpro/harvesta

# Deploy (akan upload ~411 MB termasuk vendor)
wasmer deploy
```

**Output yang diharapkan:**
```
✔ Package correctly uploaded
✔ Successfully pushed release
✔ App harvesta deployed successfully.

Live:    https://harvesta.wasmer.app
Manage:  https://wasmer.io/apps/hilmyfausta/harvesta
```

**Catatan:**
- Upload pertama ~5-10 menit (vendor 186 MB + node_modules 198 MB)
- Deploy berikutnya lebih cepat (hanya upload file yang berubah)

### 2. Tunggu Auto-Migration

Setelah deploy berhasil:

1. **Buka URL:** https://harvesta.wasmer.app
2. **Tunggu 30-60 detik** (first boot + auto-migration)
3. **Cek logs di dashboard:** https://wasmer.io/apps/hilmyfausta/harvesta → tab Logs

**Logs yang diharapkan:**
```
[Wasmer Init] Running first-time database setup...
[Wasmer Init] Migration output: Migration table created successfully...
[Wasmer Init] Seeder output: UserSeeder, MasterDataSeeder, DemoDataSeeder completed
PHP 8.3.4 Development Server (http://0.0.0.0:8080) started
```

### 3. Test Login

Jika auto-migration berhasil, akses https://harvesta.wasmer.app dan login:

**Admin (full access):**
```
Email: admin@example.com
Password: <SEED_ADMIN_PASSWORD yang kamu set>
```

**Staff (field operations):**
```
Email: staff@example.com
Password: <SEED_STAFF_PASSWORD yang kamu set>
```

**Viewer (read-only untuk demo ke penilai):**
```
Email: viewer@example.com
Password: <SEED_VIEWER_PASSWORD yang kamu set>
```

---

## 🆘 Jika Auto-Migration Gagal

**Gejala:**
- Login page error "Base table or view not found"
- Logs tidak menunjukkan migration output
- Database masih kosong (cek di Supabase → SQL Editor)

**Solusi: Manual Trigger**

1. **Extract 16 karakter dari APP_KEY:**
   ```bash
   # Misal APP_KEY = base64:abcdefgh12345678XYZABC123...
   # Ambil karakter ke-8 sampai ke-23: abcdefgh1234567
   ```

2. **Akses URL setup:**
   ```
   https://harvesta.wasmer.app/setup-db?key=abcdefgh1234567
   ```

3. **Verifikasi output:**
   ```
   Migrations: Migration table created successfully...
   Seeder: UserSeeder, MasterDataSeeder, DemoDataSeeder
   Config cached
   ```

4. **Test login lagi**

---

## 🎯 Verifikasi Deployment Sukses

### Checklist Akhir:

- [ ] **URL accessible:** https://harvesta.wasmer.app (redirect ke /login)
- [ ] **Login admin berhasil** dan dashboard muncul dengan data
- [ ] **Login staff berhasil** dan menu Finance/Users tidak muncul
- [ ] **Login viewer berhasil** dan semua tombol Create/Edit/Delete tidak muncul
- [ ] **Dashboard menampilkan statistik** (trees, harvests, dll)
- [ ] **Trees page menampilkan 40 pohon** dengan thumbnail foto
- [ ] **Upload foto baru** (sebagai admin/staff) dan muncul di list
- [ ] **Foto tersimpan di Supabase Storage** (cek bucket `harvesta-photos`)
- [ ] **Grafik dashboard** muncul (harvest per bulan, dll)
- [ ] **No error di logs** Wasmer dashboard

---

## 📝 Untuk Laporan & Demo

### Akun untuk Penilai/Dosen (Viewer - Read Only):

```
URL: https://harvesta.wasmer.app

Akun Demo (hanya bisa lihat, tidak bisa ubah):
Email: viewer@example.com
Password: [tulis password yang kamu set di SEED_VIEWER_PASSWORD]

Akun Admin (jika ingin lihat semua fitur):
Email: admin@example.com
Password: [tulis password yang kamu set di SEED_ADMIN_PASSWORD]
```

### Screenshot yang Perlu Diambil:

1. Dashboard (login sebagai admin)
2. Trees list dengan foto
3. Tree detail dengan care logs timeline
4. Upload foto baru
5. Dashboard grafik
6. Finance pages (expenses, sales)
7. Reports dengan filter
8. User management
9. Login sebagai viewer (tunjukkan tidak ada tombol edit/delete)

---

## 🔄 Update Deployment

Jika ada perubahan kode:

```bash
# 1. Test lokal
composer test

# 2. Commit changes (jangan push APP_KEY/passwords!)
git add .
git commit -m "fix: update feature X"

# 3. Deploy ulang
wasmer deploy

# Database dan foto TIDAK berubah (aman)
```

---

## 🐛 Troubleshooting Common Issues

### Error: "vendor/autoload.php not found"
**Solusi:** Pastikan `.wasmerignore` TIDAK mengabaikan `vendor/`

### Error: "Connection refused" database
**Solusi:** 
1. Cek Supabase project status (resume jika paused)
2. Verifikasi DB_HOST, DB_USERNAME, DB_PASSWORD di secrets
3. Test koneksi: `psql "postgresql://user:pass@host:5432/postgres?sslmode=require"`

### Error: "Storage S3 credentials invalid"
**Solusi:** 
1. Cek Supabase → Project Settings → Storage → S3 keys
2. Generate new access key jika perlu
3. Update secrets: `wasmer app secrets update PHOTO_S3_ACCESS_KEY_ID "..."`

### Foto tidak muncul
**Solusi:**
1. Cek bucket `harvesta-photos` adalah **public**
2. Verifikasi PHOTO_DISK_URL correct
3. Test URL manual: `https://xxx.supabase.co/storage/v1/object/public/harvesta-photos/photos/trees/t-0001.jpg`

### App lambat / cold start 10+ detik
**Ini normal untuk free tier Wasmer.** First request setelah idle memang lambat.
**Solusi:** Buka app 2-3 menit sebelum demo.

---

## 📞 Support

- **Dokumentasi lengkap:** `docs/DEPLOY_WASMER.md`
- **Wasmer docs:** https://docs.wasmer.io
- **Supabase docs:** https://supabase.com/docs

---

## ✨ Ready to Deploy?

Jalankan sekarang:

```bash
wasmer deploy
```

**Good luck! 🚀**
