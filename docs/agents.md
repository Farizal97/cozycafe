# AGENTS.MD — Blueprint & Step-by-Step Build Prompts for CozyCafe

Dokumen ini berisi blueprint dan aturan kerja untuk mengembangkan aplikasi **CozyCafe** berdasarkan spesifikasi `prd.md`. Blueprint awal mencatat target arsitektur; panduan implementasi terkini di bawah mengikuti repository yang sudah berjalan dan tidak meminta pembuatan ulang aplikasi dari nol.

---

## Panduan Eksekusi untuk AI Agent
1. **Kerjakan secara berurutan (Step 1 hingga Step 10)**. Jangan melompati fase sebelum pengujian fungsionalitas pada tahap tersebut berjalan lancar.
2. **Patuhi Tech Stack & Aturan Bisnis:** Gunakan **Native PHP (tanpa framework)**, **MySQL (PHP PDO)**, **HTML5, CSS3, JavaScript (Vanilla), dan Bootstrap 5**.
3. **Bahasa & Lokalisasi:** Seluruh teks UI, label status, format tanggal, mata uang (`Rp10.000`), dan zona waktu (`Asia/Jakarta` / WIB) wajib menggunakan **Bahasa Indonesia**.
4. **Keamanan & Integritas Data:** Terapkan *Prepared Statements*, proteksi CSRF, sanitasi XSS (`htmlspecialchars`), *Database Transactions* untuk pemotongan stok/booking, serta penyimpanan file sensitif (`storage/`, `backups/`) di luar direktori `public/`.

---

## STEP 1: Project Initialization & Docker/Dokploy Readiness

**Prompt untuk AI Agent:**
> "Inisialisasi struktur proyek aplikasi web **CozyCafe** menggunakan Native PHP 8.2+ dan MySQL 8.0 sesuai struktur folder berikut:
> ```text
> cozycafe/
> ├── app/
> │   ├── config/       # database.php, app.php
> │   ├── controllers/  # Auth, Product, Order, Payment, Booking, Report, Admin, Customer
> │   ├── models/       # Representasi tabel database
> │   ├── services/     # StockService, PaymentService, BookingService, BackupService, LogService
> │   ├── helpers/      # csrf, sanitizer, formatter (IDR/WIB), rate_limiter, response
> │   └── middleware/   # AuthMiddleware, CsrfMiddleware
> ├── public/           # Web root publik (index.php, admin/index.php, assets/css, assets/js, assets/images)
> ├── resources/views/  # customer/, admin/, layouts/, components/
> ├── database/         # migrations/ (schema.sql), seeds/ (seed.sql)
> ├── storage/          # uploads/proofs/, uploads/products/, uploads/qris/, logs/, temp/
> ├── backups/          # File backup SQL (di luar public/)
> ├── docker/           # Konfigurasi Apache VirtualHost & PHP ini
> ├── Dockerfile        # Untuk deployment Dokploy
> ├── docker-compose.yml
> ├── composer.json     # Dependensi lokal: endroid/qr-code (atau chillerlan/php-qrcode), phpoffice/phpspreadsheet, dompdf/dompdf
> └── .env.example
> ```
> **Tugas Spesifik:**
> 1. Buat router sederhana di `public/index.php` yang mendukung clean URL dan memisahkan rute Customer (`/`, `/menu`, `/cart`, `/checkout`, `/booking`, `/track-order`, `/track-booking`, `/history`) dan Admin (`/admin/...`).
> 2. Buat `app/config/database.php` menggunakan **PHP PDO** dengan mode error `PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_EMULATE_PREPARES => false`, dan pembacaan variabel dari `.env` (mendukung environment variables Dokploy).
> 3. Siapkan `Dockerfile` berbasis `php:8.2-apache` yang mengaktifkan `mod_rewrite`, ekstensi `pdo_mysql`, `gd`, `zip`, `intl`, mengarahkan `DocumentRoot` ke `/var/www/html/public`, serta mengatur permission folder `storage/` dan `backups/`.
> 4. Buat helper keamanan: generator & validator **CSRF token**, fungsi escaping XSS `e()`, format Rupiah (`Rp10.000`), dan set default timezone ke `Asia/Jakarta`."

---

## STEP 2: Database Schema & Seeder (MySQL)

