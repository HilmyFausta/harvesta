# PRD — {{APP_NAME}}: Tree & Orchard Management System

> **Dokumen ini adalah sumber kebenaran (single source of truth) proyek.** Semua anggota tim dan semua AI agent WAJIB membaca dokumen ini sebelum menulis kode. Jika ada konflik antara instruksi lain dan dokumen ini, dokumen ini yang menang. Jika dokumen ini perlu diubah, ubah lewat Pull Request dan beri tahu seluruh tim.

- **Mata kuliah:** EF234301 Web Programming (D) — Midterm Exam
- **Tim:** maksimal 3 mahasiswa (nama: `{{MEMBER_A}}`, `{{MEMBER_B}}`, `{{MEMBER_C}}`)
- **Deadline:** 28 Oktober 2026, pukul 23:59 WIB (denda keterlambatan 0,15% nilai **per menit**; terlambat 1 jam = potongan 9%)
- **Nama aplikasi:** `{{APP_NAME}}` (placeholder; lihat bagian 3.3 untuk cara menggantinya di satu tempat)

---

## 1. Ringkasan Produk

{{APP_NAME}} adalah aplikasi web CRUD untuk mengelola kebun pohon skala kecil. Pengguna dapat mencatat blok lahan, jenis pohon (umum, bukan khusus satu spesies), data tiap pohon, riwayat perawatan, hasil panen, pengeluaran, dan penjualan, lalu melihat dashboard dan laporan untuk evaluasi produktivitas serta keuntungan kebun.

**Prinsip desain produk**
1. **Generik:** jenis pohon dan kategori adalah data master yang dikelola pengguna (bukan hardcode). Contoh: kategori "Fruit", jenis "Durian".
2. **Sederhana:** alur harian cukup 3 langkah: pilih pohon → catat perawatan/panen → lihat ringkasan.
3. **Mobile-friendly:** petugas menginput dari HP di kebun.

**Tujuan**
- Memenuhi seluruh syarat tugas midterm (bagian 2) dengan nilai maksimal.
- Menghasilkan aplikasi yang rapi, aman, konsisten, dan ter-deploy.

**Bukan tujuan (non-goals)**
- Tidak ada aplikasi mobile native, pembayaran online, integrasi IoT/sensor, multi-tenant, atau multi-bahasa.
- Tidak ada API publik (REST) kecuali dibutuhkan fitur internal.

---

## 2. Syarat Tugas (Wajib Dipenuhi)

| # | Syarat | Cara pemenuhan |
|---|--------|----------------|
| R1 | Website CRUD memakai Laravel (MVC) | Laravel 13 + Livewire 4 starter kit |
| R2 | Minimal 8 fitur, tidak termasuk autentikasi | 11 fitur (F-01 s.d. F-11), bagian 5 |
| R3 | Login, registrasi, dan logout | Bawaan starter kit, disesuaikan (bagian 5.0) |
| R4 | Skema database punya minimal 1 foreign key | Banyak FK (bagian 6) |
| R5 | Laporan: desain database (CDM & PDM) | Bagian 11 |
| R6 | Laporan: daftar fitur | Bagian 11 |
| R7 | Laporan: screenshot + penjelasan tiap fitur | Bagian 11 |
| R8 | Laporan: persentase dan kontribusi tiap anggota | Bagian 11 |
| R9 | Deploy ke hosting gratis | Wasmer Edge (bagian 10) |
| R10 | Source code lengkap di GitHub **publik** | Bagian 9 |

**Rubrik:** Design 20 · Implementation 50 · Analysis/Evaluation 25 · Conclusion 5 = 100.

**Aturan pengumpulan (jangan sampai salah)**
- Kirim email ke dosen pengampu, CC ke dua asisten (alamat ada di soal ujian).
- Subjek email: `EF234301_WebPro_MID_StudentID1_Name1_StudentID2_Name2` (sesuaikan jumlah anggota).
- Lampiran: **satu** ZIP bernama `EF234301_WebPro_MID_StudentID1_Name1_StudentID2_Name2.ZIP` berisi tepat dua file: `Report.PDF` dan `Declaration.PDF` (pernyataan integritas akademik yang sudah ditandatangani).
- Isi email wajib memuat **link repository GitHub publik**.
- Target kirim: **paling lambat 28 Okt 2026 pukul 20:00 WIB**, jangan menunggu menit-menit terakhir.

---

## 3. Bahasa, Konvensi Umum, dan Konfigurasi Dasar

### 3.1 Aturan bahasa (PENTING)
| Bagian | Bahasa |
|--------|--------|
| Dokumen PRD, README internal, komentar diskusi tim | Indonesia |
| **Seluruh UI aplikasi** (menu, label, tombol, placeholder, pesan validasi, flash message, email, judul halaman, teks kosong/empty state, seed data, nama kolom pada tabel/ekspor) | **Inggris dengan grammar yang benar** |
| Nama variabel, class, method, tabel, kolom, route, nama file, commit message, nama branch | Inggris |
| Laporan PDF | Boleh Indonesia (kecuali screenshot yang otomatis Inggris) |

