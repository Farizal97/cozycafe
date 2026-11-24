# PRODUCT REQUIREMENTS DOCUMENT (PRD)
# CozyCafe — Web Application Pemesanan dan Reservasi Kafe

**Versi:** 1.0  
**Status:** Final Draft  
**Platform:** Responsive Web Application  
**Bahasa:** Bahasa Indonesia  
**Target Deployment:** Localhost (XAMPP / Laragon)  
**Backend:** Native PHP  
**Database:** MySQL  
**Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5

---

# 1. Executive Summary

CozyCafe adalah aplikasi web untuk membantu pelanggan melakukan pemesanan makanan dan minuman serta reservasi meja secara online.

Aplikasi terdiri dari dua bagian utama:

1. **Customer Website:** Website publik yang digunakan pelanggan untuk melihat menu, memesan makanan dan minuman, melakukan reservasi meja, mengunggah bukti pembayaran, serta melacak pesanan dan reservasi.
2. **Admin Dashboard:** Panel administrasi untuk mengelola menu, kategori, stok, pesanan, pembayaran, reservasi, meja, promosi, pelanggan, laporan, dan pengaturan kafe.

CozyCafe menggunakan sistem Guest Checkout sehingga pelanggan tidak perlu membuat akun atau login.

Admin hanya terdiri dari satu akun dengan akses penuh.

Sistem dibangun menggunakan Native PHP, JavaScript, Bootstrap 5, dan MySQL dengan PHP PDO.

## 1.1 Product Vision

Menyediakan sistem pemesanan dan reservasi kafe yang mudah digunakan, responsif, terstruktur, serta membantu operasional kafe menjadi lebih efisien.

## 1.2 Value Proposition

- Pelanggan dapat memesan tanpa harus datang atau menelepon kafe.
- Pelanggan dapat melihat menu, harga, dan ketersediaan produk.
- Pelanggan dapat memilih meja melalui denah interaktif.
- Pelanggan dapat melacak pesanan dan reservasi secara online.
- Admin dapat mengelola operasional kafe melalui satu dashboard.
- Data transaksi dan reservasi tersimpan secara terpusat.

---

# 2. Background dan Problem Statement

## 2.1 Permasalahan Saat Ini

1. Pelanggan harus datang langsung atau menghubungi kafe untuk melakukan pemesanan.
2. Pelanggan kesulitan mengetahui menu dan harga terbaru.
3. Proses pemesanan secara manual membutuhkan waktu.
4. Pelanggan kesulitan mengetahui ketersediaan meja.
5. Reservasi melalui telepon atau WhatsApp sulit dikelola secara terstruktur.
6. Admin kesulitan mengelola pesanan, pembayaran, stok, dan reservasi secara bersamaan.

## 2.2 Solusi

CozyCafe menyediakan satu aplikasi terintegrasi yang menangani proses pemesanan, pembayaran manual, pengelolaan stok, reservasi meja, dan pelaporan.

---

# 3. Product Goals dan Non-Goals

## 3.1 Product Goals

- Menyediakan pemesanan makanan dan minuman secara online.
- Menyediakan reservasi meja menggunakan denah interaktif.
- Mempermudah pelanggan melakukan pembayaran dan melacak pesanan.
- Mempermudah admin mengelola seluruh operasional.
- Menyediakan laporan penjualan dan reservasi.
- Menjaga integritas stok dan ketersediaan meja.
- Menyediakan riwayat transaksi dan aktivitas administrasi.

## 3.2 Non-Goals

Fitur berikut tidak termasuk dalam ruang lingkup versi pertama:

- Customer registration dan login.
- Multi-admin dan multi-role.
- Payment gateway.
- QRIS dinamis.
- Integrasi WhatsApp dan email.
- PWA dan aplikasi mobile native.
- Multi-language.
- Dark mode.
- Scheduled order.
- Modul inventaris bahan baku.
- Pencatatan biaya operasional dan laba bersih.
- Pengelolaan banner melalui dashboard.
- Halaman FAQ.
- Google Maps.
- Sistem delivery dan kurir.
- Pengelolaan varian dan add-ons produk.
- Pencatatan catatan khusus pelanggan pada pesanan.
- Pengelolaan akun staf dan kasir.

---

# 4. Target Users dan Personas

## 4.1 Customer

Pelanggan yang ingin membeli makanan/minuman atau memesan meja di CozyCafe.

Kebutuhan:
- Melihat menu dan harga.
- Mencari makanan atau minuman.
- Memesan tanpa membuat akun.
- Memilih metode pembayaran.
- Mengunggah bukti pembayaran.
- Memilih meja.
- Melakukan reservasi.
- Melacak pesanan dan reservasi.
- Memberikan ulasan setelah transaksi selesai.

## 4.2 Admin

Satu pengguna yang memiliki akses penuh terhadap dashboard.

Kebutuhan:
- Mengelola menu dan kategori.
- Mengatur stok dan ketersediaan.
- Mengelola pesanan.
- Memverifikasi pembayaran.
- Mengelola reservasi dan meja.
- Mengatur promosi dan voucher.
- Melihat data pelanggan.
- Mengelola laporan.
- Mengelola konfigurasi aplikasi.
- Melakukan backup dan restore database.

---

# 5. Scope dan Modul Aplikasi

CozyCafe memiliki dua area utama:

## 5.1 Customer Website

1. Beranda.
2. Daftar menu.
3. Detail produk.
4. Pencarian dan filter kategori.
5. Keranjang belanja.
6. Checkout.
7. Pemesanan Dine-in.
8. Pemesanan Takeaway.
9. Reservasi meja.
10. Pelacakan pesanan.
11. Pelacakan reservasi.
12. Riwayat pesanan.
13. Riwayat reservasi.
14. Ulasan dan rating.
15. Informasi kontak.

## 5.2 Admin Dashboard

1. Dashboard statistik.
2. Manajemen kategori.
3. Manajemen produk.
4. Manajemen stok.
5. Manajemen pesanan.
6. Manajemen pembayaran.
7. Manajemen reservasi.
8. Manajemen meja.
9. Manajemen area reservasi.
10. Manajemen pelanggan.
11. Manajemen voucher dan promosi.
12. Manajemen ulasan.
13. Manajemen testimonial.
14. Laporan dan ekspor.
15. Activity Log.
16. Pengaturan kafe.
17. Pengaturan akun admin.
18. Backup dan restore database.

---

# 6. Functional Requirements

## 6.1 Homepage

Website menampilkan:

- Logo dan nama CozyCafe.
- Navigasi utama.
- Informasi singkat kafe.
- Banner dan konten statis.
- Menu unggulan.
- Kategori menu.
- Produk populer.
- Testimonial pelanggan yang sudah disetujui admin.
- Informasi alamat dan nomor telepon.
- Tombol menuju halaman menu dan reservasi.

Banner dan konten halaman utama dikelola langsung melalui file website.

## 6.2 Menu Management

Pelanggan dapat:

- Melihat seluruh produk aktif.
- Melihat foto produk.
- Melihat nama produk.
- Melihat deskripsi.
- Melihat harga.
- Melihat status ketersediaan.
- Mencari produk berdasarkan nama.
- Memfilter produk berdasarkan kategori.

