<?php
/**
 * BRIGHT SPACE: lapisan baca planet & misi dari tabel bs_*.
 * Tahap 1: belum mengganti data dummy pada halaman siswa.
 */
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');
require_once __DIR__ . '/database.php';

function mission_db(): PDO
{
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('MySQL belum terhubung. Periksa konfigurasi database.');
    }
    return $pdo;
}

/** Status publikasi terpisah dari syarat level siswa. */
function mission_student_can_access(int $studentLevel, int $requiredLevel): bool
{
    return $studentLevel >= $requiredLevel;
}

/**
 * @return array<int, array<string, mixed>>
 */
function mission_list_planets(bool $publishedOnly = false): array
{
    $query = 'SELECT id, name, description, required_level, theme_key, icon_key, publication_status,
                     created_by, created_at, updated_at
              FROM bs_planets';
    if ($publishedOnly) {
        $query .= " WHERE publication_status = 'published'";
    }
    $query .= ' ORDER BY required_level ASC, created_at ASC';
    return mission_db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

function mission_get_planet(string $planetId, bool $publishedOnly = false): ?array
{
    $query = 'SELECT id, name, description, required_level, theme_key, icon_key, publication_status,
                     created_by, created_at, updated_at
              FROM bs_planets WHERE id = ?';
    if ($publishedOnly) {
        $query .= " AND publication_status = 'published'";
    }
    $stmt = mission_db()->prepare($query);
    $stmt->execute([$planetId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/** @return array<int, array<string, mixed>> */
function mission_list_for_planet(string $planetId, ?string $category = null, bool $publishedOnly = false): array
{
    if ($category !== null && !in_array($category, ['wajib', 'side'], true)) {
        throw new InvalidArgumentException('Kategori misi tidak dikenal.');
    }
    $query = 'SELECT id, planet_id, category, mode_type, title, description, reward_coins,
                     timer_seconds, question_target, sort_order, publication_status
              FROM bs_missions WHERE planet_id = ?';
    $params = [$planetId];
    if ($category !== null) {
        $query .= ' AND category = ?';
        $params[] = $category;
    }
    if ($publishedOnly) {
        $query .= " AND publication_status = 'published'";
    }
    $query .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = mission_db()->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
