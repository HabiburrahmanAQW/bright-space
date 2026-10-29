<div align="center">

# 🚀 BRIGHT SPACE
### Petualangan Belajar Interaktif Berbasis Planet

**Platform pembelajaran bergamifikasi untuk siswa, tutor, dan administrator sekolah.**

**Native PHP · MySQL · Tailwind CSS (CDN) · Vanilla JavaScript · Inline SVG**

[![PHP checks](https://github.com/HabiburrahmanAQW/bright-space/actions/workflows/php-checks.yml/badge.svg?branch=main)](https://github.com/HabiburrahmanAQW/bright-space/actions/workflows/php-checks.yml)
[![Status](https://img.shields.io/badge/status-local%20tested-2563eb)](#status-proyek)
[![Platform](https://img.shields.io/badge/platform-Laragon%20%2F%20PHP-7c3aed)](#instalasi-lokal-laragon)

</div>

---

## 📖 Tentang BRIGHT SPACE

**BRIGHT SPACE** merupakan aplikasi pembelajaran berbasis web dengan tema petualangan luar angkasa. Siswa menjelajahi **planet**, menyelesaikan **misi** dengan berbagai mode permainan, dan mendapatkan **XP** serta **Star Coins**. Tutor membuat konten pembelajaran, sedangkan administrator mengelola akun pengguna sekolah.

Proyek menggunakan **Native PHP tanpa framework backend** dengan desain responsif bernuansa *Pastel Cosmic*. Versi ini telah diuji pada lingkungan **Laragon lokal**, bukan dinyatakan siap untuk deployment publik.

## ✨ Fitur utama

| Pengguna | Fitur |
| --- | --- |
| 🛡️ **Administrator** | Dashboard statistik akun; buat, cari, lihat, ubah nama/username, nonaktifkan/aktifkan, dan reset password akun tutor maupun siswa |
| 📚 **Tutor** | Dashboard tutor; tambah/edit planet, misi wajib dan *side quest*; kelola bank soal; simpan Draft, Publish, dan **Update Publikasi** |
| 🚀 **Siswa** | Dashboard petualangan, akses planet sesuai level, permainan misi, progres belajar, XP/level, Star Coins, toko item, profil, dan leaderboard |

### Mode permainan

- ⚡ **Kuis** — pilihan ganda.
- 📖 **Story** — cerita dan pengambilan keputusan.
- 🔤 **Puzzle** — menyusun kata.
- 🧩 **Matching** — mencocokkan pasangan.
- 🎓 **Latihan** — latihan interaktif.

### Mekanisme progres dan publikasi

- Progres, perolehan XP dan Star Coins tersimpan di **MySQL**.
- Hadiah penyelesaian pertama suatu misi diberikan satu kali; memainkan ulang tidak menggandakan hadiah pertama.
- Soal tutor dikelola secara terpisah dari **snapshot soal yang dipublikasikan**. Perubahan bank soal tidak otomatis mengubah versi siswa sampai tutor melakukan **Update Publikasi**.
- Toko menyimpan pembelian dan status item yang digunakan siswa pada database.
- Akun siswa/tutor tidak dihapus permanen lewat dashboard admin; **penonaktifan** mempertahankan data dan riwayat belajar.

## 🔑 Alur akun dan hak akses

Satu halaman login di **`login.php`** menerima username dan password. Sistem memverifikasi akun di server lalu mengarahkan pengguna ke portal sesuai perannya, **tanpa tombol pemilihan role**.

| Role | Halaman setelah login | Akses |
| --- | --- | --- |
| Admin | `admin/index.php` | CRUD akun tutor dan siswa |
| Tutor | `tutor/index.php` | Planet, misi, bank soal, publikasi |
| Siswa | `dashboard.php` | Petualangan, progres, toko |

**Akun tutor dan siswa baru dibuat oleh administrator dari web**, melalui `admin/tutors.php` dan `admin/students.php`. Tidak tersedia *register* publik untuk siswa ataupun tutor.

> **Penting:** Menyembunyikan pilihan role bukan mekanisme keamanan. Pembatasan akses diterapkan melalui pemeriksaan session dan role pada endpoint PHP. Password disimpan sebagai hash; password awal yang dibuat admin ditampilkan sekali.

## 🧰 Teknologi dan persyaratan

- **PHP 8.2** disarankan, dengan ekstensi **PDO MySQL**, **mbstring** (opsional tetapi disarankan) dan **JSON**.
- **MySQL/MariaDB** dengan dukungan InnoDB, foreign key dan tipe JSON.
- **Laragon** untuk pengembangan lokal di Windows (Apache/Nginx + PHP + MySQL).
- **Tailwind CSS via CDN**, **Google Fonts (Nunito)**, **Vanilla JavaScript**, dan **Inline SVG**.
- Browser modern dan koneksi internet saat pertama memuat aset CSS/font dari CDN.

Tidak diperlukan Composer atau Node.js untuk menjalankan aplikasi yang ada.

## ⚙️ Instalasi lokal (Laragon)

### A. Clone dan buka aplikasi

```powershell
cd C:\laragon\www
git clone https://github.com/HabiburrahmanAQW/bright-space.git BRIGHT_SPACE
```

Aktifkan **Web Server** dan **MySQL** di Laragon. Folder `C:\laragon\www\BRIGHT_SPACE` biasanya dapat diakses melalui:

```text
http://localhost/BRIGHT_SPACE/login.php
```

Jika nama folder atau virtual host berbeda, sesuaikan URL-nya.

### B. Konfigurasi MySQL

Buat database `bright_space` (UTF-8/utf8mb4). Konfigurasi koneksi dibaca oleh `config/database.php` dari environment `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS`, dengan fallback lokal yang didefinisikan di file tersebut.

> File `.env.example` **hanya contoh**: proyek ini belum memuat `.env` secara otomatis. Atur environment pada konfigurasi PHP/web server atau sesuaikan konfigurasi lokal secara aman; **jangan commit password/database asli** ke GitHub.

### C. Siapkan tabel (instalasi baru saja)

Untuk database **kosong**, impor file berikut melalui **phpMyAdmin**, secara berurutan:

1. `sql/01_fondasi_tutor.sql`
2. `sql/02_bank_soal_misi.sql`
3. `sql/03_progress_misi.sql`
4. `sql/04_toko_dan_snapshot.sql`

Lalu, bila perlu untuk konten Published lama yang belum memiliki snapshot, jalankan:

```powershell
php tools\init_stage6b.php
```

File `sql/05_tutor_students.sql` berasal dari rancangan relasi tutor–siswa tahap sebelumnya. **Portal Admin/Tutor terbaru tidak memerlukannya**. Jika tabel `bs_tutor_students` sudah ada pada database lama, **jangan dihapus**.

**Jika kamu sudah menjalankan BRIGHT SPACE Tahap 6B/7C dan punya akun, koin, XP, soal, atau progres: backup database dan jangan impor ulang/reset tabel.** Cukup sinkronkan kode sesuai alur Git, lalu uji aplikasi.

### D. Admin sekolah pertama

Sistem memerlukan satu administrator aktif. Untuk instalasi awal yang belum punya admin, buat atau pilih **akun pengelola tepercaya** dalam `bs_tutors`. Setelah memastikan username pada phpMyAdmin, promosikan akun yang dimaksud **sekali**:

```sql
UPDATE bs_tutors
SET role = 'admin', is_active = 1
WHERE username = 'GANTI_DENGAN_USERNAME_ASLI';
```

Perintah `UPDATE` **tidak membuat akun**. Pastikan akun dengan username tersebut sudah ada sebelum menjalankannya. Untuk bootstrap instalasi kosong, utilitas CLI `tools/create_tutor.php` masih tersedia bagi pengelola, kemudian akun dapat dipromosikan. Setelah itu, semua akun siswa dan tutor baru dibuat melalui dashboard admin tanpa terminal.

### E. Masuk sesuai peran

Buka `login.php` dan masukkan kredensial yang valid. Selanjutnya:

- **Admin:** kelola akun di `admin/index.php`.
- **Tutor:** kelola planet, misi, dan soal di `tutor/index.php`.
- **Siswa:** mainkan misi di `dashboard.php`.

## 🗂️ Struktur proyek

```text
bright-space/
├── admin/                  # Dashboard dan pengelolaan akun tutor/siswa
├── tutor/                  # Editor planet, misi, soal, dan publikasi
│   └── assets/             # Gaya dan interaksi untuk portal tutor/admin
├── assets/                 # JavaScript/CSS portal siswa
├── components/             # Komponen UI siswa
├── config/                 # Database, repositori misi, progres, toko
├── sql/                    # Skema dan riwayat migrasi
├── tools/                  # Utilitas bootstrap dan tes CLI
├── docs/                   # Audit, catatan perubahan, dan checklist
├── login.php               # Autentikasi terpadu
├── dashboard.php           # Dashboard siswa
├── planet-detail.php       # Detail planet dan akses misi
├── mainkan-misi.php        # Mesin permainan
├── shop.php                # Toko Bintang
├── profile.php             # Profil siswa
└── leaderboard.php         # Papan peringkat siswa
```

Sebagian endpoint `api/` dan aset pembelajaran lama dipertahankan untuk kompatibilitas. Sebelum mengaktifkannya dalam lingkungan publik, lakukan audit keamanan dan dependensi lebih lanjut.

## 🧪 Pengujian

Pemeriksaan cepat pada terminal Laragon:

```powershell
php tools\test_stage7c.php
php -l login.php
php -l admin\index.php
php -l admin\tutors.php
php -l admin\students.php
php -l tutor\index.php
```

GitHub Actions menjalankan **PHP lint seluruh file** dan tes validasi Tahap 7C pada Pull Request serta perubahan branch utama. Untuk pengujian manual, lihat [Checklist Tahap 7C](docs/TAHAP7C-AUTH.md).

Tes otomatis **belum** menggantikan pengujian browser, MySQL, hak akses antarroll, transaksi toko, dan progres misi.

## 🔐 Keamanan dan privasi

**Repositori ini berisi kode aplikasi, bukan data pengguna.** Jangan unggah:
- File `.env`, password, token, API key, atau dump database aktif.
- Nama, data pribadi, maupun kredensial siswa asli.
- File backup/hasil ekspor yang berisi informasi sensitif.

Sebelum deployment publik (terutama karena ditujukan bagi anak-anak), lakukan peninjauan tambahan untuk **HTTPS, kebijakan privasi/persetujuan wali atau sekolah, perlindungan data anak, pergantian password awal, pemulihan akun, audit log, serta pembatasan login per IP/akun**.

## 📌 Status proyek

- **Tahap 6B:** misi MySQL, snapshot publikasi, XP, toko dan inventaris.
- **Tahap 7A:** perapihan file legacy dan stabilitas status publikasi.
- **Tahap 7B:** sidebar/header tutor yang konsisten.
- **Tahap 7C:** portal admin tersendiri, CRUD akun, tutor khusus materi, login bersama.

**Status:** milestone selesai dan telah melalui checklist fungsional pada Laragon. Pengembangan lanjutan untuk penggunaan satu sekolah secara operasional dapat mencakup **manajemen kelas/tahun ajaran, penugasan misi per kelas, laporan progres guru, serta penguatan keamanan**.

## 📚 Dokumentasi tambahan

- [Audit dan Cleanup Tahap 7A](docs/AUDIT-TAHAP7A.md)
- [Layout Tutor Tahap 7B](docs/TAHAP7B-TUTOR-LAYOUT.md)
- [Hak Akses dan Pengujian Tahap 7C](docs/TAHAP7C-AUTH.md)

---

<div align="center">

Dibangun untuk menjadikan belajar sebuah petualangan. 🌟

</div>