Setiap produk hanya memiliki satu harga dan satu pilihan.

Tidak terdapat varian ukuran, topping, add-ons, atau pilihan tingkat gula.

### Admin

Admin dapat melakukan CRUD kategori dan produk dengan ketentuan:

- Produk memiliki nama, kategori, foto, deskripsi, harga, stok, dan status.
- Produk dapat diaktifkan atau dinonaktifkan.
- Produk dapat memiliki batas minimum dan maksimum pembelian.
- Produk memiliki batas minimum stok untuk peringatan Low Stock.
- Admin dapat mengubah status beberapa produk sekaligus.
- Produk dan kategori ditampilkan secara alfabetis.

### Aturan penghapusan produk

Produk yang pernah dipesan tidak boleh dihapus.

Admin hanya dapat menonaktifkan produk tersebut.

Harga produk yang sudah pernah dipesan juga tidak boleh diubah. Jika ingin menjual produk dengan harga berbeda, admin dapat menonaktifkan produk lama dan membuat produk baru.

### Aturan penghapusan kategori

- Kategori yang masih memiliki produk harus memindahkan produknya ke kategori lain terlebih dahulu.
- Kategori kosong dapat dihapus.
- Minimal harus terdapat satu kategori aktif.

## 6.3 Shopping Cart

Pelanggan dapat:

- Menambahkan produk ke keranjang.
- Mengubah jumlah produk.
- Mengurangi jumlah produk.
- Menghapus produk.
- Melihat subtotal.
- Melihat total sementara.

Keranjang disimpan menggunakan Browser LocalStorage.

Keranjang tetap tersedia setelah halaman di-refresh atau browser ditutup selama data browser tidak dihapus.

Sistem wajib memvalidasi ulang harga, stok, dan batas pembelian di server ketika checkout.

## 6.4 Checkout

Pelanggan tidak perlu login.

Informasi yang wajib diisi:

- Nama pelanggan.
- Nomor telepon.
- Jenis pesanan.
- Metode pembayaran.

Sebelum pesanan dikirim, sistem menampilkan ringkasan lengkap:

- Produk.
- Jumlah.
- Harga.
- Subtotal.
- Diskon.
- Voucher.
- Pajak.
- Biaya layanan.
- Metode pembayaran.
- Total pembayaran.

Pelanggan harus melakukan konfirmasi sebelum pesanan dibuat.

### Jenis pesanan

**Dine-in**
- Dengan reservasi meja.
- Tanpa reservasi meja.

**Takeaway**
- Pelanggan mengambil pesanan di kafe.
- Tidak membutuhkan meja.
- Tidak dikenakan biaya kemasan tambahan.

Tidak ada fitur Scheduled Order. Semua pesanan diproses segera setelah checkout dan konfirmasi admin.

### Batas pesanan aktif

Pelanggan boleh memiliki maksimal:
- Satu pesanan Dine-in aktif.
- Satu pesanan Takeaway aktif.

Setiap pesanan mempunyai kode unik masing-masing.

## 6.5 Payment Management

CozyCafe mendukung tiga pilihan pembayaran:

1. Cash at Counter.
2. Bank Transfer.
3. QRIS Statis.

Tidak menggunakan payment gateway.

### Cash at Counter

- Pelanggan memilih pembayaran tunai.
- Admin menerima pembayaran di kasir.
- Admin menekan tombol Konfirmasi Pembayaran Tunai.
- Status pembayaran berubah menjadi Paid.

### Bank Transfer dan QRIS

- Pelanggan melihat instruksi pembayaran.
- Pelanggan melakukan pembayaran.
- Pelanggan mengunggah bukti pembayaran.
- Admin memverifikasi bukti.
- Admin menyetujui atau menolak pembayaran.

### Konfigurasi metode pembayaran

Admin dapat:

- Mengaktifkan atau menonaktifkan Cash at Counter.
- Mengaktifkan atau menonaktifkan Bank Transfer.
- Mengaktifkan atau menonaktifkan QRIS.
- Mengubah nama bank.
- Mengubah nomor rekening.
- Mengubah nama pemilik rekening.
- Mengunggah gambar QRIS.
- Mengubah instruksi pembayaran.

### Upload bukti pembayaran

Format:
- JPG.
- JPEG.
- PNG.
- PDF.

Batas ukuran: maksimal 5 MB per file.

Satu file untuk setiap pengiriman bukti pembayaran.

Keamanan:
- Validasi MIME type di server.
- Validasi ekstensi.
- Nama file dibuat secara acak.
- File disimpan di luar direktori publik.
- File tidak boleh dapat dieksekusi sebagai script.
- Akses file dilakukan melalui endpoint terproteksi.

### Rejected Payment

Jika admin menolak bukti pembayaran:

- Admin wajib memberikan alasan.
- Pelanggan dapat mengunggah bukti baru tanpa batas jumlah percobaan.
- Bukti lama tetap disimpan.
- Bukti terbaru masuk antrean verifikasi.

### Payment Deadline

Admin dapat mengatur batas waktu pembayaran secara terpisah untuk setiap metode pembayaran.

Contoh konfigurasi:
- Bank Transfer: 30 menit.
- QRIS: 15 menit.
- Cash at Counter: 60 menit.

Nilai tersebut dapat diubah admin.

Sistem menyediakan countdown pada halaman pelacakan pesanan.

Ketika deadline berakhir tanpa penyelesaian pembayaran:
- Status pesanan menjadi Expired.
- Data pesanan tidak dihapus.
- Admin dapat mengaktifkan kembali pesanan dengan deadline baru.
- Pelanggan dapat meminta perpanjangan pembayaran melalui halaman pelacakan.
- Admin dapat menyetujui atau menolak permintaan.

### Payment Status

- Unpaid.
- Waiting Verification.
- Paid.
- Rejected.
- Refunded.

Status pesanan dan status pembayaran harus disimpan sebagai dua informasi berbeda.

## 6.6 Refund Management

Refund hanya dapat diputuskan oleh admin.

Pelanggan tidak memiliki fitur pengajuan refund mandiri.

Admin melakukan pengembalian dana secara manual di luar aplikasi.

Sistem menyediakan pencatatan:
- Nominal refund.
- Tanggal refund.
- Bukti refund.
- Catatan admin.

Status pembayaran diperbarui menjadi Refunded jika proses refund sudah dicatat sesuai kebijakan admin.

## 6.7 Order Management

### Order Status

- Pending.
- Processing.
- Ready.
- Completed.
- Cancelled.
- Expired.
- Cancellation Requested.
- Cancellation Rejected.

Admin mengubah status secara manual sesuai kondisi aktual.

Alur utama:

Pending → Processing → Ready → Completed.

### Admin

Admin dapat:
- Melihat daftar pesanan.
- Memfilter tanggal.
- Memfilter status.
- Melihat detail pesanan.
- Mengonfirmasi pesanan.
- Mengubah status pesanan.
- Mengelola pembayaran.
- Menyetujui atau menolak pembatalan.
- Membatalkan pesanan secara langsung.

Pengelolaan pesanan menggunakan filter tanggal dan status tanpa kolom pencarian.

### Customer Cancellation

Pelanggan dapat mengajukan pembatalan melalui halaman pelacakan.

Pelanggan harus memberikan alasan.

