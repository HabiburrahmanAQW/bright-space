# Tahap 7C — Registrasi dan login terpadu

- Login siswa: login.php?role=siswa
- Login tutor: login.php?role=tutor
- URL lama tutor/login.php tetap berlaku sebagai pengalihan.
- Register siswa: register.php (menulis ke tabel bs_students).
- Registrasi tutor: tutor/accounts.php (khusus role admin, membuat role tutor).
- Tidak ada SQL baru; tidak memigrasikan atau menghapus akun yang sudah ada.

## Registrasi publik dan keamanan

Default registrasi terbuka hanya dari loopback (localhost, 127.0.0.1, atau host *.test). BS_PUBLIC_REGISTRATION=0 menutup registrasi. BS_PUBLIC_REGISTRATION=1 secara eksplisit membuka registrasi untuk akses publik; jangan lakukan ini sebelum kebijakan perlindungan anak, verifikasi orang tua/wali atau sekolah, pembatasan IP, penanganan abuse, dan HTTPS tersedia.

Username baru 3–30 karakter, diawali huruf; password 12–72 byte. Password disimpan dengan password_hash dan diperiksa password_verify. CSRF digunakan untuk login, registrasi, dan penambahan tutor. Login siswa dan tutor menggunakan dua cookie session berbeda. Rate limiting awal masih per session dan belum cukup untuk produksi.

## Menetapkan administrator pertama

Jika semua akun tutor yang ada masih ber-role tutor, pengelola tepercaya dapat menetapkan satu akun sebagai admin lewat phpMyAdmin dengan query berikut (ganti username):
    UPDATE bs_tutors SET role='admin' WHERE username='tutor_pengelola';

Sesudah itu, login dengan akun tersebut dan buka tutor/accounts.php. Tutor lain tidak dapat membuka endpoint ini; form publik tidak punya pilihan role tutor/admin.

## Checklist Laragon

1. Jalankan php tools/test_stage7c.php dan php -l pada file PHP baru/berubah.
2. Login tab Siswa dan Tutor berfungsi dari login.php, cookie sesi tidak saling tertukar.
3. Registrasi siswa berhasil, lalu login ke dashboard. Akun terbuat di bs_students.
4. Username ganda, password pendek, atau konfirmasi salah ditolak.
5. Akun siswa tidak bisa login sebagai tutor, dan sebaliknya.
6. URL lama tutor/login.php dialihkan ke login.php?role=tutor.
7. Logout tutor tidak menghapus sesi siswa, dan logout siswa tidak menghapus sesi tutor.
8. Tutor biasa tidak dapat mengakses tutor/accounts.php (403).
9. Admin dapat membuat tutor baru lewat tutor/accounts.php; tutor baru bisa login.
10. Misi, publikasi soal, progres XP, dan toko tidak berubah.

## Keterbatasan

Belum ada reset password, email/undangan, persetujuan wali, audit log admin, atau rate limiting tingkat IP. Fitur ini untuk pengujian lokal, belum siap dibuka untuk anak-anak di internet.
