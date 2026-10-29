# BRIGHT SPACE

Platform pembelajaran bergamifikasi berbasis **Native PHP, MySQL, Tailwind CSS (CDN), Vanilla JavaScript, dan inline SVG**.

## Menjalankan proyek (Laragon)

1. Salin repositori ke folder web Laragon.
2. Buat database MySQL bernama `bright_space` dan sesuaikan `config/database.php` atau variabel lingkungan `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
3. Jalankan migrasi berurutan dari `sql/01_fondasi_tutor.sql` sampai `sql/04_toko_dan_snapshot.sql`. Untuk database yang sudah dipakai Tahap 6B, **jangan hapus atau reset tabel**.
4. Untuk instalasi baru setelah migrasi, jalankan `php tools/init_stage6b.php` agar snapshot awal publikasi terisi bila diperlukan. Jalankan hanya pada database yang sudah memiliki keempat migrasi.
5. Pastikan satu akun tutor pengelola yang tepercaya memiliki role admin untuk menyetujui pendaftaran tutor. Setelah itu, akun tutor dan siswa baru dibuat lewat web, bukan terminal.
6. Impor juga sql/05_tutor_students.sql. Buka login.php tanpa pilihan role. Tutor mendaftar di register-tutor.php dan menunggu persetujuan admin; siswa dibuat oleh tutor di tutor/students.php.

`sql/` berisi skema/migrasi, **bukan data pribadi siswa**. Jangan commit dump data nyata atau file kredensial.

## Struktur aktif

- `dashboard.php`, `misi.php`, `planet-detail.php`, `mainkan-misi.php`: portal siswa dan permainan
- `tutor/`: portal tutor, editor planet, bank soal, dan publikasi
- `config/`: akses data, ekonomi, progres, dan snapshot publikasi
- `components/`: komponen UI portal siswa
- `assets/js/`: interaksi browser
- `sql/`: skema MySQL
- `tools/create_*.php`, `tools/init_stage6b.php`, `tools/test_stage*.php`: utilitas setup/pengujian

## Catatan Tahap 7A

Kode halaman guru prototipe dan installer patch Tahap 1–6B sudah dikeluarkan dari **source aktif**. Versi sebelumnya tetap ada dalam riwayat Git; installer tidak diperlukan lagi untuk menjalankan proyek yang sudah dimigrasikan.

Mengedit planet yang sudah Published **tidak otomatis mengubahnya menjadi Draft**. Perubahan pertanyaan tetap perlu `Update Publikasi` pada halaman `tutor/mission-list.php`. Menghapus misi atau mengganti gamemode yang sudah tayang perlu mengembalikan planet ke Draft dahulu untuk mencegah hilangnya snapshot aktif.

**Belum selesai:** API legacy di api/, logika toko lama dalam assets/js/app.js, serta keamanan login untuk produksi (reset password, audit log, pembatasan per IP). Hindari menggunakan endpoint API lama sampai migrasi autentikasi dan toko selesai.

Lihat [audit dan checklist Tahap 7A](docs/AUDIT-TAHAP7A.md).

## Tahap 7C — Registrasi dan Login (Revisi)

- Login: login.php — satu form, server menentukan role dan dashboard dari kredensial sah.
- Tutor: register-tutor.php — mandiri, akun Pending sampai disetujui admin di tutor/accounts.php.
- Siswa: tutor/students.php — dibuat tutor, password awal acak dan hanya diperlihatkan sekali.
- Tidak ada register siswa publik; migrasi sql/05_tutor_students.sql wajib diimpor.
- URL tutor/login.php lama tetap mengarah ke login utama. Session siswa dan tutor terpisah.

Panduan dan checklist: docs/TAHAP7C-AUTH.md.