Admin dapat:
- Menyetujui pembatalan.
- Menolak pembatalan.
- Membatalkan pesanan secara langsung.

Jika pembatalan disetujui, status berubah menjadi Cancelled.

Pengembalian stok tidak otomatis. Admin menentukan apakah stok perlu dikembalikan.

### Admin Walk-in Order

Admin dapat membuat pesanan untuk pelanggan walk-in melalui dashboard.

Data pelanggan wajib:
- Nama.
- Nomor telepon.

Pesanan online tetap dibuat oleh pelanggan melalui website.

### Perubahan pesanan

Pesanan yang sudah dikonfirmasi tidak dapat diedit secara manual.

Jika pelanggan ingin mengubah pesanan, pelanggan harus mengajukan pembatalan dan membuat pesanan baru.

Pengecualian adalah proses substitusi produk yang memerlukan persetujuan pelanggan.

## 6.8 Inventory Management

Stok menggunakan sistem hybrid:
- Pengelolaan kuantitas otomatis.
- Pengaturan ketersediaan manual.

Admin dapat:
- Menambahkan stok.
- Mengatur batas minimum stok.
- Mengubah status Available.
- Mengubah status Sold Out.
- Mengubah status Unavailable.
- Melihat peringatan Low Stock.
- Mengubah status beberapa produk sekaligus.

Penambahan stok menggunakan metode penjumlahan.

Contoh:
Stok saat ini 10, tambahan stok 20, stok akhir 30.

Admin tidak wajib mengisi alasan perubahan stok.

Semua perubahan tetap dicatat dalam Activity Log.

### Stock Deduction

Stok dikurangi setelah admin mengonfirmasi pesanan.

Sistem harus:
- Memeriksa stok terbaru sebelum konfirmasi.
- Mencegah stok minus.
- Mencegah stok dikurangi dua kali akibat request ganda.
- Menggunakan transaksi database saat konfirmasi pesanan.

### Stock Availability

Jika stok mencapai nol, produk otomatis menjadi Sold Out.

Status manual Unavailable tidak boleh otomatis berubah menjadi Available hanya karena stok bertambah.

### Substitution

Jika stok berubah dan produk yang sudah dipesan ternyata tidak dapat dipenuhi:

1. Admin memilih produk pengganti.
2. Sistem menampilkan harga produk pengganti.
3. Pelanggan dapat menerima atau menolak.
4. Jika pelanggan menerima, sistem menyesuaikan total pembayaran.
5. Jika pelanggan menolak, item pengganti tidak dimasukkan.
6. Jika tidak ada respons dalam 5 menit, penawaran otomatis dibatalkan.
7. Item lainnya tetap dapat diproses.

Admin tidak boleh mengganti produk tanpa persetujuan pelanggan.

### Quantity Limits

Setiap produk memiliki:
- Minimum purchase quantity.
- Maximum purchase quantity.

Jumlah pembelian tidak boleh melebihi stok yang tersedia.

Jika stok tidak mencukupi, sistem membatasi kuantitas yang dapat dimasukkan ke keranjang atau checkout.

## 6.9 Pricing, Tax, Service Charge, dan Promotions

Admin dapat mengatur:
- Pajak.
- Biaya layanan.
- Diskon otomatis.
- Voucher.

Pajak dan biaya layanan dapat diaktifkan atau dinonaktifkan secara independen.

Biaya layanan memiliki tarif yang sama untuk Dine-in dan Takeaway.

Tidak ada biaya kemasan Takeaway.

### Contoh perhitungan

Subtotal: Rp100.000  
Diskon 10%: Rp10.000  
Subtotal setelah diskon: Rp90.000  
Pajak 10%: Rp9.000  
Biaya layanan 5%: Rp4.500  

Total: **Rp103.500**

Pajak dan biaya layanan dihitung dari subtotal setelah diskon.

### Voucher

Admin dapat mengatur:
- Kode voucher.
- Jenis diskon persentase atau nominal.
- Minimum pembelian.
- Masa berlaku.
- Total penggunaan.
- Batas penggunaan per nomor telepon.
- Status aktif/nonaktif.

Satu pesanan hanya boleh menggunakan satu voucher.

Sistem mencegah penumpukan voucher dan diskon yang tidak diizinkan.

## 6.10 Table Booking Management

CozyCafe memiliki 15 meja:
- Table 01 sampai Table 15.

Setiap meja memiliki:
- Nomor meja.
- Kapasitas.
- Area.
- Posisi pada denah.
- Status aktif/nonaktif.

Admin dapat mengelola area:
- Membuat area.
- Mengubah area.
- Menghapus area.
- Mengatur deskripsi.
- Mengaktifkan atau menonaktifkan area.
- Menghubungkan meja ke area.

Contoh area:
- Indoor.
- Outdoor.
- Smoking.
- VIP.

Area dapat disesuaikan oleh admin.

### Booking Flow

1. Pelanggan memilih tanggal hari ini.
2. Pelanggan memilih slot waktu.
3. Pelanggan memilih area.
4. Sistem menampilkan denah meja.
5. Pelanggan memasukkan jumlah tamu.
6. Sistem memfilter meja sesuai kapasitas.
7. Pelanggan memilih satu meja.
8. Pelanggan mengisi nama dan nomor telepon.
9. Sistem membuat reservasi.
10. Pelanggan menerima kode reservasi dan QR Code.

### Booking Slots

Durasi reservasi tetap 3 jam.

Slot:
- 09.00–12.00.
- 12.00–15.00.
- 15.00–18.00.
- 18.00–21.00.

Jam operasional default: 09.00–22.00 WIB.

Reservasi hanya dapat dibuat untuk hari yang sama.

Admin dapat mengubah hari dan jam operasional, hari libur, serta penutupan sementara.

### Booking Confirmation Mode

Admin dapat memilih:

**Automatic**
- Sistem memeriksa ketersediaan.
- Reservasi langsung Confirmed jika memenuhi seluruh ketentuan.

**Manual**
- Reservasi masuk dengan status Pending.
- Admin menyetujui atau menolak.

### Booking Status

- Pending.
- Confirmed.
- Rejected.
- Completed.
- Cancelled.
- Cancellation Requested.
- Checked-In.
- No-Show.

### Booking Cancellation

Pelanggan tidak dapat langsung mengubah atau membatalkan reservasi.

Pelanggan dapat mengajukan pembatalan dengan alasan.

Admin menyetujui atau menolak.

Jika disetujui, meja kembali tersedia untuk slot terkait.

Jika pelanggan ingin mengubah tanggal, waktu, atau meja, pelanggan harus mengajukan pembatalan dan membuat reservasi baru setelah pembatalan disetujui.

### Booking No-Show

Sistem otomatis menetapkan No-Show jika:
- Reservasi berstatus Confirmed.
- Pelanggan belum check-in.
- Sudah 30 menit sejak slot dimulai.

Jika meja tidak lagi dibutuhkan oleh reservasi tersebut, sistem dapat membuatnya tersedia untuk sisa waktu yang memungkinkan.

Reservasi yang sudah Checked-In tidak akan terkena aturan No-Show.

### Booking Check-In

Tersedia dua metode:

1. Pelanggan memindai QR Code reservasi.
2. Admin melakukan check-in melalui dashboard.

