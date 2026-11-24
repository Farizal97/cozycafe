# CozyCafe

Aplikasi pemesanan dan reservasi kafe menggunakan PHP 8.2+, PDO, MySQL 8.0/MariaDB 10.11, Bootstrap 5, dan JavaScript. Tampilan berbahasa Indonesia dan menggunakan zona waktu WIB.

## Kebutuhan

- PHP 8.2 atau lebih baru dengan ekstensi `pdo_mysql`, `fileinfo`, dan `mbstring`.
- MySQL 8.0 atau MariaDB 10.11.
- Apache dengan `mod_rewrite` untuk XAMPP; Docker Compose adalah alternatifnya.
- Composer untuk memasang dependensi QR dan ekspor dokumen bila fitur tersebut digunakan.

## Menjalankan dengan Docker

1. Pasang dan jalankan Docker Desktop.
2. Dari folder proyek, salin `.env.example` menjadi `.env`. Nilai default `.env.example` sudah sesuai dengan service Docker lokal. Ganti password database sebelum dipakai di server.
3. Jalankan `docker compose up -d --build`.
4. Pada database Docker yang baru, `schema.sql` dan `seed.sql` dijalankan otomatis saat MySQL pertama kali membuat volume database.
5. Buka `http://localhost:8080`.
6. Untuk menghentikan aplikasi, jalankan `docker compose down`. Data database tetap disimpan pada volume Docker.

**Penting:** Skrip inisialisasi database Docker hanya dijalankan pada volume baru. Untuk volume lama, impor `database/migrations/schema.sql` lalu `database/seeds/seed.sql` secara manual melalui klien MySQL. Jangan menghapus volume database jika datanya masih diperlukan.

## Menjalankan dengan XAMPP

1. Letakkan proyek di `C:\xampp\htdocs\pos-cozycafe`.
2. Buka XAMPP Control Panel dan jalankan **Apache** serta **MySQL**. Pastikan Apache `mod_rewrite` aktif.
3. Buka `http://localhost/phpmyadmin`, pilih **Import**, impor `database/migrations/schema.sql`, lalu impor `database/seeds/seed.sql`.
4. Salin `.env.example` menjadi `.env` di folder proyek. Untuk konfigurasi MySQL XAMPP default, gunakan `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=cozycafe`, `DB_USERNAME=root`, `DB_PASSWORD=` (kosong). Jika MySQL memakai password, masukkan nilainya di `DB_PASSWORD`.
5. Buka `http://localhost/pos-cozycafe/public/`. Halaman masuk admin: `http://localhost/pos-cozycafe/public/admin/login`.
6. Akun awal dari seeder: **username `admin`**, **password `password`**. Setelah berhasil masuk, segera ubah melalui **Akun Admin → Ubah Password**.

Jika password awal tidak berfungsi karena database sudah pernah di-seed sebelumnya, lakukan reset password admin melalui prosedur administrasi database yang Anda kendalikan. Seeder menggunakan `INSERT IGNORE`, sehingga impor ulang seed tidak mengganti password akun yang sudah ada.

## Konfigurasi dan penyimpanan

- Pengaturan environment dibaca dari `.env`; jangan commit file tersebut.
- Upload produk dan bukti pembayaran tersimpan di `storage/`, bukan di web root.
- Backup database tersimpan di `backups/`, bukan di web root. Backup database tidak menyertakan file upload; salin folder `storage/` secara terpisah.
- Untuk Docker, volume `storage/`, `backups/`, dan database tetap disimpan saat container dihentikan.

## Fitur admin

Setelah login, sidebar menyediakan Dashboard, Kategori, Produk, Stok, Pesanan, Pembayaran, Reservasi, Meja, Area Reservasi, Pelanggan, Voucher dan Promosi, Ulasan, Testimonial, Laporan, Activity Log, Pengaturan Kafe, Akun Admin, serta Backup dan Restore.

- Pembayaran mendukung verifikasi bukti, konfirmasi tunai, dan pencatatan pengembalian dana.
- Backup/restore tersedia melalui menu **Backup dan Restore**. Restore mengganti seluruh data database, memerlukan password admin dan konfirmasi tertulis `RESTORE`. Lakukan backup terlebih dahulu.
- Menu Backup tidak sama dengan snapshot file upload: foto produk dan bukti pembayaran perlu dicadangkan terpisah.

## Tahap 4 — Katalog, Produk, dan Stok

Beranda dan katalog pelanggan tersedia di `/` dan `/menu`, termasuk pencarian, filter kategori, detail produk, serta tombol keranjang berbasis LocalStorage. Admin dapat mengelola kategori di `/admin/categories`, produk di `/admin/products`, dan stok di `/admin/stock`.

Foto produk JPG, PNG, dan WebP (maksimal 5 MB) disimpan di `storage/uploads/products/`, di luar web root, lalu disajikan melalui rute media aplikasi.

## Tahap 5 — Keranjang dan Checkout

Keranjang pelanggan tersedia di `/cart`; data LocalStorage dikirim sebagai ID produk dan jumlah saja. Server menghitung ulang harga, stok, diskon otomatis atau satu voucher (tidak ditumpuk), pajak, dan biaya layanan pada `/checkout`. Pesanan baru dibuat setelah pelanggan meninjau dan mengonfirmasi ringkasan. Stok tidak dikurangi saat checkout; pengurangan dilakukan ketika admin mengonfirmasi pesanan.