Aturan tambahan UI:
- Gunakan Title Case untuk judul halaman dan menu ("Care Logs"), sentence case untuk tombol dan pesan ("Save changes", "The tree was created successfully.").
- Pesan validasi dan flash harus kalimat lengkap berakhiran titik.
- Jangan hardcode teks di banyak tempat tanpa perlu; gunakan file lang `lang/en/*.php` untuk pesan yang berulang. `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en`.
- Format tanggal tampilan: `21 Oct 2026` (`d M Y`). Format angka: `1,250,000.50`. Mata uang: `IDR 1,250,000` (tanpa simbol Rp). Zona waktu: `Asia/Jakarta` (`APP_TIMEZONE`).

### 3.2 Aturan kode umum
- PHP 8.3+, mengikuti PSR-12; jalankan `./vendor/bin/pint` sebelum commit.
- Satu kelas satu tanggung jawab. Logika bisnis yang dipakai ulang diletakkan di `app/Services` atau `app/Actions`, bukan di view.
- Validasi memakai Form Request (atau validasi Livewire yang setara) dengan aturan yang jelas.
- Otorisasi memakai Policy + Gate. Jangan mengecek peran dengan `if` tersebar di view.
- Query yang menampilkan daftar wajib memakai pagination dan eager loading (`with()`) untuk mencegah N+1.
- Jangan pernah menulis rahasia ke repo.

### 3.3 Placeholder nama aplikasi
- Set di `.env`: `APP_NAME="{{APP_NAME}}"` (ganti nanti, sekali saja).
- Di kode dan view selalu pakai `config('app.name')`. **Dilarang** menulis nama aplikasi sebagai string literal.
- Logo dan favicon placeholder; ganti belakangan.

---

## 4. Pengguna dan Peran

| Peran | Siapa | Hak akses |
|-------|-------|-----------|
| **Admin** | Pemilik/pengelola kebun | Akses penuh: semua CRUD, data master, keuangan, laporan, manajemen pengguna |
| **Staff** | Petugas lapangan | Lihat data; buat/ubah Trees, Care Logs, Harvests. **Tidak** bisa menghapus data, tidak bisa membuka Finance, Users, maupun mengubah data master |

Aturan:
- Pengguna yang mendaftar sendiri lewat halaman Register otomatis berperan **Staff**.
- Admin pertama dibuat oleh seeder. Hanya Admin yang dapat mengubah peran pengguna lain.
- Admin tidak boleh menghapus atau menurunkan peran dirinya sendiri jika ia satu-satunya Admin.
- Persona: (1) *Owner* yang butuh gambaran untung/rugi dan produktivitas; (2) *Field staff* yang butuh input cepat dari HP.

---

## 5. Fitur

Semua fitur: tabel dengan pencarian, filter, sortir, pagination (15/halaman), konfirmasi sebelum hapus, flash message sukses/gagal, dan empty state yang informatif.

### 5.0 Autentikasi (tidak dihitung dalam 8 fitur minimum)
- Register (name, email, password + confirmation), Login (email + password, remember me), Logout, dan Forgot password bawaan starter kit.
- Gunakan hasil starter kit apa adanya sebagai dasar; hanya sesuaikan teks (Inggris), tampilan (bagian 8), dan penetapan peran default `staff`.
- Halaman selain auth wajib diproteksi middleware `auth` (dan `verified` hanya jika email verification diaktifkan; **default: tidak diaktifkan** agar demo mudah).
- Fitur starter kit yang dipakai (sudah diterapkan lewat `install:features`): registration, password confirmation, two-factor authentication. **Dibuang:** email verification dan passkeys (passkeys butuh WebAuthn/HTTPS dengan domain tetap sehingga berisiko saat deploy).

### F-01 Dashboard
- **Akses:** Admin, Staff (konten finansial hanya untuk Admin).
- Kartu statistik: total trees, trees per status, total blocks, harvest bulan ini, *(Admin)* expenses bulan ini, sales bulan ini, net profit bulan ini.
- Widget: "Upcoming Care" (care log dengan `next_due_on` ≤ 7 hari ke depan atau terlambat), "Recent Harvests" (5 terakhir).
- Grafik: panen per bulan (12 bulan terakhir), *(Admin)* expenses vs sales per bulan.
- **Selesai jika:** angka cocok dengan data seeder dan tidak ada N+1 (maks. ±15 query).

### F-02 Categories (data master)
- Atribut: `name` (wajib, unik, maks. 100), `description` (opsional).
- **Akses:** Admin = CRUD; Staff = lihat.
- Aturan: kategori yang masih dipakai oleh Tree Type tidak boleh dihapus (tampilkan pesan: "This category cannot be deleted because it is still used by tree types.").

### F-03 Tree Types (data master)
- Atribut: `category_id` (wajib), `name` (wajib, unik per kategori), `scientific_name` (opsional), `description` (opsional).
- **Akses:** Admin = CRUD; Staff = lihat.
- Aturan: tidak bisa dihapus jika masih dipakai Tree. Filter daftar berdasarkan kategori.

### F-04 Blocks (blok/lahan)
- Atribut: `name` (wajib, unik), `area_sqm` (opsional, ≥ 0), `location_note` (opsional), `description` (opsional).
- **Akses:** Admin = CRUD; Staff = lihat.
- Halaman detail: daftar pohon di blok itu + ringkasan (jumlah pohon, total panen).
- Aturan: tidak bisa dihapus jika masih ada Tree.