Validasi:
- Hanya reservasi Confirmed yang dapat check-in.
- QR Code harus valid.
- Check-in hanya dapat dilakukan satu kali.
- Sistem mencatat waktu dan metode check-in.

### Multiple Tables

Pelanggan hanya dapat memilih satu meja untuk reservasi mandiri.

Admin dapat menggabungkan beberapa meja ke satu reservasi jika:
- Semua meja tersedia pada slot yang sama.
- Kapasitas gabungan mencukupi.
- Satu kode reservasi digunakan.

### Booking Deposit

Reservasi tidak membutuhkan uang muka.

## 6.11 Dine-in Without Reservation

Pelanggan dapat memilih Dine-in tanpa membuat reservasi.

Pelanggan:
- Melihat denah meja.
- Memilih meja yang tersedia.
- Melanjutkan checkout.

Sistem memeriksa konflik dengan reservasi dan penggunaan meja lainnya.

Durasi penggunaan meja tanpa reservasi dapat diatur admin.

Penggunaan meja:
- Berakhir otomatis setelah durasi habis.
- Dapat diakhiri lebih awal oleh admin.

Check-in dilakukan melalui:
- QR Code pada meja.
- Admin melalui dashboard.

Check-in mencatat waktu dan memulai durasi penggunaan meja.

QR Code meja berbeda dari QR Code reservasi.

## 6.12 Booking Tracking

Pelanggan dapat melacak reservasi menggunakan:
- Kode reservasi.
- Nomor telepon.

Pelanggan dapat melihat:
- Status.
- Tanggal.
- Slot waktu.
- Area.
- Nomor meja.
- Jumlah tamu.
- Informasi check-in.
- Kode QR.

Kode reservasi menggunakan format:

BK-YYYYMMDD-001.

Pelanggan juga dapat mencetak konfirmasi reservasi atau menyimpannya sebagai PDF.

## 6.13 Order Tracking

Pelanggan melacak pesanan menggunakan:
- Kode pesanan.
- Nomor telepon.

Halaman tracking menampilkan:
- Kode pesanan.
- Detail produk.
- Total pembayaran.
- Metode pembayaran.
- Status pembayaran.
- Status pesanan.
- Countdown pembayaran.
- Riwayat perubahan status.
- Informasi substitusi produk jika ada.

Status diperbarui otomatis menggunakan JavaScript polling tanpa reload halaman.

## 6.14 Customer Order History

Pelanggan dapat melihat seluruh riwayat pesanan dengan memasukkan:
- Nomor telepon.
- Salah satu kode pesanan yang pernah dibuat menggunakan nomor tersebut.

Setelah verifikasi, sistem menampilkan seluruh riwayat pesanan pelanggan.

Informasi meliputi:
- Kode pesanan.
- Tanggal.
- Produk.
- Total.
- Jenis layanan.
- Status pesanan.
- Status pembayaran.

## 6.15 Customer Booking History

Pelanggan dapat melihat riwayat reservasi berstatus:
- Completed.
- Cancelled.

Verifikasi menggunakan:
- Nomor telepon.
- Salah satu kode reservasi yang pernah dibuat menggunakan nomor tersebut.

Reservasi aktif tetap dapat dilihat melalui halaman tracking reservasi.

## 6.16 Reviews dan Ratings

Pelanggan yang telah menyelesaikan pesanan dapat memberikan ulasan.

Verifikasi:
- Kode pesanan.
- Nomor telepon.

Jenis ulasan:
1. Review makanan/minuman.
2. Review pelayanan kafe.

Rating menggunakan skala 1–5 bintang.

Pelanggan dapat menulis komentar.

Ketentuan:
- Hanya produk yang dibeli yang dapat diulas.
- Satu produk hanya dapat diulas satu kali per pesanan.
- Pesanan harus berstatus Completed.
- Admin dapat menyetujui, menyembunyikan, atau menghapus ulasan.
- Dashboard menampilkan rata-rata rating.

### Testimonials

Modul testimonial terpisah dari sistem review.

Admin dapat:
- Memilih review yang disetujui untuk ditampilkan di homepage.
- Menyetujui testimonial.
- Menyembunyikan testimonial.
- Menghapus testimonial.

Hanya testimonial yang telah disetujui yang tampil kepada pelanggan.

## 6.17 Customer Management

Data pelanggan dibuat otomatis dari transaksi.

Pelanggan tidak perlu membuat akun.

Nomor telepon digunakan sebagai identitas penghubung data.

Admin dapat melihat:
- Nama.
- Nomor telepon.
- Riwayat pesanan.
- Riwayat reservasi.
- Total transaksi.
- Ulasan pelanggan.

### Customer Data Deletion

Admin hanya dapat menghapus data pribadi pelanggan jika tidak memiliki pesanan atau reservasi aktif.

Penghapusan dilakukan melalui anonimisasi:
- Nama pribadi dihapus atau diganti.
- Nomor telepon dihapus atau diganti.
- Informasi identitas pribadi dianonimkan.
- Riwayat transaksi keuangan tetap dipertahankan.

Tindakan harus:
- Memerlukan konfirmasi.
- Dicatat dalam Activity Log.

## 6.18 Kitchen Order Ticket (KOT)

Setiap pesanan yang dikonfirmasi admin memicu proses pencetakan tiket dapur.

Isi tiket:
- Logo/nama CozyCafe.
- Kode pesanan.
- Waktu pemesanan.
- Nama pelanggan.
- Jenis layanan.
- Nomor meja jika Dine-in.
- Daftar produk.
- Jumlah.

Pencetakan otomatis membutuhkan printer yang terhubung dan konfigurasi perangkat.

Untuk lingkungan browser standar, pencetakan dapat memerlukan dialog print. Pencetakan tanpa dialog memerlukan konfigurasi tambahan pada komputer kasir.

### Reprint KOT

Admin hanya dapat mencetak ulang jika:
- Terjadi kesalahan pencetakan.
- Tiket sebelumnya hilang.

Admin wajib memilih alasan cetak ulang.

Tiket cetak ulang diberi penanda REPRINT.

Aktivitas dicatat dalam Activity Log.

## 6.19 Receipt dan Print

Pelanggan dapat mencetak atau menyimpan bukti pesanan melalui browser Print / Save as PDF.

Isi bukti pesanan:
- Logo dan nama kafe.
- Kode pesanan.
- Tanggal dan waktu.
- Nama pelanggan.
- Nomor telepon.
- Jenis layanan.
- Daftar produk.
- Jumlah dan harga.
- Diskon.
- Pajak.
- Biaya layanan.
- Metode pembayaran.
- Status pembayaran.
- Total pembayaran.

Bukti reservasi memuat:
- Kode reservasi.
- Nama pelanggan.
- Tanggal.
- Slot.
- Area.
- Nomor meja.
- Kapasitas.
- Jumlah tamu.
- Status reservasi.

## 6.20 Admin Dashboard

Dashboard menampilkan:

- Total pesanan.
- Total pendapatan.
- Jumlah reservasi.
- Produk paling laris.
- Grafik pendapatan harian.
- Grafik pendapatan mingguan.
- Grafik pendapatan bulanan.
- Statistik berdasarkan periode.

Tidak ada modul pencatatan biaya operasional dan laba bersih.

