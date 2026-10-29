# Tahap 7C (Revisi) — Portal Admin Terpisah

Admin mengakses admin/index.php (dashboard akun), admin/tutors.php (buat, lihat, edit, nonaktif/aktif, reset password tutor) dan admin/students.php (fitur yang sama bagi siswa). Form tidak mengubah role admin yang ada. Hapus permanen diganti penonaktifan untuk menjaga integritas XP, Star Coins, progres dan foreign key.

Tutor mengakses tutor/index.php, planet-form.php, mission-list.php, questions.php dan endpoint pengelola terkait. Tutor tidak dapat mengakses CRUD akun; admin tidak dapat mengakses editor tutor meski mengetahui URL.

Akun siswa, tutor, dan admin masuk melalui login.php yang sama tanpa pemilihan role. Validasi backend mengarahkan ke dashboard yang tepat, dengan session siswa terpisah dari session tutor/admin.

Tidak ada registrasi tutor atau siswa publik. Semua akun baru dibuat melalui dashboard admin. Password acak hanya ditampilkan sekali dan disimpan sebagai password_hash di database.

## Database
Tabel bs_tutors dan bs_students tetap digunakan. Tidak ada migrasi wajib yang baru. Jika migrasi sebelumnya membuat bs_tutor_students, biarkan ada sebagai data historis dan jangan drop.

## Checklist Laragon
1. Backup proyek dan database; checkout feature/stage-7c-auth-register.
2. Jalankan php tools/test_stage7c.php. Lint seluruh admin/*.php, login.php, tutor/index.php.
3. Login admin -> admin/index.php, sidebar hanya Dashboard Admin, Akun Tutor, Akun Siswa.
4. Admin membuat tutor dan siswa; password muncul sekali; login akun tersebut diarahkan ke portal masing-masing.
5. Admin dapat mengedit nama/username, mencari, nonaktifkan/aktifkan, dan reset password; XP/koin siswa tetap utuh.
6. Tutor tidak melihat menu akun dan tidak bisa mengakses admin/tutors.php ataupun admin/students.php (403).
7. Admin tidak bisa mengakses tutor/planet-form.php, planet-save.php, mission-list.php, questions.php (403).
8. Logout kedua portal, planet dan publikasi, misi, XP serta toko tetap normal.
9. URL lama tutor/index.php bagi admin seharusnya diarahkan ke admin/index.php.
10. Pastikan register-tutor.php, tutor/accounts.php dan tutor/students.php tidak lagi tersedia.

## Keterbatasan
Manajemen pengguna ini cocok untuk pengujian satu sekolah, namun belum ada kelas/tahun ajaran, log admin, ganti password pertama kali, pemulihan akun, perizinan wali, atau rate-limit IP yang kuat. Simpan kredensial secara privat.
