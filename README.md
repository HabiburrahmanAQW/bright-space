# BRIGHT SPACE

Aplikasi pembelajaran berbasis Native PHP, MySQL, Tailwind CDN, dan Vanilla JS.

## Akses
- Login bersama: login.php (tanpa memilih role).
- Administrator: admin/index.php, admin/tutors.php, admin/students.php — CRUD akun tutor dan siswa saja.
- Tutor: tutor/index.php — planet, misi, dan soal saja.
- Siswa: dashboard.php — petualangan, toko, progres, XP.

Admin membuat tutor dan siswa melalui web. Password awal dibangkitkan secara acak dan hanya terlihat sekali. Tidak ada formulir registrasi publik.

## Laragon
1. Gunakan database bright_space yang sudah ada; backup sebelum mengganti kode.
2. Migrasi awal tersimpan di sql/01 sampai sql/04. Jangan impor ulang/reset database aktif.
3. Migrasi sql/05_tutor_students.sql adalah sisa relasi tutor-siswa dari versi lama. Bila tabel bs_tutor_students sudah ada, jangan hapus; aplikasi revisi ini tidak lagi bergantung padanya.
4. Buat admin pertama dengan menetapkan satu akun bs_tutors yang sudah ada, aktif dan tepercaya menjadi role admin via phpMyAdmin. Akun admin berikutnya dikelola menurut kebijakan sekolah, bukan melalui form umum.
5. Masuk melalui login.php; server mengarahkan akun berdasarkan role yang diverifikasi.
6. Jalankan php tools/test_stage7c.php serta PHP lint sebelum merge.

## Struktur
- admin/: dashboard dan CRUD akun siswa/tutor, reset password, aktif/nonaktifkan (bukan hard delete).
- tutor/: editor planet, bank soal, publikasi. Semua endpoint editor hanya untuk tutor.
- config/: helper database, progres, toko dan autentikasi.
- sql/: migrasi database lama dan terkini.
- docs/TAHAP7C-AUTH.md: panduan pengujian.

## Batasan
Belum siap untuk pengguna anak secara publik. Perlu privasi/retensi data, perubahan password awal, pemulihan password, audit log, proteksi brute-force per IP/akun, serta HTTPS.