### F-05 Trees (inti aplikasi)
- Atribut: `code` (wajib, unik, format bebas mis. `T-0001`; boleh digenerate otomatis), `tree_type_id`, `block_id`, `planted_on` (tanggal, tidak boleh di masa depan), `status` (`seedling`, `growing`, `productive`, `needs_attention`, `dead`), `height_cm` (opsional), `notes`, `photo_path` (opsional).
- **Foto:** satu foto per pohon, JPG/PNG/WebP, maks. 2 MB, disimpan lewat disk `photos` (bagian 10.3). Tampilkan thumbnail di daftar dan foto besar di detail.
- **Akses:** Admin = CRUD (hapus = soft delete); Staff = buat, lihat, ubah.
- Pencarian: kode/catatan. Filter: block, tree type, category, status. Sortir: code, planted_on.
- Halaman detail: info pohon, timeline care logs, daftar harvests, total panen.
- Aturan: pohon berstatus `dead` tidak boleh menerima care log atau harvest baru.

### F-06 Care Logs (riwayat perawatan)
- Atribut: `tree_id`, `activity_type` (`watering`, `fertilizing`, `pruning`, `pest_control`, `disease_treatment`, `weeding`, `inspection`, `other`), `performed_on` (tidak boleh di masa depan), `next_due_on` (opsional, harus ≥ `performed_on`), `notes`. `user_id` diisi otomatis dari pengguna yang login.
- **Akses:** Admin = CRUD; Staff = buat, lihat, ubah (hanya milik sendiri).
- Filter: tree, block, activity type, rentang tanggal.
- Tombol cepat "Add care log" dari halaman detail Tree (tree terisi otomatis).

### F-07 Harvests (hasil panen)
- Atribut: `tree_id`, `harvested_on` (tidak boleh di masa depan), `quantity` (> 0), `unit` (`kg` atau `pcs`), `grade` (`premium`, `good`, `fair`, `poor`), `notes`. `user_id` otomatis.
- **Akses:** Admin = CRUD; Staff = buat, lihat, ubah (hanya milik sendiri).
- Filter: tree, block, tree type, grade, rentang tanggal. Tampilkan total quantity per filter.
- Harvest tidak boleh dihapus jika sudah punya Sale.

### F-08 Expenses (pengeluaran)
- Atribut: `block_id` (opsional; kosong = pengeluaran umum), `category` (`fertilizer`, `pesticide`, `labor`, `equipment`, `water`, `transport`, `other`), `amount` (> 0), `spent_on`, `description`. `user_id` otomatis.
- **Akses:** Admin saja.
- Filter: block, category, rentang tanggal; tampilkan total.

### F-09 Sales (penjualan)
- Atribut: `harvest_id` (wajib), `buyer_name`, `sold_on`, `quantity` (> 0), `unit_price` (> 0), `total_amount` (**dihitung server**: `quantity × unit_price`, tidak boleh dari input pengguna), `notes`. `user_id` otomatis.
- **Aturan bisnis:** total `quantity` semua Sale pada satu Harvest tidak boleh melebihi `quantity` Harvest tersebut. Pesan error: "The quantity exceeds the remaining unsold amount of this harvest (X unit)."
- **Akses:** Admin saja.
- Filter: buyer, rentang tanggal, tree type; tampilkan total pendapatan.

### F-10 Reports
- **Akses:** Admin.
- Laporan: (a) Harvest summary per block / tree type / bulan; (b) Financial summary (sales − expenses) per bulan dan per block; (c) Care activity summary.
- Filter periode (from–to). Grafik (Chart.js). **Ekspor CSV** untuk tiap laporan (kolom berbahasa Inggris). PDF opsional (nice-to-have).
- Ekspor dibuat *streamed* (tanpa menulis file ke disk server).

### F-11 User Management
- **Akses:** Admin.
- CRUD pengguna (name, email, role, password opsional saat edit). Cari berdasarkan nama/email. Ubah peran. Nonaktifkan tidak diperlukan.
- Aturan perlindungan Admin terakhir (bagian 4).

### F-12 Profile (bawaan starter kit, bonus)
- Ubah nama, email, password. Hapus akun dinonaktifkan untuk Admin terakhir.

> Total fitur di luar autentikasi: **11** (F-01 s.d. F-11) → memenuhi syarat ≥ 8 dengan cadangan.

---

## 6. Desain Database (PostgreSQL)

> Skema di bawah adalah **acuan CDM/PDM** (digambar di Vertabelo/Redgate) dan **acuan migration**. Sumber kebenaran runtime adalah migration Laravel; setiap perubahan skema harus memperbarui bagian ini dan diagram, dan dikabarkan ke tim.

Konvensi: nama tabel jamak snake_case; PK `id` (bigint, auto-increment); semua tabel punya `created_at`, `updated_at`; waktu `timestamp`; uang `decimal(14,2)`; enum diimplementasikan sebagai `string` + PHP backed Enum + validasi (bukan tipe enum native DB, agar migrasi mudah).

### 6.1 Tabel

**users** (dari starter kit, tambah kolom `role`)
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | |
| name | string(255) | |
| email | string(255) unique | |
| email_verified_at | timestamp null | |
| password | string | hashed |
| role | string(20) default `staff` | `admin` / `staff` |
| remember_token | string null | |
| (kolom tambahan starter kit, mis. two-factor) | — | biarkan bawaan |