**Prompt untuk AI Agent:**
> "Buat file `database/migrations/schema.sql` dan `database/seeds/seed.sql` untuk MySQL berdasarkan rancangan entitas di PRD Bagian 10.
> **Pastikan mencakup seluruh tabel beserta Primary Key, Foreign Key, Index, dan Unique Constraint:**
> 1. **Auth:** `admins` (1 akun admin), `admin_login_attempts` (tracking gagal login & lockout 15 menit).
> 2. **Menu:** `categories`, `products` (kolom: `stock`, `min_stock`, `min_order_quantity`, `max_order_quantity`, `availability_status` ENUM('available','sold_out','unavailable'), `is_active`, `has_order_history`).
> 3. **Customer:** `customers` (`name`, `phone` UNIQUE/INDEXED, `is_anonymized`).
> 4. **Orders:** `orders` (`order_code` UNIQUE, `customer_id`, `order_type` ENUM('dine_in','takeaway'), `booking_id`, `table_id`, `subtotal`, `discount_amount`, `tax_amount`, `service_charge`, `total_amount`, `payment_method_id`, `payment_status`, `order_status`, `payment_deadline`, `confirmed_at`, `completed_at`), `order_items` (menyimpan `product_name_snapshot` dan `price_snapshot`), `order_status_histories`, `order_cancellation_requests`, `order_substitutions` (untuk penawaran produk pengganti dengan batas waktu 5 menit).
> 5. **Payments:** `payment_methods` (Cash at Counter, Bank Transfer, QRIS Statis + `deadline_minutes`, konfigurasi rekening & gambar QRIS), `payments`, `payment_proofs` (simpan path file di luar public, status verifikasi, alasan penolakan), `payment_extensions` (permintaan perpanjangan deadline), `refunds`.
> 6. **Booking & Tables:** `booking_areas` (Indoor, Outdoor, Smoking, VIP), `tables` (`table_number`, `area_id`, `capacity`, `position_x`, `position_y`, `is_active`), `bookings` (`booking_code` format `BK-YYYYMMDD-001`, `booking_date`, `slot_start`, `slot_end`, `guest_count`, `status`, `qr_token` UNIQUE, `confirmation_mode`, `checked_in_at`, `checkin_method`), `booking_tables` (mendukung multi-meja oleh admin), `booking_cancellation_requests`, `table_sessions` (untuk dine-in tanpa reservasi dengan `expected_end_at` dan `qr_token` meja).
> 7. **Promotions & Engagement:** `promotions` (diskon otomatis), `vouchers` (`code`, `discount_type`, `discount_value`, `minimum_purchase`, `total_usage_limit`, `per_phone_limit`, masa berlaku), `voucher_usages`, `reviews` (`review_type` ENUM('product','cafe'), `rating` 1-5, `moderation_status`), `testimonials`.
> 8. **System:** `activity_logs` (read-only: `admin_id`, `action`, `entity_type`, `entity_id`, `before_data` JSON, `after_data` JSON), `cafe_settings` (pajak %, biaya layanan %, mode konfirmasi booking auto/manual, durasi sesi meja), `operating_hours`, `special_closures`, `backup_logs`.
> 
> **Isi `seed.sql` dengan data awal:**
> - 1 akun admin default (`admin` / password ter-hash `password_hash`).
> - 4 Area reservasi (Indoor, Outdoor, Smoking, VIP) dan **15 Meja (`Table 01` s/d `Table 15`)** lengkap dengan koordinat posisi denah (`position_x`, `position_y`) dan kapasitas.
> - 3 Metode pembayaran (Cash at Counter: 60 menit, Bank Transfer: 30 menit, QRIS Statis: 15 menit).
> - Pengaturan default kafe (Jam operasional 09.00–22.00 WIB, Pajak 10%, Service Charge 5%) serta contoh kategori & menu kopi/makanan."

---

## STEP 3: UI Layout Foundation & Admin Authentication

**Prompt untuk AI Agent:**
> "Bangun sistem autentikasi Admin dan kerangka antarmuka (UI/UX) sesuai PRD Bagian 9.
> **Ketentuan Desain:**
> - Tema **Light Mode** saja, konsep *Fresh & Modern* menggunakan **Bootstrap 5**.
> - Palet warna CSS custom properties: Primary Green `#2E7D52`, Accent Orange `#F4A340`, Background `#F7F8F5`, Text `#26332B`, White `#FFFFFF`.
> - Buat dua layout utama: `resources/views/layouts/customer.php` (Navbar responsif, keranjang badge counter dari LocalStorage, footer info kafe) dan `resources/views/layouts/admin.php` (Sidebar navigasi lengkap untuk 18 modul, topbar profil admin, alert notifikasi).
> 
> **Ketentuan Keamanan Login Admin:**
> - Implementasikan `AuthController` (`login`, `logout`, `updateProfile`, `updatePassword`).
> - Gunakan `password_verify()`, regenerasi session ID (`session_regenerate_id(true)`) setelah login.
> - Batasi maksimal **5 kali percobaan gagal** per username/IP; jika tercapai, kunci akun sementara selama **15 menit** dan catat ke `admin_login_attempts` serta `activity_logs`.
> - Terapkan **session timeout otomatis setelah 30 menit tidak aktif** pada `AuthMiddleware`."

