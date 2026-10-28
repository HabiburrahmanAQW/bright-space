<?php
/**
 * BRIGHT SPACE / Stage 3: patch minimal halaman siswa.
 * Jalankan via CLI: php tools/apply_stage3.php
 * Tidak memakai database atau mengubah isinya.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Jalankan hanya dari terminal.');
}
$root = dirname(__DIR__);
$dryRun = in_array('--check', $argv, true);

/** Satu patch harus hanya menemukan SATU string target. */
function stage3_replace(string $body, string $before, string $after, string $filename): string
{
    $count = substr_count($body, $before);
    if ($count !== 1) {
        throw new RuntimeException("{$filename}: penanda tidak cocok (ditemukan {$count}x): " . substr($before, 0, 70) . ". File mungkin telah dimodifikasi. Jangan dipaksakan; kirim error ini ke ChatGPT.");
    }
    return str_replace($before, $after, $body);
}

/**
 * Sesuaikan hanya tautan kartu planet di misi.php.
 * Repo dasar memakai kuis.php?planet=..., sementara sebagian salinan lokal
 * mungkin sudah memakai planet-detail.php?id=..., variasi kutip, atau spasi.
 * Jangan mengubah halaman lain bila format tidak diketahui.
 */
function stage3_update_planet_link(string $body): string
{
    $pattern = '~\bhref\s*=\s*(["\'])(?:\./)?(?:kuis\.php\?planet|planet-detail\.php\?id)\s*=\s*<\?=\s*e\(\s*\$p\s*\[\s*["\']id["\']\s*\]\s*\)\s*\?>\s*\1~';
    $count = preg_match_all($pattern, $body);
    if ($count === false) {
        throw new RuntimeException('misi.php: gagal membaca pola tautan kartu planet.');
    }
    if ($count !== 1) {
        // Tampilkan informasi aman untuk debugging; tidak pernah mengubah file.
        $candidates = [];
        foreach (preg_split('/\r?\n/', $body) as $lineNo => $line) {
            if (str_contains($line, '<a ') && (str_contains($line, 'kuis.php') || str_contains($line, 'planet-detail.php'))) {
                $candidates[] = 'baris ' . ($lineNo + 1) . ': ' . trim($line);
            }
        }
        $hint = $candidates ? implode(' | ', array_slice($candidates, 0, 3)) : 'tidak ada tautan kuis.php / planet-detail.php yang dapat dikenali';
        throw new RuntimeException("misi.php: pola href kartu planet ditemukan {$count}x, seharusnya 1x. {$hint}. Kirim baris href tersebut agar penyesuaian tidak merusak file.");
    }
    $updated = preg_replace($pattern, 'href="planet-detail.php?id=<?= e($p[\'id\']) ?>"', $body, 1);
    if ($updated === null) {
        throw new RuntimeException('misi.php: penggantian href gagal.');
    }
    return $updated;
}