### Admin Authentication

Hanya satu akun admin.

Fitur:
- Login.
- Logout.
- Ubah username.
- Ubah password.
- PHP Session.
- Password hashing.

Keamanan login:
- Maksimal 5 kali login gagal.
- Penguncian sementara 15 menit.
- Login gagal dan penguncian dicatat.

Sesi admin otomatis berakhir setelah 30 menit tidak aktif.

### Activity Log

Mencatat aktivitas penting:
- Login dan logout.
- Perubahan menu.
- Perubahan harga sebelum produk memiliki transaksi.
- Perubahan stok.
- Perubahan status produk.
- Perubahan pesanan.
- Perubahan pembayaran.
- Perubahan reservasi.
- Perubahan pengaturan.
- Backup dan restore.
- Penghapusan/anonimisasi data pelanggan.
- Cetak ulang KOT.

Setiap catatan menyimpan:
- Waktu.
- Jenis tindakan.
- Data sebelum perubahan.
- Data sesudah perubahan.
- Akun admin.

Activity Log:
- Read-only.
- Tidak dapat diedit.
- Tidak dapat dihapus melalui dashboard.
- Ditampilkan berdasarkan waktu terbaru.
- Tidak menyediakan pencarian atau filter.

## 6.21 Reports

Laporan:
- Laporan transaksi.
- Laporan pesanan.
- Laporan pembayaran.
- Laporan reservasi.
- Laporan pendapatan.

Filter laporan:
- Rentang tanggal.
- Jenis transaksi.
- Metode pembayaran.
- Status pembayaran.

Ekspor:
- Excel.
- PDF.

Nilai laporan harus konsisten dengan data transaksi.

## 6.22 Database Backup dan Restore

Backup otomatis:
- Setiap hari.
- Retensi 30 hari.

Backup manual:
- Admin dapat membuat backup kapan saja.
- Admin dapat mengunduh file SQL.

Restore:
- Admin dapat mengunggah backup.
- Sistem meminta konfirmasi sebelum restore.
- Sistem memvalidasi file.
- Sebelum restore, sistem membuat backup pengaman jika memungkinkan.

File backup disimpan di luar public web root.

---

# 7. Business Rules

## 7.1 Order Rules

- Customer tidak membutuhkan akun.
- Nama dan nomor telepon wajib.
- Maksimal satu pesanan Dine-in aktif dan satu Takeaway aktif per pelanggan.
- Pesanan diproses setelah dikonfirmasi admin.
- Stok dikurangi saat admin mengonfirmasi pesanan.
- Harga transaksi disimpan sebagai snapshot.
- Pesanan yang sudah dikonfirmasi tidak dapat diedit.
- Pembatalan pelanggan membutuhkan persetujuan admin.
- Admin menentukan pengembalian stok saat pesanan dibatalkan.

## 7.2 Payment Rules

- Cash at Counter dikonfirmasi admin.
- Bank Transfer dan QRIS diverifikasi admin.
- Bukti pembayaran maksimal 5 MB.
- Pelanggan dapat mengunggah ulang bukti yang ditolak.
- Bukti lama tetap disimpan.
- Payment deadline dapat diatur per metode.
- Pesanan expired tidak dihapus.
- Perpanjangan pembayaran memerlukan persetujuan admin.
- Refund diproses di luar sistem dan dicatat admin.

## 7.3 Booking Rules

- 15 meja tersedia secara default.
- Durasi reservasi 3 jam.
- Hanya same-day booking.
- Slot waktu sudah ditentukan.
- Pelanggan memilih area terlebih dahulu.
- Kapasitas meja harus mencukupi.
- Pelanggan hanya memilih satu meja.
- Admin dapat menggabungkan beberapa meja.
- Tidak ada deposit.
- No-Show otomatis setelah 30 menit.
- Check-in dapat melalui pelanggan atau admin.
- Reservasi tidak dapat diubah langsung oleh pelanggan.

## 7.4 Inventory Rules

- Stok dikurangi setelah konfirmasi admin.
- Stok tidak boleh minus.
- Jumlah pesanan tidak boleh melebihi stok.
- Stok nol menyebabkan Sold Out.
- Admin dapat mengatur status manual.
- Produk Unavailable tidak otomatis menjadi Available setelah stok bertambah.

## 7.5 Data Retention Rules

- Pesanan dan reservasi disimpan tanpa batas waktu.
- Data pribadi dapat dianonimkan sesuai aturan.
- Data transaksi keuangan tetap disimpan.
- Backup otomatis disimpan selama 30 hari.

---

# 8. User Flows

## 8.1 Customer Order Flow

1. Pelanggan membuka website.
2. Pelanggan melihat menu.
3. Pelanggan mencari produk atau memilih kategori.
4. Pelanggan membuka detail produk.
5. Pelanggan menambahkan produk ke keranjang.
6. Pelanggan mengatur jumlah produk.
7. Pelanggan membuka checkout.
8. Pelanggan memilih Dine-in atau Takeaway.
9. Jika Dine-in, pelanggan memilih meja atau menghubungkan reservasi.
10. Pelanggan memasukkan nama dan nomor telepon.
11. Pelanggan memilih metode pembayaran.
12. Sistem menampilkan ringkasan.
13. Pelanggan mengonfirmasi pesanan.
14. Sistem membuat kode pesanan.
15. Admin menerima pesanan.
16. Admin memeriksa dan mengonfirmasi.
17. Sistem mengurangi stok.
18. Pesanan diproses.
19. Pelanggan melacak status.
20. Pesanan menjadi Ready dan Completed.

## 8.2 Customer Payment Flow

1. Pelanggan memilih Transfer Bank atau QRIS.
2. Sistem menampilkan instruksi.
3. Pelanggan melakukan pembayaran.
4. Pelanggan mengunggah bukti.
5. Status menjadi Waiting Verification.
6. Admin memeriksa bukti.
7. Admin menyetujui atau menolak.
8. Jika ditolak, pelanggan dapat mengunggah bukti baru.
9. Jika disetujui, status pembayaran menjadi Paid.

## 8.3 Customer Booking Flow

1. Pelanggan membuka halaman reservasi.
2. Memilih tanggal hari ini.
3. Memilih slot waktu.
4. Memilih area.
5. Memasukkan jumlah tamu.
6. Memilih meja pada denah.
7. Mengisi nama dan nomor telepon.
8. Mengirim reservasi.
9. Sistem memeriksa ketersediaan.
10. Sistem membuat kode reservasi.
11. Sistem menghasilkan QR Code.
12. Reservasi dikonfirmasi otomatis atau menunggu admin.
13. Pelanggan datang dan check-in.
14. Reservasi selesai.

## 8.4 Admin Order Flow

1. Admin login.
2. Membuka daftar pesanan.
3. Memeriksa detail pesanan.
4. Memeriksa stok.
5. Memeriksa pembayaran jika diperlukan.
6. Mengonfirmasi pesanan.
7. Sistem mengurangi stok.
8. Sistem memicu pencetakan KOT.
9. Admin mengubah status menjadi Processing.
10. Admin mengubah status menjadi Ready.
11. Pesanan diserahkan.
12. Admin mengubah status menjadi Completed.

---

# 9. UI/UX Requirements