**categories**
| id | bigint PK |
|----|-----------|
| name | string(100) **unique** |
| description | text null |

**tree_types**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | |
| category_id | bigint **FK → categories.id** | `restrictOnDelete` |
| name | string(100) | unique bersama `category_id` |
| scientific_name | string(150) null | |
| description | text null | |

**blocks**
| id | bigint PK |
|----|-----------|
| name | string(100) **unique** |
| area_sqm | decimal(12,2) null |
| location_note | string(255) null |
| description | text null |

**trees**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | |
| code | string(30) **unique** | |
| tree_type_id | bigint **FK → tree_types.id** | `restrictOnDelete` |
| block_id | bigint **FK → blocks.id** | `restrictOnDelete` |
| created_by | bigint **FK → users.id** null | `nullOnDelete` |
| planted_on | date | |
| status | string(20) default `seedling` | `seedling/growing/productive/needs_attention/dead` |
| height_cm | decimal(8,2) null | |
| notes | text null | |
| photo_path | string(255) null | path relatif di disk `photos` |
| deleted_at | timestamp null | soft delete |

**care_logs**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | |
| tree_id | bigint **FK → trees.id** | `cascadeOnDelete` |
| user_id | bigint **FK → users.id** null | `nullOnDelete` |
| activity_type | string(30) | lihat F-06 |
| performed_on | date | |
| next_due_on | date null | |
| notes | text null | |

**harvests**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | |
| tree_id | bigint **FK → trees.id** | `cascadeOnDelete` |
| user_id | bigint **FK → users.id** null | `nullOnDelete` |
| harvested_on | date | |
| quantity | decimal(10,2) | > 0 |
| unit | string(10) | `kg` / `pcs` |
| grade | string(20) | `premium/good/fair/poor` |
| notes | text null | |

**expenses**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | |
| block_id | bigint **FK → blocks.id** null | `nullOnDelete` |
| user_id | bigint **FK → users.id** null | `nullOnDelete` |
| category | string(30) | lihat F-08 |
| amount | decimal(14,2) | > 0 |
| spent_on | date | |
| description | string(255) null | |

**sales**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint PK | |
| harvest_id | bigint **FK → harvests.id** | `restrictOnDelete` |
| user_id | bigint **FK → users.id** null | `nullOnDelete` |
| buyer_name | string(150) | |
| sold_on | date | |
| quantity | decimal(10,2) | > 0 |
| unit_price | decimal(14,2) | > 0 |
| total_amount | decimal(14,2) | dihitung server |
| notes | text null | |

### 6.2 Relasi (kardinalitas)
- `categories 1 — N tree_types`
- `tree_types 1 — N trees`
- `blocks 1 — N trees`
- `blocks 1 — N expenses` (opsional)
- `trees 1 — N care_logs`
- `trees 1 — N harvests`
- `harvests 1 — N sales`
- `users 1 — N` trees (created_by), care_logs, harvests, expenses, sales

### 6.3 Indeks tambahan
`trees(block_id)`, `trees(tree_type_id)`, `trees(status)`, `care_logs(tree_id, performed_on)`, `care_logs(next_due_on)`, `harvests(tree_id, harvested_on)`, `expenses(spent_on)`, `sales(sold_on)`.

### 6.4 Seeder (wajib, bahasa Inggris)
- 1 Admin (`admin@example.com`) dan 1 Staff (`staff@example.com`), password dev: `password` (**wajib diganti di produksi**; email dan password produksi dibaca dari env, bukan hardcode). Variabel: `SEED_ADMIN_*`, `SEED_STAFF_*`, `SEED_DEMO_DATA` (lihat `config/seeding.php`). Seeder menolak berjalan di produksi bila password kosong.
- Data demo dibuat deterministik (Faker di-seed), jadi angka di semua laptop sama. `fakerphp/faker` dipindah ke `require` agar seeder demo tetap jalan di server produksi (`composer install --no-dev`).
- 4 categories (Fruit, Timber, Ornamental, Spice), ±10 tree types (contoh: Durian, Mango, Rambutan, Teak, Mahogany, Clove, dll.), 3 blocks, ±40 trees, ±100 care logs, ±40 harvests, ±30 expenses, ±20 sales dengan tanggal tersebar 12 bulan terakhir (agar grafik tampak hidup).
- Seeder harus idempotent-friendly (`php artisan migrate:fresh --seed` aman dijalankan berulang).
- Buat Model Factory untuk semua model.

---

## 7. Struktur Menu dan Route

### 7.1 Sidebar (urutan dan label — Inggris)
1. **Dashboard** — `/dashboard`
2. **Trees** — `/trees`
3. **Care Logs** — `/care-logs`
4. **Harvests** — `/harvests`
5. **Master Data** (grup)
   - Categories — `/categories`
   - Tree Types — `/tree-types`
   - Blocks — `/blocks`
6. **Finance** (grup, Admin saja)
   - Expenses — `/expenses`
   - Sales — `/sales`
7. **Reports** (Admin) — `/reports`
8. **Users** (Admin) — `/users`
9. Menu pengguna (footer/topbar): **Profile**, **Log out**

