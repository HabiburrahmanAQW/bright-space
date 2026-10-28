# 📜 JURNAL PROMPT & LOG AGENT AI - BRIGHT SPACE
**Proyek**: BRIGHT SPACE - Interactive EdTech Platform
**Pengembang**: Alfath / Team
**Kompetisi**: M-ONE Coding Competition

---

## 1. DOKUMEN ACUAN AI AGENT (PRD & System Prompt)
- **Role Agent**: Senior Full-Stack Web Developer & UI/UX Specialist.
- **Tech Stack Acuan**: PHP Native, Tailwind CSS (via CDN), Vanilla JavaScript, LocalStorage, MySQL/SQLite.
- **Tujuan Utama**: Membangun platform pembelajaran interaktif bertema luar angkasa ramah anak SD dengan fitur Peta Planet Misi, Toko Bintang, Arazka AI Mentor, Leaderboard Sekolah, dan Profil Kapten.

---

## 2. JURNAL PROMPT TERKURASI (TAHAP PENGEMBANGAN)

### Phase 1: Arsitektur & Core Layout
- **Prompt**: "Buat struktur layout dashboard edtech 'BRIGHT SPACE' dengan sidebar navigasi di sebelah kiri, topbar koin/profile, dan container konten utama menggunakan PHP dan Tailwind CSS."
- **Hasil**: Implementasi modular `components/header.php`, `components/sidebar.php`, dan refactoring `dashboard.php`.

### Phase 2: Integrasi Fitur Utama & Logic Session
- **Prompt**: "Perbaiki warning session PHP pada toko bintang dan buat logika kalkulasi Star Coins menggunakan Null Coalescing Operator."
- **Hasil**: Penanganan session aman tanpa PHP error/warning.

### Phase 3: Peta Misi & Modul Kuis Interaktif
- **Prompt**: "Buat halaman kuis.php interaktif yang menangkap parameter planet dari URL (?planet=...) dengan mempertahankan Misi Wajib dan Side Quest, serta popup koin real-time saat jawaban benar."
- **Hasil**: Pengisian data dinamis pada `misi.php` dan `kuis.php` beserta reward koin/bintang.

### Phase 4: Leaderboard & Inventory Profil Kapten
- **Prompt**: "Ubah cakupan data leaderboard menjadi level satu sekolah dengan variasi kelas SMK (X/XI/XII RPL), serta hubungkan item yang dibeli di shop.php agar ter-equip di profile.php."
- **Hasil**: `leaderboard.php` ter-update dengan cakupan sekolah, dan `shop.php` mengunci item ter-beli (`disabled`).

---

## 3. LOG CHAT PROMPT MENTAH (RAW LOG)
[Log percakapan pengoperasian prompt penuh dilakukan secara bertahap dalam sesi pengembangan proyek BRIGHT SPACE menggunakan bantuan AI Assistant sebagai personal collaborator.]