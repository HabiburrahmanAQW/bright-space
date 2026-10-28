# Audit Tahap 7A — Cleanup aman

## Dihapus pada branch ini

**Portal guru prototipe (tidak digunakan oleh portal tutor aktif):**
- `dashboard-guru.php`, `tambah-misi.php`, `tambah-planet.php`, `simpan-planet.php`
- `components/header-guru.php`, `components/sidebar-guru.php`

Alasan: portal lama memakai `require_login()` siswa; `tambah-planet.php` dan `simpan-planet.php` juga menunjuk ke `includes/bootstrap.php` yang tidak ada pada repositori.

**Sisa pemasang paket tahap terdahulu:** seluruh `tools/apply_stage*.php`, `tools/apply_mission_close_fix.php`, dan folder `tools/stage*_assets/`. File-file `.txt` pada folder tersebut adalah template milik pemasang, bukan konten runtime. Installer dan template dihapus bersama-sama agar tidak meninggalkan ketergantungan rusak.

## Tetap dipertahankan

- Tabel dan migrasi `sql/01_...` sampai `sql/04_...`
- `tools/create_student.php`, `tools/create_tutor.php`, `tools/init_stage6b.php` dan `tools/test_stage*.php`
- `config/planets.php`, `kuis.php`, `components/modal-quiz.php` karena kuis lama masih hidup
- `api/` dan `assets/js/app.js` untuk audit selanjutnya; jangan mengasumsikan endpoint ini aman tanpa migrasi

## Perbaikan status publikasi

1. Simpan edit pada planet `published`: status planet dan misi yang sudah published tetap dipertahankan.
2. Misi baru yang ditambahkan ke planet published tetap `draft` sampai tutor memilih **Update Publikasi**.
3. Mengubah gamemode atau menghapus misi yang sudah published diblokir. Untuk mengubahnya, kembalikan planet ke Draft terlebih dahulu.
4. Soal baru/edit soal tetap memerlukan **Update Publikasi** (snapshot siswa).
5. Perubahan metadata planet (nama/deskripsi/level) masih langsung tampak ke siswa saat disimpan. Snapshot metadata terpisah merupakan pekerjaan lanjutan.

## Checklist Laragon sebelum merge

- `php -l tutor/planet-save.php`
- `php -l tutor/planet-form.php`
- `php -l tutor/index.php`
- Login tutor → edit planet Published → simpan → masih Published.
- Tambah misi baru → simpan → misi baru tidak tampil bagi siswa sebelum Update Publikasi.
- Tambah soal dan Update Publikasi → misi siap tampil sesuai target soal.
- Periksa kuis lama, modal Misi Selesai, login/logout siswa/tutor, toko, dan progres MySQL.
- Pastikan tidak ada link ke halaman guru prototipe yang dihapus.

## Rollback

Perubahan disiapkan sebagai Pull Request, sehingga `main` tidak berubah sampai di-merge.
Jika terdapat kegagalan, batalkan merge / tutup PR. Jika sudah di-merge, lakukan revert commit PR dari GitHub dan pastikan backup database/berkas lokal tetap tersedia.