## 9.1 Design Direction

Konsep: Fresh & Modern.

Palet warna:

- Primary Green: #2E7D52
- Accent Orange: #F4A340
- Background: #F7F8F5
- Text: #26332B
- White: #FFFFFF

## 9.2 Customer Interface

- Navigasi sederhana.
- Menu menggunakan product cards.
- Foto produk jelas.
- Tombol tambah ke keranjang mudah ditemukan.
- Keranjang mudah diakses.
- Checkout ringkas.
- Denah meja interaktif.
- Tracking status mudah dipahami.
- Responsive untuk smartphone.

## 9.3 Admin Interface

- Sidebar navigation.
- Dashboard statistik.
- Tabel data dengan pagination.
- Form CRUD.
- Modal konfirmasi.
- Badge status.
- Filter dan pencarian sesuai kebutuhan modul.
- Denah meja dengan pengaturan posisi.
- Responsive.

## 9.4 Responsive Design

Website harus mendukung:
- Smartphone.
- Tablet.
- Desktop.

## 9.5 Language

Seluruh tampilan menggunakan Bahasa Indonesia.

## 9.6 Theme

Hanya Light Mode.

---

# 10. Database Design

Database menggunakan MySQL dengan relasi yang menjaga konsistensi transaksi.

Berikut entitas utama yang direkomendasikan.

## 10.1 Admin dan Authentication

**admins**
- id
- username
- password_hash
- created_at
- updated_at

**admin_login_attempts**
- id
- username
- ip_address
- attempted_at
- success
- locked_until

## 10.2 Menu

**categories**
- id
- name
- description
- is_active
- created_at
- updated_at

**products**
- id
- category_id
- name
- description
- image
- price
- stock
- min_stock
- min_order_quantity
- max_order_quantity
- availability_status
- is_active
- has_order_history
- created_at
- updated_at

## 10.3 Customer

**customers**
- id
- name
- phone
- is_anonymized
- created_at
- updated_at

Nomor telepon digunakan untuk menghubungkan transaksi pelanggan.

## 10.4 Orders

**orders**
- id
- order_code
- customer_id
- order_type
- booking_id
- table_id
- subtotal
- discount_amount
- tax_amount
- service_charge
- total_amount
- payment_method_id
- payment_status
- order_status
- payment_deadline
- created_at
- confirmed_at
- completed_at

**order_items**
- id
- order_id
- product_id
- product_name_snapshot
- price_snapshot
- quantity
- subtotal

**order_status_histories**
- id
- order_id
- old_status
- new_status
- changed_at
- changed_by

**order_cancellation_requests**
- id
- order_id
- reason
- status
- admin_response
- created_at
- processed_at

## 10.5 Payments

**payment_methods**
- id
- name
- method_type
- is_enabled
- deadline_minutes
- bank_name
- account_number
- account_holder
- qris_image
- instructions

**payments**
- id
- order_id
- payment_method_id
- amount
- status
- verified_by
- verified_at
- created_at

**payment_proofs**
- id
- payment_id
- file_path
- original_filename
- mime_type
- file_size
- verification_status
- rejection_reason
- uploaded_at
- verified_at

**payment_extensions**
- id
- order_id
- requested_deadline
- request_status
- admin_response
- approved_deadline
- created_at
- processed_at

**refunds**
- id
- order_id
- amount
- refund_date
- proof_path
- admin_note
- created_at

## 10.6 Booking

**booking_areas**
- id
- name
- description
- is_active

**tables**
- id
- table_number
- area_id
- capacity
- position_x
- position_y
- is_active

**bookings**
- id
- booking_code
- customer_id
- booking_date
- slot_start
- slot_end
- guest_count
- status
- qr_token
- confirmation_mode
- checked_in_at
- checkin_method
- created_at

**booking_tables**
- id
- booking_id
- table_id

**booking_cancellation_requests**
- id
- booking_id
- reason
- status
- admin_response
- created_at
- processed_at

**table_sessions**
- id
- table_id
- order_id
- customer_id
- started_at
- expected_end_at
- ended_at
- checkin_method
- status

## 10.7 Promotion

**promotions**
- id
- name
- discount_type
- discount_value
- start_date
- end_date
- minimum_purchase
- is_active

**vouchers**
- id
- code
- discount_type
- discount_value
- minimum_purchase
- total_usage_limit
- per_phone_limit
- start_date
- end_date
- is_active

**voucher_usages**
- id
- voucher_id
- order_id
- customer_id
- phone_snapshot
- used_at

## 10.8 Reviews dan Testimonials

**reviews**
- id
- order_id
- customer_id
- product_id
- review_type
- rating
- comment
- moderation_status
- created_at

**testimonials**
- id
- review_id
- is_approved
- is_visible
- created_at

## 10.9 Activity Log

**activity_logs**
- id
- admin_id
- action
- entity_type
- entity_id
- before_data
- after_data
- created_at

## 10.10 Settings dan Backup

**cafe_settings**
- id
- setting_key
- setting_value
- updated_at

**operating_hours**
- id
- day_of_week
- opening_time
- closing_time
- is_open

**special_closures**
- id
- closure_date
- reason
- is_closed

**backup_logs**
- id
- filename
- backup_type
- file_size
- status
- created_at

## 10.11 Database Constraints

Implementasi wajib menggunakan:
- Primary Key.
- Foreign Key.
- Unique Index.
- Index pada kolom pencarian dan filter.
- NOT NULL untuk data wajib.
- Check constraints jika didukung versi MySQL.
- Transaction untuk proses penting.

Nomor pesanan, kode reservasi, dan token QR harus unik.

Data transaksi menggunakan snapshot nama dan harga produk agar riwayat tidak berubah ketika informasi produk diperbarui.

---

# 11. Technical Architecture

## 11.1 Technology Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5 |
| Styling | CSS3 |
| Framework UI | Bootstrap 5 |
| Client scripting | JavaScript |
| Backend | Native PHP |
| Database | MySQL |
| Database access | PHP PDO |
| Local server | Apache |
| Development environment | XAMPP / Laragon |
| Icons | Bootstrap Icons / Lucide |
| Charts | Chart.js |
| PDF reporting | PHP PDF library |
| Excel reporting | PhpSpreadsheet |
| QR Code | PHP QR Code library |

Semua dependency pihak ketiga harus dipilih dan dipasang secara lokal serta dicatat versinya.

## 11.2 Project Structure

Struktur folder yang direkomendasikan:

```text
cozycafe/
│
├── app/
│   ├── config/
│   │   ├── database.php
│   │   └── app.php
│   │
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   ├── OrderController.php
│   │   ├── PaymentController.php
│   │   ├── BookingController.php
│   │   └── ReportController.php
│   │
│   ├── models/
│   │   ├── Product.php
│   │   ├── Order.php
│   │   ├── Payment.php
│   │   ├── Booking.php
│   │   └── Customer.php
│   │
│   ├── services/
│   │   ├── StockService.php
│   │   ├── PaymentService.php
│   │   ├── BookingService.php
│   │   ├── BackupService.php
│   │   └── NotificationService.php
│   │
│   ├── helpers/
│   └── middleware/
│
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   │   └── fonts/
│   │
│   └── admin/
│
├── resources/
│   └── views/
│       ├── customer/
│       ├── admin/
│       └── layouts/
│
├── database/
│   ├── migrations/
│   └── seeds/
│
├── storage/
│   ├── uploads/
│   ├── logs/
│   └── temp/
│
├── backups/
│
├── vendor/
│
└── README.md
```