function stage3_transform(string $filename, string $body): string
{
    if ($filename === 'misi.php') {
        if (str_contains($body, 'bs_student_planets(')) return $body;
        $body = stage3_replace($body,
            "require __DIR__ . '/config/planets.php';",
            "require __DIR__ . '/config/planets.php';\nrequire_once __DIR__ . '/config/student_mission_bridge.php';\n\$planets = bs_student_planets(\$planets, \$user);",
            $filename);
        $body = stage3_update_planet_link($body);
        // Badge progres 2/3 dalam halaman lama masih dummy; jangan tampilkan progres palsu.
        if (substr_count($body, '⭐ 2/3') !== 1 || substr_count($body, '⭐ 0/3') !== 1) {
            throw new RuntimeException('misi.php: badge progres dummy berubah. Perlu penyesuaian manual sebelum menerapkan Tahap 3.');
        }
        $body = str_replace(['⭐ 2/3', '⭐ 0/3'], '<?= ($p[\'source\'] ?? \'legacy\') === \'database\' ? \'⭐ Planet Baru\' : \'⭐ Planet\' ?>', $body);
        return $body;
    }
    if ($filename === 'planet-detail.php') {
        if (str_contains($body, 'bs_student_get_planet(')) return $body;
        $body = stage3_replace($body,
            "require __DIR__ . '/config/planets.php';",
            "require __DIR__ . '/config/planets.php';\nrequire_once __DIR__ . '/config/student_mission_bridge.php';",
            $filename);
        $body = stage3_replace($body,
            '$planet   = cariPlanet($planets, $planetId);',
            '$planet   = bs_student_get_planet($planetId, $planets, $user);',
            $filename);
        $body = stage3_replace($body,
            "if (!\$planet) { header('Location: index.php'); exit; }\nif (\$planet['status'] !== 'Terbuka') { header('Location: index.php'); exit; }",
            "if (!\$planet) { header('Location: misi.php'); exit; }\nif (\$planet['status'] !== 'Terbuka') { header('Location: misi.php'); exit; }",
            $filename);
        $body = stage3_replace($body,
            '$pageTitle = $planet[\'nama\'] . \' - \' . $appName;',
            "\$gameModes = bs_student_game_modes(\$planet, \$gameModes);\n\$pageTitle = \$planet['nama'] . ' - ' . \$appName;",
            $filename);
        $body = stage3_replace($body,
            '$soalPerMode[$mid] = get_soal($planet[\'id\'], $m[\'type\'], $m[\'jumlah_soal\']);',
            "// Misi MySQL belum memiliki bank soal per-misi (akan dibuat di Tahap 4).\n    \$soalPerMode[\$mid] = (\$planet['source'] ?? 'legacy') === 'database'\n        ? []\n        : get_soal(\$planet['id'], \$m['type'], \$m['jumlah_soal']);",
            $filename);
        $body = stage3_replace($body,
            "<button type=\"button\"\n                                class=\"btn-mode",
            "<?php \$canPlay = (\$planet['source'] ?? 'legacy') === 'legacy' && in_array(\$mode['type'], ['kuis', 'story', 'latihan'], true); ?>\n                        <button type=\"button\" <?= \$canPlay ? '' : 'disabled aria-disabled=\"true\"' ?>\n                                class=\"btn-mode",
            $filename);
        $body = stage3_replace($body,
            'hover:scale-105">' . "\n" . '                            Mulai Game →' . "\n" . '                        </button>',
            'hover:scale-105 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100">' . "\n" . '                            <?= $canPlay ? \'Mulai Game →\' : \'⏳ Soal belum tersedia\' ?>' . "\n" . '                        </button>',
            $filename);
        $body = stage3_replace($body,
            '<script src="assets/js/app.js" defer></script>',
            '<script src="assets/js/app.js" defer></script>' . "\n" . '<script src="assets/js/student-missions.js" defer></script>',
            $filename);
        return $body;
    }
    if ($filename === 'kuis.php') {
        if (str_contains($body, 'bs_student_get_planet(')) return $body;
        $body = stage3_replace($body,
            "require __DIR__ . '/config/planets.php';",
            "require __DIR__ . '/config/planets.php';\nrequire_once __DIR__ . '/config/student_mission_bridge.php';",
            $filename);
        $body = stage3_replace($body,
            '$planet = cariPlanet($planets, $planetId);',
            '$planet = bs_student_get_planet($planetId, $planets, $user);',
            $filename);
        $body = stage3_replace($body,
            "if (!\$planet || \$planet['status'] !== 'Terbuka') {",
            "// Soal untuk misi dari MySQL belum disiapkan; URL kuis lama tidak boleh melewati pembatas ini.\nif (!\$planet || \$planet['status'] !== 'Terbuka' || (\$planet['source'] ?? 'legacy') === 'database') {",
            $filename);
        $body = stage3_replace($body,
            '$soalList = get_soal($planet[\'id\'], $kategori, 5);',
            "\$kategori = in_array(\$kategori, ['wajib', 'side'], true) ? \$kategori : 'wajib';\n\$soalList = get_soal(\$planet['id'], 'kuis', 5);",
            $filename);
        return $body;
    }
    if ($filename === 'tutor/planet-save.php') {
        if (str_contains($body, 'Planet kini tampil di Peta Misi')) return $body;
        return stage3_replace($body,
            'Konfigurasi planet berhasil dipublikasikan. Belum tampil di halaman siswa sampai integrasi Tahap 3.',
            'Planet kini tampil di Peta Misi siswa sesuai level. Misi belum bisa dimainkan sampai soal tersedia.',
            $filename);
    }
    if ($filename === 'tutor/planet-form.php') {
        if (str_contains($body, 'Planet published akan tampil di Peta Misi')) return $body;
        return stage3_replace($body,
            'Publikasi saat ini hanya menyimpan status di MySQL. Menampilkan dan memainkan misi di halaman siswa dilakukan pada Tahap 3; editor soal juga belum tersedia.',
            'Planet published akan tampil di Peta Misi siswa sesuai level. Misi baru ditampilkan sebagai belum siap dimainkan sampai editor soal tersedia.',
            $filename);
    }
    throw new LogicException('File tidak dikenal: ' . $filename);
}

