<?php
/**
 * BRIGHT SPACE - Tahap 3 (adapter halaman siswa)
 * Menghubungkan array lama dan tabel bs_* tanpa mengganti desain.
 * Wajib di-require setelah config/planets.php dan config/database.php.
 */
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');
require_once __DIR__ . '/mission_repository.php';
require_once __DIR__ . '/mission_catalog.php';

/** Definisi kelas CSS tetap eksplisit untuk Tailwind CDN. */
function bs_student_theme_classes(string $key): array
{
    $themes = [
        'indigo'  => ['gradien' => 'from-blue-100 to-indigo-200',    'border' => 'border-indigo-300',  'tombol' => 'bg-indigo-500 hover:bg-indigo-600 focus-visible:ring-indigo-300'],
        'emerald' => ['gradien' => 'from-emerald-100 to-green-200',  'border' => 'border-emerald-300', 'tombol' => 'bg-emerald-500 hover:bg-emerald-600 focus-visible:ring-emerald-300'],
        'amber'   => ['gradien' => 'from-amber-100 to-yellow-200',  'border' => 'border-amber-300',   'tombol' => 'bg-amber-500 hover:bg-amber-600 focus-visible:ring-amber-300'],
        'violet'  => ['gradien' => 'from-purple-100 to-pink-200',   'border' => 'border-purple-300',  'tombol' => 'bg-purple-500 hover:bg-purple-600 focus-visible:ring-purple-300'],
        'rose'    => ['gradien' => 'from-rose-100 to-pink-200',     'border' => 'border-rose-300',    'tombol' => 'bg-rose-500 hover:bg-rose-600 focus-visible:ring-rose-300'],
        'sky'     => ['gradien' => 'from-sky-100 to-cyan-200',      'border' => 'border-sky-300',     'tombol' => 'bg-sky-500 hover:bg-sky-600 focus-visible:ring-sky-300'],
    ];
    return $themes[$key] ?? $themes['indigo'];
}

/** Memetakan baris bs_planets ke array lama yang dibutuhkan template. */
function bs_student_map_planet(array $row, int $studentLevel): array
{
    $requiredLevel = (int) ($row['required_level'] ?? 0);
    $icons = mission_editor_icons();
    $iconKey = (string) ($row['icon_key'] ?? 'umum');
    // Semua SVG dari katalog tepercaya; tambahkan kelas ukuran untuk wrapper kartu yang sudah ada.
    $iconSvg = preg_replace('/<svg\b/', '<svg class="w-full h-full"', $icons[$iconKey] ?? $icons['umum'], 1);
    return array_merge([
        'id' => (string) $row['id'],
        'nama' => (string) $row['name'],
        'deskripsi' => (string) $row['description'],
        'level' => $requiredLevel,
        'status' => mission_student_can_access($studentLevel, $requiredLevel) ? 'Terbuka' : 'Terkunci',
        'ikon' => $iconSvg,
        'poin' => 0, // placeholder kompatibilitas komponen lama, bukan reward misi
        'source' => 'database',
    ], bs_student_theme_classes((string) ($row['theme_key'] ?? 'indigo')));
}

/** Planet legacy tetap tersedia, tetapi level dan statusnya kini mengikuti siswa. */
function bs_student_map_legacy(array $planet, int $studentLevel): array
{
    $requiredLevel = (int) ($planet['level'] ?? 0);
    $planet['level'] = $requiredLevel;
    $planet['status'] = mission_student_can_access($studentLevel, $requiredLevel) ? 'Terbuka' : 'Terkunci';
    $planet['source'] = 'legacy';
    $planet['poin'] = (int) ($planet['poin'] ?? 0);
    return $planet;
}

/**
 * Semua planet legacy (kecuali ID yang sudah dipakai di DB) + hanya DB published.
 * Baris draft akan menutupi planet legacy dengan slug sama, bukan membocorkannya.
 */
function bs_student_merge_planets(array $legacy, array $rows, int $studentLevel): array
{
    $reserved = [];
    foreach ($rows as $row) {
        $reserved[(string) $row['id']] = true;
    }
    $result = [];
    foreach ($legacy as $planet) {
        if (!isset($reserved[(string) ($planet['id'] ?? '')])) {
            $result[] = bs_student_map_legacy($planet, $studentLevel);
        }
    }
    foreach ($rows as $row) {
        if (($row['publication_status'] ?? '') === 'published') {
            $result[] = bs_student_map_planet($row, $studentLevel);
        }
    }
    return $result;
}

function bs_student_planets(array $legacy, array $user): array
{
    // Jangan fallback diam-diam ke data lama jika MySQL tidak terhubung.
    return bs_student_merge_planets($legacy, mission_list_planets(false), (int) ($user['level'] ?? 1));
}

/** Fetch-by-id berlaku juga untuk URL langsung. Draft selalu ditolak. */
function bs_student_get_planet(string $id, array $legacy, array $user): ?array
{
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $id) || strlen($id) > 64) {
        return null;
    }
    $studentLevel = (int) ($user['level'] ?? 1);
    $row = mission_get_planet($id, false);
    if ($row !== null) {
        return $row['publication_status'] === 'published'
            ? bs_student_map_planet($row, $studentLevel)
            : null;
    }
    foreach ($legacy as $planet) {
        if (($planet['id'] ?? '') === $id) {
            return bs_student_map_legacy($planet, $studentLevel);
        }
    }
    return null;
}

/** Adaptasi tiap misi menjadi struktur gameModes lama; ID tidak berbenturan. */
function bs_student_modes_from_rows(array $missions, array $legacyModes): array
{
    $catalog = mission_editor_modes();
    $result = [];
    foreach ($missions as $row) {
        if (($row['publication_status'] ?? '') !== 'published') {
            continue;
        }
        $type = (string) ($row['mode_type'] ?? '');
        $category = (string) ($row['category'] ?? '');
        if (!isset($catalog[$type]) || !in_array($category, ['wajib', 'side'], true)) {
            continue;
        }
        $id = 'mission-' . (int) $row['id'];
        $result[$id] = [
            'id' => $id,
            'mission_id' => (int) $row['id'],
            'type' => $type,
            'kategori' => $category,
            'judul' => (string) $row['title'],
            'deskripsi' => (string) $row['description'],
            'ikon' => $catalog[$type]['emoji'],
            'reward' => (int) $row['reward_coins'],
            'timer_detik' => (int) $row['timer_seconds'],
            'jumlah_soal' => (int) $row['question_target'],
            'warna' => $legacyModes[$type]['warna'] ?? [
                'bg' => 'from-indigo-100 to-blue-200',
                'border' => 'border-indigo-300',
                'tombol' => 'bg-indigo-500 hover:bg-indigo-600',
            ],
        ];
    }
    return $result;
}

function bs_student_game_modes(array $planet, array $legacyModes): array
{
    if (($planet['source'] ?? 'legacy') !== 'database') {
        return $legacyModes;
    }
    return bs_student_modes_from_rows(mission_list_for_planet($planet['id'], null, true), $legacyModes);
}