Item yang tidak boleh diakses peran tertentu **tidak ditampilkan** dan route-nya tetap diproteksi (403).

### 7.2 Konvensi route
- Resource-style: `index`, `create`, `show`, `edit` (+ aksi store/update/destroy). Nama route: `trees.index`, `trees.create`, dst.
- Semua route aplikasi di dalam grup `auth`. Group Admin-only memakai middleware peran (`role:admin`) atau policy.
- Halaman publik hanya: landing/redirect ke login, login, register, forgot password.

---

## 8. Desain UI/UX

**Karakter:** natural, bersih, hangat, mudah dibaca, ramah HP.

**Palet warna (token Tailwind/CSS variable)**
| Peran | Warna | Hex |
|-------|-------|-----|
| Primary | Leaf green | `#2F7D4F` |
| Primary dark (hover) | Forest | `#1F5C3A` |
| Background | Cream | `#FAF7F0` |
| Surface (kartu) | White | `#FFFFFF` |
| Accent | Soil brown | `#8B5E3C` |
| Success / Warning / Danger / Info | `#2F7D4F` / `#D9A406` / `#C0392B` / `#2B6CB0` |
| Teks utama / sekunder | `#1F2937` / `#6B7280` |

**Komponen dan pola**
- Layout: sidebar kiri (collapse jadi drawer di mobile), topbar dengan nama pengguna dan Log out.
- Kartu dengan sudut membulat (`rounded-xl`), bayangan halus. Tipografi sans-serif bersih (mis. Inter). Heading jelas, spasi lega.
- Tabel: header lengket, aksi berupa ikon (View/Edit/Delete) dengan tooltip, baris responsif (di mobile menjadi kartu atau scroll horizontal).
- **Badge status pohon:** `seedling` = biru, `growing` = hijau muda, `productive` = hijau tua, `needs_attention` = kuning, `dead` = merah.
- Form: label di atas input, pesan error merah di bawah field, tombol utama hijau, tombol sekunder abu, tombol hapus merah dengan dialog konfirmasi.
- Empty state: ikon + kalimat Inggris + tombol aksi ("No trees yet. Add your first tree.").
- Loading state pada tombol submit; flash message sukses/gagal di pojok atas.
- Aksesibilitas: kontras cukup, label untuk semua input, fokus terlihat, `alt` pada gambar.
- Gunakan komponen bawaan starter kit (Flux UI/Tailwind) terlebih dahulu sebelum membuat komponen sendiri.

---

## 9. Tech Stack, Struktur Proyek, dan Alur Git

### 9.1 Stack (dikunci — jangan ganti tanpa kesepakatan tim)
| Lapisan | Pilihan |
|---------|---------|
| Bahasa/framework | PHP 8.4 (minimal 8.3), Laravel 13 |
| Starter kit | **Livewire 4** + Flux UI (free), single-file components (`⚡name.blade.php`) |
| Database | PostgreSQL 16+ (lokal dan produksi, termasuk tes); tidak memakai SQLite/MySQL |
| Frontend | Livewire + Blade + Tailwind CSS 4 (bawaan starter kit), Chart.js untuk grafik |
| Package manager JS | **pnpm** (bukan npm); commit `pnpm-lock.yaml`, jangan commit `package-lock.json` |
| Tes | PHPUnit (bawaan starter kit) di database PostgreSQL `harvesta_testing`; PHPStan level 7 (Larastan) |
| Deploy | Wasmer Edge (bagian 10) |
| Format kode | Laravel Pint |

Setup lokal tiap anggota:
```bash
git clone <repo-url> && cd <repo>
composer install && pnpm install
cp .env.example .env && php artisan key:generate
# buat DUA database PostgreSQL: harvesta (aplikasi) dan harvesta_testing (tes), isi DB_* di .env
createdb harvesta && createdb harvesta_testing
php artisan storage:link
php artisan migrate:fresh --seed
composer run dev      # otomatis memakai pnpm (dideteksi dari pnpm-lock.yaml)
composer test         # Pint + PHPStan + PHPUnit, sama persis dengan CI
```
`.env.example` sudah berisi `DB_CONNECTION=pgsql`, `APP_LOCALE=en`, `APP_TIMEZONE=Asia/Jakarta`, variabel disk `photos`, dan variabel seeder.

Catatan per OS:
- **Linux (Ubuntu/Arch):** pasang ekstensi `pdo_pgsql` (Ubuntu: `sudo apt install php8.4-pgsql`; Arch: aktifkan `extension=pdo_pgsql` dan `extension=pgsql` di `/etc/php/php.ini`).
- **Windows:** PHP dari php.new/Herd Lite **tidak** punya `pdo_pgsql`. Pakai PHP resmi: `winget install PHP.PHP.NTS.8.4`, lalu aktifkan `pdo_pgsql`, `pgsql`, `intl`, `zip`, `fileinfo`, `gd`, `sodium` di `php.ini`.