$files = ['misi.php', 'planet-detail.php', 'kuis.php', 'tutor/planet-save.php', 'tutor/planet-form.php'];
try {
    if (!is_file($root . '/config/student_mission_bridge.php') || !is_file($root . '/assets/js/student-missions.js')) {
        throw new RuntimeException('File adapter atau assets/js/student-missions.js belum tersalin. Ekstrak seluruh isi ZIP ke root proyek.');
    }
    $old = $new = [];
    foreach ($files as $file) {
        $path = $root . '/' . $file;
        if (!is_file($path)) throw new RuntimeException("File {$file} tidak ditemukan. Jalankan dari proyek yang benar.");
        $raw = file_get_contents($path);
        if ($raw === false) throw new RuntimeException("Tidak dapat membaca {$file}.");
        $isCrLf = str_contains($raw, "\r\n");
        $normalized = str_replace("\r\n", "\n", $raw);
        $patched = stage3_transform($file, $normalized);
        $old[$file] = $raw;
        $new[$file] = $isCrLf ? str_replace("\n", "\r\n", $patched) : $patched;
    }
    $changed = array_values(array_filter($files, fn($f) => $old[$f] !== $new[$f]));
    if (!$changed) {
        echo "Tahap 3 sudah terpasang, tidak ada perubahan.\n";
        exit(0);
    }
    echo "Siap mengubah: " . implode(', ', $changed) . "\n";
    if ($dryRun) {
        echo "Mode --check: belum ada file diubah.\n";
        exit(0);
    }
    $backupBase = getenv('USERPROFILE') ?: getenv('HOME') ?: sys_get_temp_dir();
    $backup = rtrim($backupBase, '/\\') . '/BRIGHT_SPACE_stage3_backups/' . basename($root) . '_' . date('Ymd_His');
    if (!mkdir($backup, 0700, true) && !is_dir($backup)) throw new RuntimeException('Tidak dapat membuat folder backup.');
    foreach ($changed as $file) {
        $target = $backup . '/' . $file;
        if (!is_dir(dirname($target))) mkdir(dirname($target), 0700, true);
        if (file_put_contents($target, $old[$file]) === false) throw new RuntimeException("Backup {$file} gagal.");
    }
    foreach ($changed as $file) {
        if (file_put_contents($root . '/' . $file, $new[$file], LOCK_EX) === false) {
            foreach ($changed as $restore) file_put_contents($root . '/' . $restore, $old[$restore]);
            throw new RuntimeException("Menulis {$file} gagal; file dikembalikan dari backup.");
        }
    }
    echo "Berhasil menerapkan Tahap 3.\nBackup otomatis: {$backup}\n";
    echo "Lanjutkan pengujian di browser; belum perlu impor SQL.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "GAGAL: " . $e->getMessage() . "\nTidak ada perubahan jika gagal pada pemeriksaan awal.\n");
    exit(1);
}
