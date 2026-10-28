# Tahap 7B — Layout Tutor Terpadu

## Yang berubah

- `tutor/_layout.php`: satu sumber navigasi sidebar, header, nama tutor, role dan tombol keluar.
- `tutor/assets/tutor-layout.css` dan `tutor/assets/tutor-layout.js`: gaya responsif dan menu mobile.
- `tutor/index.php`, `tutor/planet-form.php`, `tutor/mission-list.php`, `tutor/questions.php`: memakai komponen bersama tanpa mengubah form dan logika data.

Semua logout tutor tetap menggunakan **POST + CSRF** ke `tutor/logout.php`.
Tidak ada migrasi SQL atau perubahan login siswa.

## Cara tes di Laragon

1. Backup aplikasi/database dan uji branch `feature/stage-7b-tutor-layout` dalam salinan proyek.
2. Pastikan keempat halaman memiliki sidebar dan header yang sama.
3. Di desktop, sidebar terlihat; di mobile, tombol ☰ membuka sidebar dan backdrop dapat menutupnya.
4. Periksa navigasi kembali ke Dashboard, Tambah Planet, dan Kelola Soal Misi.
5. Tombol Keluar di header dan sidebar mengakhiri sesi tutor.
6. Form planet, tambah/edit/hapus soal, dan Update Publikasi tetap berfungsi.
7. Pastikan login siswa, kuis, toko, dan progres tidak berubah.

## Catatan

Menu Kelola Soal Misi menuju daftar planet di Dashboard; pilih planet yang ingin diedit. Halaman indeks semua bank soal tutor belum dibuat. Tahap 7C akan membahas registrasi/login terpadu.
