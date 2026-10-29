# Tahap 7C (Revisi) — Akun Tutor, Siswa, dan Login

## Alur
1. Tutor mendaftar sendiri melalui register-tutor.php. Akun dibuat pada bs_tutors dengan role tutor dan is_active=0 (Pending).
2. Admin yang sudah aktif membuka tutor/accounts.php untuk menyetujui tutor. Sebelum disetujui, tutor tidak bisa masuk atau membuat akun siswa.
3. Login pada login.php tanpa opsi role. Server memeriksa username+password yang valid pada tabel bs_students/bs_tutors, kemudian mengarahkan ke dashboard yang sesuai.
4. Tutor terautentikasi membuka tutor/students.php, membuat siswa dan memperoleh password awal acak (ditampilkan satu kali).
5. Data siswa di bs_students, kepemilikan siswa pada bs_tutor_students; hanya tutor pembuat yang dapat melihat daftar siswanya.
6. Siswa masuk lewat login.php. Tidak ada register siswa publik.

## Migrasi wajib
Backup database bright_space, lalu impor sql/05_tutor_students.sql setelah migrasi sebelumnya (01–04). Migrasi ini hanya menambah tabel relasi; tidak menghapus data siswa, koin, misi, atau tutor.

Akun siswa Tahap 5/6B yang dibuat lewat CLI tetap dapat login, tetapi belum otomatis menjadi siswa dari tutor tertentu di halaman Kelola Siswa.

## Admin pertama
Jika belum ada admin, gunakan akun tutor pengelola yang sudah aktif dan tepercaya. Jalankan sekali pada phpMyAdmin (sesuaikan username):

    UPDATE bs_tutors SET role='admin' WHERE username='tutor_pengelola' AND is_active=1;

Admin dapat menyetujui tutor dari halaman akun. Pendaftar publik tidak dapat memilih role admin atau menyetujui dirinya sendiri.

## Kebijakan pendaftaran
Secara default register-tutor.php hanya terbuka bagi request loopback (localhost/127.0.0.1/host .test). BS_TUTOR_REGISTRATION=0 menutup register; BS_TUTOR_REGISTRATION=1 membuka secara eksplisit untuk akses dari jaringan lain. Jangan aktifkan di internet sebelum verifikasi pengajar, kebijakan privasi, perlindungan bot, dan rate limiting per IP siap.

## Checklist Laragon
1. php tools/test_stage7c.php dan php -l untuk file PHP baru/yang berubah.
2. Buka login.php: hanya ada username dan password; tidak ada tab login Siswa/Tutor.
3. Akun lama siswa/tutor dapat login dan diarahkan sesuai role masing-masing.
4. Daftar tutor via register-tutor.php; di database is_active=0; tidak dapat login sebelum admin setujui.
5. Admin menyetujui tutor via tutor/accounts.php; tutor tersebut sekarang bisa login.
6. Tutor biasa mendapatkan 403 saat mengakses tutor/accounts.php.
7. Tutor aktif membuka tutor/students.php; siswa baru memperoleh username dan password awal sekali.
8. Siswa baru dapat login, melihat dashboard, dan bermain misi. Tidak ada register siswa publik.
9. Tutor A tidak dapat melihat daftar siswa milik Tutor B.
10. Logout, XP/Star Coins, toko, dan Update Publikasi tetap berfungsi.
11. Periksa username ganda, password pendek, CSRF salah, dan status pending.

## Keterbatasan keamanan
Menghilangkan tab role mengurangi kebingungan, bukan menggantikan pemeriksaan akses server. Akun tutor selalu harus disetujui admin. Belum tersedia verifikasi resmi pengajar, reset password, wajib mengganti password awal siswa, perlindungan bot/rate-limit per IP, maupun audit log admin. Sebelum digunakan publik—terutama untuk anak-anak—fitur tersebut dan tata kelola data siswa wajib ditambahkan.