Untuk instalasi localhost dengan URL `http://localhost/cozycafe/`, konfigurasi Apache dapat diarahkan ke folder aplikasi. File sensitif dan backup tetap harus berada di luar akses publik.

## 11.3 Database Connection

Semua koneksi database menggunakan PHP PDO.

Ketentuan:
- Prepared statements.
- Error handling.
- Transaction.
- Centralized connection configuration.

## 11.4 Application Architecture

Gunakan pendekatan modular MVC sederhana.

Pemisahan:
- Model untuk database.
- Controller untuk business logic.
- View untuk antarmuka.
- Service untuk proses kompleks.

Tidak menggunakan framework PHP seperti Laravel atau CodeIgniter.

---

# 12. Security Requirements

Keamanan wajib diterapkan pada seluruh aplikasi.

## 12.1 Authentication

- Password admin menggunakan `password_hash()`.
- Verifikasi menggunakan `password_verify()`.
- Session ID diregenerasi setelah login.
- Admin page dilindungi session.
- Logout menghapus session.
- Timeout 30 menit.
- Maksimal 5 login gagal dan lockout 15 menit.

## 12.2 SQL Injection

Seluruh query menggunakan PDO prepared statements.

Tidak menggabungkan input pengguna langsung ke query SQL.

## 12.3 XSS

Semua input yang ditampilkan kembali harus melalui escaping yang sesuai, misalnya `htmlspecialchars()`.

## 12.4 CSRF

Semua form yang mengubah data harus menggunakan CSRF token.

## 12.5 File Upload Security

- Validasi ekstensi.
- Validasi MIME type.
- Batas ukuran.
- Nama file acak.
- Penyimpanan di luar public web root.
- Proteksi terhadap script execution.
- Pemeriksaan hak akses ketika file ditampilkan.

## 12.6 Rate Limiting

Terapkan pembatasan percobaan untuk:
- Login admin.
- Pelacakan pesanan.
- Pelacakan reservasi.
- Akses riwayat pelanggan.
- Pengajuan pembatalan.
- Upload bukti pembayaran.

## 12.7 QR Code Security

QR reservasi dan QR meja harus menggunakan token yang sulit ditebak.

Jangan menggunakan ID database berurutan sebagai satu-satunya mekanisme autentikasi QR.

Validasi status, waktu, dan identitas reservasi sebelum check-in.

## 12.8 Backup Security

Backup tidak boleh dapat diakses langsung dari browser.

File backup hanya dapat diunduh melalui endpoint admin yang terproteksi.

---

# 13. Non-Functional Requirements

## 13.1 Performance

- Halaman utama harus dimuat secara efisien.
- Query database harus menggunakan index yang sesuai.
- Gambar produk harus dioptimalkan.
- Pagination diterapkan pada tabel admin.
- Polling tracking menggunakan interval yang wajar dan dapat dihentikan ketika halaman tidak aktif.

## 13.2 Reliability

- Proses pembayaran tidak boleh tercatat ganda.
- Stok tidak boleh berkurang dua kali.
- Reservasi tidak boleh menggunakan meja yang sama pada waktu yang bertabrakan.
- Checkout harus menggunakan validasi server.
- Proses kritis menggunakan database transaction.

## 13.3 Usability

- Navigasi mudah dipahami.
- Form memiliki validasi.
- Pesan error jelas.
- Status menggunakan label yang konsisten.
- Denah meja mudah digunakan.

## 13.4 Compatibility

Mendukung browser modern:
- Google Chrome.
- Microsoft Edge.
- Mozilla Firefox.
- Safari.

## 13.5 Maintainability

- Kode modular.
- Penamaan konsisten.
- Pemisahan business logic.
- Dokumentasi database.
- Dokumentasi instalasi.
- Konfigurasi aplikasi terpusat.

## 13.6 Localization

- Bahasa Indonesia.
- WIB.
- Format tanggal Indonesia.
- Mata uang IDR.
- Format harga contoh: Rp10.000.

---

# 14. Acceptance Criteria

## 14.1 Customer Website

- [ ] Pelanggan dapat melihat menu aktif.
- [ ] Pelanggan dapat mencari menu berdasarkan nama.
- [ ] Pelanggan dapat memfilter kategori.
- [ ] Pelanggan dapat menambahkan produk ke keranjang.
- [ ] Keranjang tersimpan di LocalStorage.
- [ ] Pelanggan dapat mengubah isi keranjang.
- [ ] Sistem memvalidasi stok ketika checkout.
- [ ] Pelanggan dapat checkout tanpa login.
- [ ] Pelanggan dapat memilih Dine-in atau Takeaway.
- [ ] Pelanggan dapat memilih metode pembayaran.
- [ ] Pelanggan dapat melihat ringkasan sebelum konfirmasi.
- [ ] Pelanggan dapat melacak pesanan menggunakan kode dan nomor telepon.
- [ ] Pelanggan dapat melacak reservasi.
- [ ] Pelanggan dapat mengajukan pembatalan.
- [ ] Pelanggan dapat meminta perpanjangan pembayaran.
- [ ] Pelanggan dapat mengunggah bukti pembayaran.
- [ ] Pelanggan dapat melihat riwayat pesanan.
- [ ] Pelanggan dapat melihat riwayat reservasi yang memenuhi ketentuan.
- [ ] Pelanggan dapat memberikan ulasan setelah pesanan selesai.

## 14.2 Admin Dashboard

- [ ] Admin dapat login dan logout.
- [ ] Admin dapat mengubah username dan password.
- [ ] Admin dapat mengelola kategori.
- [ ] Admin dapat mengelola produk.
- [ ] Admin dapat mengatur stok.
- [ ] Admin dapat melihat peringatan Low Stock.
- [ ] Admin dapat mengonfirmasi pesanan.
- [ ] Admin dapat memverifikasi pembayaran.
- [ ] Admin dapat mengelola reservasi.
- [ ] Admin dapat mengelola meja dan area.
- [ ] Admin dapat mengelola voucher.
- [ ] Admin dapat mengelola pengaturan pembayaran.
- [ ] Admin dapat melihat data pelanggan.
- [ ] Admin dapat mengelola review dan testimonial.
- [ ] Admin dapat melihat Activity Log.
- [ ] Admin dapat mengekspor laporan.
- [ ] Admin dapat membuat dan memulihkan backup.

## 14.3 Business Logic

- [ ] Stok berkurang setelah pesanan dikonfirmasi.
- [ ] Stok tidak pernah menjadi negatif.
- [ ] Meja tidak dapat dipesan ganda pada slot yang sama.
- [ ] No-Show otomatis diproses setelah 30 menit.
- [ ] Check-in tidak dapat dilakukan dua kali.
- [ ] Payment deadline bekerja sesuai metode.
- [ ] Bukti pembayaran ditolak dapat diunggah ulang.
- [ ] Refund tercatat secara manual.
- [ ] Voucher memiliki batas penggunaan.
- [ ] Harga dan total transaksi historis tetap konsisten.