### 9.2 Struktur folder (tambahan di atas default Laravel)
```
app/
  Enums/            # TreeStatus, ActivityType, HarvestUnit, HarvestGrade, ExpenseCategory, UserRole (+ Concerns/HasOptions)
  Http/Middleware/  # EnsureUserHasRole (alias `role`)
  Models/           # + Concerns/RecordsAuthor (isi user_id/created_by otomatis)
  Policies/         # satu policy per model, auto-discovered
  Services/         # SaleService (validasi sisa stok), ReportService, dll.
  Support/          # Format (tanggal, angka, IDR)
resources/views/pages/  # halaman Livewire single-file, per modul: pages/trees/, pages/care-logs/, ...
database/{migrations,factories,seeders}
resources/views/    # dikelompokkan per modul: trees/, care-logs/, ...
lang/en/
tests/Feature/
docs/               # ERD (png/pdf), PRD, catatan
```
Ikuti pola Livewire dari starter kit; jangan mencampur dua gaya (mis. single-file dan class component) tanpa alasan. Konvensi teknis yang sudah tersedia (komponen, helper, policy, dst.) dirangkum di `AGENTS.md` (di-commit; `CLAUDE.md` hanya mengimpornya). Laravel Boost tidak terpasang.

### 9.3 Alur Git
- Repo GitHub **publik**. **Semua anggota bekerja langsung di branch `main`** (keputusan tim, 22 Okt); tidak memakai feature branch atau PR. Karena itu `main` harus tetap bisa jalan setiap kali di-push.
- Commit kecil dan sering, memakai Conventional Commits berbahasa Inggris: `feat(trees): add tree photo upload`, `fix(sales): validate remaining quantity`, `docs: update prd`.
- Alur harian: `git pull --rebase origin main` sebelum mulai kerja dan sebelum push → jalankan `./vendor/bin/pint`, `php artisan test`, dan `php artisan migrate:fresh --seed` tanpa error → `git push origin main`.
- Konflik saat rebase diselesaikan oleh anggota yang sedang push; jika konflik menyentuh area anggota lain, diskusikan dulu.
- `.gitignore` wajib mencakup `.env`, `/vendor`, `/node_modules`, `/storage/*.key`, file upload lokal, dan berkas IDE.
- **Jangan pernah commit** `.env`, kunci API, password, atau data pribadi. Jika bocor, rotasi kunci.

### 9.4 Aturan migration (mencegah konflik antaranggota)
- **Semua migration awal (seluruh tabel di bagian 6) dibuat oleh Member A pada Tahap 1** dan di-merge ke `main` sebelum fitur dikerjakan.
- Setelah itu, perubahan skema hanya lewat migration **baru** (jangan mengedit migration lama yang sudah di-merge), dan wajib mengabari tim serta memperbarui bagian 6 dan ERD.
- Nama migration deskriptif; tidak ada dua migration yang mengubah tabel yang sama secara bersamaan tanpa koordinasi.

---

## 10. Deployment dan Penyimpanan Foto

### 10.1 Target deploy: Azure App Service + Supabase (diputuskan 23 Okt)
Langkah lengkap: [`docs/DEPLOY.md`](docs/DEPLOY.md).

- **Aplikasi:** Azure App Service (Linux container, plan Free F1; B1 dari kredit Azure for Students bila perlu). Image dibuat dari `Dockerfile` + `docker/start.sh` oleh workflow `.github/workflows/deploy.yml` setelah `tests` hijau di `main`, disimpan di GitHub Container Registry (`ghcr.io/hilmyfausta/harvesta`), lalu di-deploy ke Azure (publish profile di GitHub Secrets).
- **Database:** Supabase PostgreSQL (free, tidak kedaluwarsa) lewat koneksi *Session pooler* (IPv4, `sslmode=require`).
- **Foto:** Supabase Storage, bucket publik `harvesta-photos`, diakses lewat protokol S3 (`PHOTO_DISK=s3`).
- **Data demo:** `start.sh` menjalankan `migrate --force` lalu `db:seed --force` setiap boot. Aman diulang: akun dan master data di-*upsert*, data demo (40 pohon + 32 foto) hanya dibuat jika tabel `trees` masih kosong.
- **Rahasia** (`APP_KEY`, `DB_PASSWORD`, kunci S3, `SEED_*_PASSWORD`) hanya diisi di Azure App settings / GitHub Secrets, tidak pernah di repo.
- Ditolak: Wasmer Edge (dukungan ekstensi PHP tidak terdokumentasi, PostgreSQL hanya region Eropa/Kanada) dan Render (Blueprint maupun Web Service meminta kartu kredit).
- Batasan: F1 dibatasi 60 menit CPU/hari dan tidur saat idle (buka ±2 menit sebelum demo); project Supabase di-*pause* setelah ±1 minggu tidak aktif (aktifkan lagi dari dashboard).

### 10.2 Batasan runtime yang harus dipatuhi sejak sekarang
Anggap disk lokal server bersifat **sementara / tidak persisten**. Maka:
- Jangan menyimpan data penting di `storage/` lokal. Session, cache, queue memakai driver `database` atau `array`/`sync` (tanpa file).
- Log ke `stderr` di produksi (`LOG_CHANNEL=stderr`).
- Ekspor/Laporan dihasilkan streaming, bukan disimpan ke disk.
- Hindari ekstensi PHP yang tidak umum; periksa kompatibilitas saat uji deploy.
- Jika uji deploy gagal pada hal tertentu, catat di bagian 14 dan cari alternatif (cadangan: plan B1 Azure dari kredit student).

