# Skenario Demo Aplikasi Perpustakaan Digital

Dokumen ini berisi urutan langkah demonstrasi aplikasi untuk keperluan UAS.

## Persiapan

1. Pastikan dua server berjalan:
    - Server Utama: `php artisan serve --port=8000`
    - Server API Internal: `php artisan serve --port=8011`
2. Buka browser di `http://127.0.0.1:8000`.

## Langkah-langkah Demo

### 1. Login

- Masuk ke halaman login.
- Gunakan akun Admin: `admin@pens.ac.id` / `password`.
- **Hasil:** Redirect ke Dashboard dan nama "Bambang Sutrisno" muncul di navbar.

### 2. Tampilkan Dashboard

- Lihat tiga kartu statistik (Total Buku, Total Anggota, Peminjaman Aktif).
- Klik tombol "Lihat Laporan Peminjaman".

### 3. Manajemen Kategori (Admin Only)

- Masuk ke menu **Kategori**.
- Klik **Tambah Kategori**.
- Isi data (misal: "Filosofi") dan simpan.
- **Hasil:** Kategori baru muncul di tabel.

### 4. Manajemen Buku

- Masuk ke menu **Buku**.
- Klik **Tambah Buku**.
- Pilih kategori, isi judul (misal: "Filsafat Jawa"), penulis, dan stok.
- **Hasil:** Buku baru tersimpan dan tampil di tabel dengan pagination yang rapi.

### 5. Manajemen Anggota

- Masuk ke menu **Anggota**.
- Klik **Tambah Anggota**.
- Isi data mahasiswa (Nama, NIM, Email domain `@pens.ac.id`).
- **Hasil:** Data anggota tersimpan.

### 6. Proses Peminjaman

- Masuk ke menu **Peminjaman**.
- Klik **Pinjam Buku Baru**.
- Pilih Anggota dan pilih minimal 1 Buku.
- Klik Simpan.
- **Hasil:** Status peminjaman menjadi "dipinjam".

### 7. Daftar Peminjaman Aktif & Pengembalian

- Di halaman daftar Peminjaman, cari transaksi yang statusnya "dipinjam".
- Klik tombol **Kembalikan**.
- **Hasil:** Status berubah menjadi "dikembalikan" dan tanggal pengembalian terisi.

### 8. Tampilkan Laporan

- Kembali ke Dashboard atau klik menu **Laporan**.
- **Hasil:** Tabel menampilkan riwayat transaksi lengkap dengan nama anggota, petugas, dan daftar buku.

### 9. Demo API (Postman)

- Buka Postman.
- Lakukan request `GET http://127.0.0.1:8000/api/stats`.
- **Hasil:** Menerima respons JSON berisi total_buku, total_anggota, dll.
- Lakukan request `GET http://127.0.0.1:8000/api/loans`.
- **Hasil:** Menerima respons JSON berisi array data peminjaman.
