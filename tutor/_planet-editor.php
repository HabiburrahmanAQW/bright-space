<?php
/** Shared validation, ownership checks and flash helpers. */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/mission_repository.php';
require_once __DIR__ . '/../config/mission_catalog.php';

function planet_editor_owned(string $id, array $tutor, bool $lock = false): ?array
{
    $sql = 'SELECT id, name, description, required_level, theme_key, icon_key, publication_status, created_by
            FROM bs_planets WHERE id = ?';
    $params = [$id];
    if ($tutor['role'] !== 'admin') {
        $sql .= ' AND created_by = ?';
        $params[] = (int) $tutor['id'];
    }
    if ($lock) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = mission_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function planet_editor_flash(string $target, array $errors, array $old): never
{
    $_SESSION['planet_editor_flash'] = [
        'errors' => $errors,
        'old' => $old,
    ];
    header('Location: ' . $target, true, 303);
    exit;
}

function planet_editor_string(array $arr, string $key): string
{
    return isset($arr[$key]) && is_string($arr[$key]) ? trim($arr[$key]) : '';
}

function planet_editor_length(string $s): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($s, 'UTF-8');
    }
    $n = preg_match_all('/[\s\S]/u', $s);
    return $n === false ? PHP_INT_MAX : $n;
}

function planet_editor_number($value, int $min, int $max): ?int
{
    if (!is_int($value) && !is_string($value)) {
        return null;
    }
    $n = filter_var($value, FILTER_VALIDATE_INT);
    return $n !== false && $n >= $min && $n <= $max ? $n : null;
}

/** @return array{0: array<string,mixed>, 1: list<string>} */
function planet_editor_validate(array $input, bool $isEdit): array
{
    $errors = [];
    $name = planet_editor_string($input, 'name');
    $slug = planet_editor_string($input, 'slug');
    $description = planet_editor_string($input, 'description');
    $level = planet_editor_number($input['required_level'] ?? null, 0, 1000);
    $theme = planet_editor_string($input, 'theme_key');
    $icon = planet_editor_string($input, 'icon_key');
    $action = planet_editor_string($input, 'save_action');

    if ($name === '' || planet_editor_length($name) > 120) $errors[] = 'Nama planet wajib diisi (maksimal 120 karakter).';
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 64) $errors[] = 'Slug harus huruf kecil, angka, atau tanda hubung (maksimal 64 karakter).';
    if (planet_editor_length($description) > 500) $errors[] = 'Deskripsi planet maksimal 500 karakter.';
    if ($level === null) $errors[] = 'Level minimum harus antara 0 sampai 1000.';
    if (!array_key_exists($theme, mission_editor_themes())) $errors[] = 'Tema planet tidak valid.';
    if (!array_key_exists($icon, mission_editor_icons())) $errors[] = 'Ikon planet tidak valid.';
    if (!in_array($action, ['draft', 'draft_next'], true)) $errors[] = 'Pilihan penyimpanan tidak valid.';

    $raw = $input['missions_json'] ?? '[]';
    $missions = null;
    if (!is_string($raw) || strlen($raw) > 65000) {
        $errors[] = 'Ukuran data misi terlalu besar.';
    } else {
        try {
            $missions = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $errors[] = 'Data misi tidak dapat dibaca.';
        }
    }
    if (!is_array($missions) || !array_is_list($missions) || count($missions) > 24) {
        $errors[] = 'Maksimal 24 misi dalam satu planet.';
        $missions = [];
    }

    $normalized = [];
    $usedIds = [];
    foreach ($missions as $index => $m) {
        $number = $index + 1;
        if (!is_array($m) || array_is_list($m)) {
            $errors[] = "Misi #{$number}: format data tidak sesuai.";
            continue;
        }
        $id = $m['id'] ?? null;
        $id = ($id === null || $id === '') ? null : planet_editor_number($id, 1, PHP_INT_MAX);
        if (($m['id'] ?? null) !== null && ($m['id'] ?? null) !== '' && $id === null) {
            $errors[] = "Misi #{$number}: ID tidak valid.";
        }
        if ($id !== null) {
            if (isset($usedIds[$id])) $errors[] = "Misi #{$number}: ID misi ganda.";
            $usedIds[$id] = true;
        }
        $category = planet_editor_string($m, 'category');
        $mode = planet_editor_string($m, 'mode_type');
        $title = planet_editor_string($m, 'title');
        $desc = planet_editor_string($m, 'description');
        $reward = planet_editor_number($m['reward_coins'] ?? null, 0, 1000);
        $timer = planet_editor_number($m['timer_seconds'] ?? null, 0, 3600);
        $questions = planet_editor_number($m['question_target'] ?? null, 1, 50);
        if (!in_array($category, ['wajib', 'side'], true)) $errors[] = "Misi #{$number}: kategori tidak valid.";
        if (!array_key_exists($mode, mission_editor_modes())) $errors[] = "Misi #{$number}: gamemode tidak valid.";
        if ($title === '' || planet_editor_length($title) > 160) $errors[] = "Misi #{$number}: judul harus 1–160 karakter.";
        if (planet_editor_length($desc) > 500) $errors[] = "Misi #{$number}: deskripsi maksimal 500 karakter.";
        if ($reward === null) $errors[] = "Misi #{$number}: koin 0–1000.";
        if ($timer === null) $errors[] = "Misi #{$number}: timer 0–3600 detik.";
        if ($questions === null) $errors[] = "Misi #{$number}: jumlah soal 1–50.";
        if ($mode === 'latihan' && ($timer !== 0 || $reward !== 0)) $errors[] = "Misi #{$number}: latihan bebas harus tanpa timer dan tanpa koin.";
        $normalized[] = [
            'id' => $id, 'category' => $category, 'mode_type' => $mode,
            'title' => $title, 'description' => $desc, 'reward_coins' => $reward,
            'timer_seconds' => $timer, 'question_target' => $questions,
        ];
    }
    if (!$isEdit) {
        foreach ($normalized as $m) {
            if ($m['id'] !== null) $errors[] = 'Planet baru tidak boleh mempunyai ID misi lama.';
        }
    }
    return [[
        'id' => $slug, 'name' => $name, 'description' => $description,
        'required_level' => $level, 'theme_key' => $theme, 'icon_key' => $icon,
        'publication_status' => 'draft', 'missions' => $normalized,
    ], $errors];
}