### 10.3 Abstraksi penyimpanan foto (akan "diakali" belakangan)
- Semua kode foto hanya boleh memakai disk Laravel bernama **`photos`**: `Storage::disk('photos')`. **Dilarang** memakai `public_path()`, `storage_path()`, atau path file langsung.
- Konfigurasi `config/filesystems.php`: disk `photos` memakai driver dari `PHOTO_DISK` (default `local`, folder `storage/app/public/photos`, butuh `php artisan storage:link`). Saat deploy: `PHOTO_DISK=s3`, `PHOTO_DISK_ROOT=photos`, dan isi `PHOTO_S3_*` (kandidat: Supabase Storage, Cloudflare R2) tanpa mengubah kode fitur. Driver S3 butuh paket `league/flysystem-aws-s3-v3` (dipasang saat keputusan penyimpanan diambil).
- URL foto selalu lewat method model (mis. `$tree->photo_url`), tidak dirangkai manual di view.
- Validasi unggah: `image`, mimes `jpg,jpeg,png,webp`, maks. 2048 KB. Hapus file lama saat foto diganti/pohon dihapus permanen.
- Cadangan terakhir bila semua opsi gagal: simpan foto kecil (≤ 500 KB) sebagai data di database; keputusan ini hanya diambil tim bersama.

---

## 11. Laporan (Deliverable) dan Pemetaan Rubrik

| Bagian laporan | Isi | Poin |
|----------------|-----|------|
| **Design (20)** | CDM, PDM (ekspor dari Vertabelo/Redgate), arsitektur singkat, desain UI (palet, wireframe/mockup) | 20 |
| **Implementation (50)** | Daftar fitur (F-01..F-11 + auth), screenshot + penjelasan tiap fitur, cuplikan kode penting (validasi, otorisasi, aturan bisnis), link GitHub dan URL deploy | 50 |
| **Analysis/Evaluation (25)** | Hasil pengujian (tabel kasus uji), kelebihan dan keterbatasan, keamanan (auth, otorisasi, validasi, CSRF, hashing), performa (eager loading, indeks), kendala deployment dan solusinya, saran pengembangan | 25 |
| **Conclusion (5)** | Ringkasan hasil dan pembelajaran | 5 |
| **Kontribusi** | Persentase dan uraian kontribusi tiap anggota (target seimbang, mis. 33/33/34) | wajib |

Kumpulkan sejak awal: screenshot tiap halaman (data seeder sudah terisi), catatan bug dan solusi, serta bukti tes. Siapkan `Declaration.PDF` yang ditandatangani semua anggota.

---

## 12. Pembagian Tugas dan Kepemilikan File

Setiap anggota (dan AI agent-nya) **hanya mengedit area miliknya**. Perubahan di area orang lain harus didiskusikan dulu. File bersama (layout, navigasi, route, `AppServiceProvider`, config) diubah dalam commit kecil terpisah agar konflik mudah diselesaikan.

| Anggota | Tanggung jawab utama | Area kepemilikan |
|---------|----------------------|------------------|
| **A — Foundation & Core Data** | Setup proyek, **semua migration, enum, model, factory, seeder** (Tahap 1), layout + sidebar + role middleware/policy dasar, F-05 Trees (+ foto) | `database/*`, `app/Enums`, `app/Models`, layout/navigasi, modul trees |
| **B — Field Operations & Master Data** | F-02 Categories, F-03 Tree Types, F-04 Blocks, F-06 Care Logs, F-07 Harvests, widget "Upcoming Care", halaman detail Tree bagian timeline/harvest (berkoordinasi dengan A), Profile/auth polish dan teks Inggris, unit/feature test modulnya | modul categories, tree-types, blocks, care-logs, harvests, auth views |
| **C — Finance & Insights** | F-08 Expenses, F-09 Sales (+ `SaleService`), F-01 Dashboard, F-10 Reports + CSV, F-11 Users, deploy pipeline (Wasmer + GitHub Actions) | modul expenses, sales, dashboard, reports, users, deploy config |

Semua anggota: menulis feature test untuk modulnya, ikut mengisi laporan (screenshot dan penjelasan fitur miliknya), dan review PR anggota lain. Persentase kontribusi dihitung dari pekerjaan nyata (commit/PR, laporan, deploy), bukan dari rencana ini saja.

---

## 13. Jadwal (21 – 28 Oktober 2026)

| Tanggal | Target | Penanggung jawab |
|---------|--------|------------------|
| 21 Okt | PRD disepakati; CDM/PDM dibuat; repo GitHub + setup lokal berjalan di ketiga OS | Semua |
| 22 Okt | Migration, model, enum, seeder di-merge ke `main`; layout dan sidebar; **deploy kerangka kosong ke Wasmer** | A, C |
| 22–25 Okt | Fitur dikerjakan paralel sesuai area (commit kecil dan sering ke `main`) | A, B, C |
| 25 Okt | Semua fitur F-01..F-11 *feature-complete* | Semua |
| 26 Okt | Integrasi, perbaikan bug, polish UI, uji lintas peran, uji mobile | Semua |
| 27 Okt | Deploy final, foto di produksi, screenshot final, tulis laporan, tanda tangan Declaration | Semua |
| 28 Okt | Final check dan **kirim email paling lambat 20:00 WIB** (buffer 4 jam) | Semua |