---

## STEP 4: Menu Management, Inventory Hybrid, & Customer Catalog

**Prompt untuk AI Agent:**
> "Implementasikan modul Manajemen Kategori, Produk, Stok Hybrid (Admin), serta Katalog Menu & Beranda (Customer) sesuai PRD Bagian 6.1, 6.2, dan 6.8.
> **Fitur Admin:**
> - CRUD Kategori: larang penghapusan kategori yang masih memiliki produk; wajibkan minimal 1 kategori aktif.
> - CRUD Produk (tanpa varian/add-ons): nama, kategori, foto (validasi ekstensi/MIME), deskripsi, harga, stok, `min_stock`, batas min/max pembelian, status aktif.
> - **Aturan Produk Historis:** Jika `has_order_history = 1` (pernah dipesan), **blokir penghapusan produk dan kunci input harga** agar tidak bisa diubah (admin hanya boleh menonaktifkan produk).
> - Manajemen Stok Hybrid (`StockService`): penambahan stok menggunakan sistem penjumlahan (`stok_lama + tambahan`), peringatan *Low Stock* jika `stok <= min_stock`, ubah status manual (`Available`, `Sold Out`, `Unavailable`), dan *bulk status update*. Aturan: jika stok 0 otomatis `Sold Out`; jika status manual `Unavailable`, penambahan stok tidak boleh otomatis mengubahnya menjadi `Available`. Catat semua perubahan ke `activity_logs`.
> 
> **Fitur Customer:**
> - **Homepage:** Banner statis, info singkat kafe, menu unggulan, daftar kategori, produk populer, testimonial yang disetujui admin, alamat & telepon, serta tombol CTA ke Menu dan Reservasi.
> - **Halaman Menu & Detail Produk:** Tampilkan produk aktif secara alfabetis, fitur pencarian nama, filter kategori, badge status ketersediaan, serta tombol tambah ke keranjang."

---

## Status Implementasi dan Panduan Kerja Repository

Bagian blueprint sebelumnya adalah spesifikasi bertahap, bukan instruksi untuk membuat proyek duplikat. Repository sudah memiliki router aktif di `public/index.php`; selalu periksa kode aktual sebelum mengimplementasikan fitur agar tidak mengganti atau menduplikasi pekerjaan yang sudah ada.

### Modul Admin

Semua grup menu sidebar di `resources/views/layouts/admin.php` harus mengarah ke fitur yang dapat dibuka dan dikelola:

- Ringkasan: Dashboard.
- Operasional: Pesanan dan Pembayaran.
- Menu & Inventaris: Kategori, Produk, Stok.
- Reservasi: Reservasi, Meja, Area Reservasi.
- Pelanggan & Promosi: Pelanggan, Voucher dan Promosi, Ulasan, Testimonial.
- Analisis: Laporan dan Activity Log.
- Sistem: Pengaturan Kafe, Akun Admin, Backup dan Restore.

Rute admin harus tetap di balik `AuthMiddleware`; setiap perubahan data harus memakai POST, `CsrfMiddleware`, prepared statement PDO, transaksi database bila beberapa tabel berubah, dan pencatatan `activity_logs`. File backup dan unggahan harus tetap di luar web root. Menu aktif dibuat tebal dan jelas; menu lain tetap mudah dibaca. Gunakan UI admin yang ringan, profesional, responsif, dengan status, filter, empty state, dan konfirmasi untuk aksi sensitif.

### Menjalankan dan Memeriksa Aplikasi

1. Ikuti langkah instalasi pada `README.md`; gunakan skema `database/migrations/schema.sql` dan seeder `database/seeds/seed.sql`.
2. Docker Compose menjalankan seeder otomatis hanya saat volume database MySQL dibuat pertama kali. Impor manual diperlukan untuk volume database yang sudah ada.
3. Admin seed awal menggunakan `admin` / `password`; seeder memakai `INSERT IGNORE`, sehingga tidak mereset akun yang telah ada.
4. Sebelum menyatakan pekerjaan selesai, jalankan `php -l` pada semua berkas PHP yang diubah. Jalankan pemeriksaan fitur/read query dengan database pengembangan bila tersedia. Jangan melakukan restore, menghapus volume, atau mengubah kredensial database tanpa izin eksplisit.
5. Pertahankan teks UI dalam Bahasa Indonesia, format Rupiah, dan zona waktu `Asia/Jakarta`. Perbarui dokumentasi langsung terkait bila rute, instalasi, atau perilaku operasional berubah.