## 14.4 Security

- [ ] Login admin menggunakan password hash.
- [ ] Semua query menggunakan prepared statements.
- [ ] Semua form mutasi menggunakan CSRF token.
- [ ] Input dan output dilindungi dari XSS.
- [ ] Upload file tervalidasi.
- [ ] Halaman admin terlindungi.
- [ ] Percobaan login dibatasi.
- [ ] Backup tidak dapat diakses publik.

---

# 15. Release Plan

## Phase 1 — Foundation

Fokus:
- Setup project.
- Database.
- Koneksi PDO.
- Struktur MVC sederhana.
- Login admin.
- Desain dasar.
- Homepage.

## Phase 2 — Menu dan Ordering

Fokus:
- Kategori.
- Produk.
- Stok.
- Menu pelanggan.
- Pencarian.
- Keranjang.
- Checkout.
- Dine-in dan Takeaway.

## Phase 3 — Payment dan Order Management

Fokus:
- Pembayaran tunai.
- Transfer bank.
- QRIS statis.
- Upload bukti.
- Verifikasi admin.
- Countdown pembayaran.
- Order tracking.
- Cancellation.
- Refund recording.
- KOT.

## Phase 4 — Booking System

Fokus:
- Denah meja.
- Area.
- Slot reservasi.
- Booking.
- QR Code.
- Check-in.
- No-Show.
- Dine-in tanpa reservasi.
- Table session.

## Phase 5 — Promotion dan Customer Engagement

Fokus:
- Voucher.
- Diskon otomatis.
- Review.
- Rating.
- Testimonial.
- Riwayat pelanggan.

## Phase 6 — Dashboard dan Reporting

Fokus:
- Dashboard statistik.
- Laporan.
- Ekspor Excel/PDF.
- Activity Log.
- Customer management.

## Phase 7 — Security dan Deployment

Fokus:
- Security testing.
- Backup otomatis.
- Restore.
- Validasi transaksi.
- Optimasi.
- Dokumentasi instalasi.
- UAT.

---

# 16. Risks dan Mitigations

| Risiko | Mitigasi |
|---|---|
| Stok berubah ketika pelanggan checkout | Validasi stok ulang saat konfirmasi admin dan gunakan transaksi database |
| Double booking | Validasi konflik waktu dan locking transaksi database |
| Pembayaran palsu | Verifikasi manual oleh admin |
| Bukti pembayaran berbahaya | Validasi MIME, ukuran, ekstensi, dan penyimpanan privat |
| QR Code disalahgunakan | Token acak dan validasi status serta waktu |
| Admin lupa mengubah status | Dashboard dengan status yang jelas |
| Data hilang | Backup harian dan backup manual |
| Akses riwayat pelanggan oleh pihak lain | Verifikasi nomor telepon dan kode transaksi, rate limiting |
| Kesalahan pencetakan KOT | Fitur reprint dengan alasan |
| Ketidaksesuaian harga historis | Snapshot harga pada order item |
| Restore backup gagal | Validasi file dan backup pengaman sebelum restore |

---

# 17. Dependencies

Implementasi membutuhkan:

- PHP yang mendukung fitur bahasa yang digunakan.
- MySQL.
- Apache.
- XAMPP atau Laragon.
- Browser modern.
- Printer kasir/dapur jika menggunakan KOT otomatis.
- Library QR Code.
- Library Excel.
- Library PDF.
- Ruang penyimpanan untuk backup dan upload.

Pencetakan tanpa dialog browser memerlukan konfigurasi tambahan pada komputer kasir.

---

# 18. Final Product Decisions dan Catatan Implementasi

Beberapa keputusan perlu diterjemahkan secara konsisten saat implementasi:

1. **Konfirmasi stok:** stok dikurangi setelah admin mengonfirmasi pesanan. Jika stok tidak mencukupi ketika konfirmasi, sistem harus menahan konfirmasi item terkait dan menjalankan proses penawaran substitusi jika memungkinkan.
2. **Batas stok:** jumlah yang dipilih pelanggan tidak boleh melebihi stok yang tersedia saat validasi checkout. Sistem tetap melakukan validasi ulang sebelum konfirmasi admin.
3. **Pembayaran Cash at Counter:** tetap memiliki deadline tersendiri sesuai konfigurasi admin. Jika melewati deadline, pesanan dapat menjadi Expired.
4. **Dine-in dengan reservasi:** order harus terhubung dengan reservasi dan meja yang sesuai.
5. **Dine-in tanpa reservasi:** sesi meja memiliki durasi yang dapat dikonfigurasi admin dan berakhir otomatis atau lebih awal melalui dashboard.
6. **Data pelanggan:** riwayat transaksi tetap dipertahankan meskipun informasi identitas dianonimkan.
7. **Produk historis:** produk yang pernah dipesan tidak dapat dihapus dan harganya dikunci. Produk baru dapat dibuat untuk harga yang berbeda.
8. **Status pesanan dan pembayaran:** keduanya harus dipisahkan agar status pembayaran tidak tercampur dengan status pemrosesan makanan.
9. **Pencetakan KOT:** implementasi pencetakan otomatis perlu diuji pada perangkat kasir karena browser biasa memiliki pembatasan pencetakan tanpa dialog.
10. **Backup harian:** penjadwalan otomatis pada localhost memerlukan konfigurasi Cron atau Windows Task Scheduler.

---

# 19. Definition of Done

CozyCafe dinyatakan siap digunakan ketika:

1. Seluruh modul utama selesai.
2. Semua proses pelanggan berjalan dari awal hingga akhir.
3. Admin dapat mengelola pesanan, pembayaran, dan reservasi.
4. Perhitungan transaksi benar.
5. Stok dan ketersediaan meja konsisten.
6. QR Code reservasi dapat digunakan untuk check-in.
7. Pelacakan pesanan dan reservasi berfungsi.
8. Laporan dapat diekspor.
9. Backup dan restore telah diuji.
10. Keamanan dasar telah diuji.
11. Tidak ada bug kritis pada proses transaksi.
12. Dokumentasi instalasi dan penggunaan tersedia.

---

# 20. Product Conclusion

CozyCafe dirancang sebagai aplikasi web pemesanan dan reservasi kafe yang terintegrasi dengan pengelolaan operasional melalui satu dashboard admin.

Prioritas pengembangan adalah memastikan alur utama berikut berjalan dengan benar:

- Pemesanan makanan dan minuman.
- Pembayaran manual dan verifikasi.
- Pengelolaan stok.
- Reservasi dan pemilihan meja.
- Check-in QR Code.
- Pelacakan pesanan.
- Pelacakan reservasi.
- Pengelolaan pelanggan.
- Laporan transaksi.
- Keamanan dan backup database.

Teknologi Native PHP, MySQL, JavaScript, dan Bootstrap 5 dipilih agar aplikasi mudah dijalankan secara lokal melalui XAMPP atau Laragon serta mudah dikembangkan secara modular.

**Hasil akhir yang diharapkan:** CozyCafe menjadi aplikasi web yang siap digunakan untuk mendukung operasional pemesanan makanan/minuman dan reservasi meja sebuah kafe, dengan pengalaman pelanggan yang sederhana dan pengelolaan admin yang terstruktur.