Jika jadwal meleset, potong dulu: PDF export, grafik tambahan, dan fitur foto di produksi (fitur inti tetap prioritas).

---

## 14. Kualitas, Pengujian, dan Definition of Done

**Pengujian minimum (Feature tests)**
- Auth: register (peran default staff), login, logout, akses tanpa login ditolak.
- Otorisasi: Staff tidak bisa membuka Finance/Users/Master write; Admin bisa.
- CRUD happy path untuk tiap modul + validasi gagal untuk kasus penting.
- Aturan bisnis: sale melebihi sisa panen ditolak; kategori/jenis/blok yang dipakai tidak bisa dihapus; pohon `dead` tidak menerima care log/harvest.
- Manual test lintas peran dan di layar HP.

**Definition of Done (per fitur)**
1. Sesuai spesifikasi dan aturan di bagian 5 (atribut, validasi, akses).
2. UI mengikuti bagian 8 dan **seluruh teks berbahasa Inggris benar secara grammar**.
3. Otorisasi (policy) dan validasi server-side ada.
4. Tidak ada N+1 pada halaman daftar; ada pagination.
5. Feature test minimal untuk happy path dan satu kasus gagal; `pint` bersih.
6. Berjalan di `migrate:fresh --seed` dan di lingkungan semua anggota (Ubuntu/Arch/Windows).
7. Sudah di-push ke `main` dan dicek anggota lain; screenshot tersedia untuk laporan.

**Keamanan dasar:** CSRF aktif (bawaan), password di-hash, mass assignment dilindungi (`$fillable`), validasi unggahan file, tidak ada SQL mentah tanpa binding, `APP_DEBUG=false` di produksi, rahasia hanya di env/secrets.

**Catatan uji deploy (isi saat Tahap 1)**
- Hasil uji Wasmer (ekstensi PHP, koneksi PostgreSQL, session/cache, log): **tidak dapat dilanjutkan** — registry Wasmer hanya menyediakan `php/php` sampai 8.3.4, sedangkan `composer.lock` mensyaratkan PHP >= 8.4.1 (Symfony 8).
- Docker scaffold (`Dockerfile`, `docker/start.sh`) sudah dibuat dan teruji: image berhasil di-build dan container menjalankan migrasi, cache, `storage:link`, serta server di `/login`. Target deploy kini Azure App Service (10.1); `render.yaml` dihapus.
- Keputusan penyimpanan foto produksi: Supabase Storage (lihat 10.1) (sementara `PHOTO_DISK=local`).

---

## 15. Pertanyaan Terbuka (isi saat diputuskan)

| # | Pertanyaan | Keputusan |
|---|-----------|-----------|
| 1 | Nama aplikasi final | `{{APP_NAME}}` |
| 2 | Nama dan NRP anggota tim | `{{MEMBER_A/B/C}}` |
| 3 | Layanan penyimpanan foto produksi | Supabase Storage (S3), bucket publik `harvesta-photos` (23 Okt) |
| 4 | Ekspor PDF laporan (opsional) | belum diputuskan |
| 5 | Verifikasi email diaktifkan? | tidak (default) |
| 6 | Versi framework (PRD awal menyebut Laravel 12) | Laravel 13 + Livewire 4, mengikuti hasil starter kit terbaru (22 Okt) |
| 7 | Fitur auth starter kit | registration, password confirmation, 2FA dipakai; passkeys dan email verification dibuang (22 Okt) |
| 8 | Database tes | PostgreSQL `harvesta_testing`, bukan SQLite in-memory (22 Okt) |
| 9 | Alur Git | semua kerja langsung di `main`, tanpa feature branch/PR (keputusan tim, 22 Okt) |

---

## 16. Aturan untuk AI Agent

Berlaku untuk semua agent di laptop semua anggota:
1. **Baca** `PRD.md` dan `AGENTS.md` sebelum mengubah apa pun; ikuti stack, konvensi, dan batas area kepemilikan (bagian 12).
2. **Jangan** mengubah skema, enum, atau kontrak antarmodul di luar area sendiri; ajukan perubahan sebagai PR/diskusi.
3. **Jangan** mengganti teknologi (pnpm, PostgreSQL, Livewire, Wasmer), menambah dependency besar, atau mengubah arsitektur tanpa persetujuan tim.
4. **Seluruh teks UI dalam bahasa Inggris yang benar**; kode dan commit juga Inggris.
5. Gunakan `config('app.name')`, disk `photos`, Policy, Form Request/validasi, eager loading, dan pagination sesuai PRD.
6. Jangan menulis file ke disk server untuk data aplikasi; jangan commit rahasia; jangan push kecuali diminta anggota tim (push dilakukan oleh manusia).
7. Jalankan `pint` dan tes sebelum menyatakan tugas selesai. Jangan menyatakan selesai jika tes gagal.
8. Bila instruksi pengguna bertentangan dengan PRD, beri tahu pengguna dan minta mereka memperbarui PRD terlebih dahulu.
9. Pertahankan perubahan sekecil mungkin dan terarah; jangan merapikan/mengubah file yang tidak terkait tugas.
10. Jelaskan secara singkat apa yang diubah dan file mana saja, tanpa mengulang seluruh kode di jawaban akhir.
