# CozyCafe

Aplikasi pemesanan dan reservasi kafe berbasis Native PHP 8.2+ dan MySQL 8.0. Pengembangan lokal juga diuji dengan MariaDB 10.11 yang disertakan dalam XAMPP.

## Menjalankan dengan Docker

1. Salin `.env.example` menjadi `.env`.
2. Jalankan `docker compose up -d --build`.
3. Buka `http://localhost:8080`.

## Menjalankan dengan XAMPP

1. Letakkan proyek di `C:\xampp\htdocs\pos-cozycafe`.
2. Jalankan Apache dan MySQL dari XAMPP, serta pastikan modul Apache `mod_rewrite` aktif.
3. Jika database belum dibuat, impor `database/migrations/schema.sql`, kemudian impor `database/seeds/seed.sql` melalui phpMyAdmin atau klien MySQL.
4. Salin `.env.example` menjadi `.env` hanya jika kredensial MySQL lokal berbeda dari nilai default, lalu sesuaikan koneksinya.
5. Buka `http://localhost/pos-cozycafe/public/`. Halaman admin tersedia di `http://localhost/pos-cozycafe/public/admin/login`.

Akun admin awal adalah `admin` dengan password `password`. Ganti password awal melalui menu **Akun Admin → Ubah Password** sebelum aplikasi digunakan.
