<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$userId = require_tutor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}

if (!is_string($_POST['csrf_token'] ?? null) ||
    !hash_equals(csrf_token(), $_POST['csrf_token'])) {
    http_response_code(419);
    exit('Sesi form kedaluwarsa. Muat ulang halaman lalu coba lagi.');
}

function length_of(string $text): int
{
    return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

function whole_number(mixed $value, int $min, int $max): ?int
{
    if (!is_scalar($value) || is_bool($value)) return null;
    $text = (string) $value;
    if (!preg_match('/^(0|[1-9][0-9]*)$/D', $text)) return null;
    $number = filter_var($text, FILTER_VALIDATE_INT);
    if ($number === false || $number < $min || $number > $max) return null;
    return $number;
}

function field_string(array $data, string $key): string
{
    $value = $data[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$errors = [];
$name = field_string($_POST, 'name');
$slug = field_string($_POST, 'slug');
$description = field_string($_POST, 'description');
$minLevel = whole_number($_POST['min_level'] ?? null, 0, 1000);
$theme = field_string($_POST, 'theme_key');
$icon = field_string($_POST, 'icon_key');

if ($name === '' || length_of($name) > 120) $errors[] = 'Nama planet wajib diisi (maksimal 120 karakter).';
if ($description === '' || length_of($description) > 500) $errors[] = 'Deskripsi planet wajib diisi (maksimal 500 karakter).';
if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 80) $errors[] = 'Slug hanya boleh huruf kecil, angka, dan tanda hubung (maksimal 80 karakter).';
if ($minLevel === null) $errors[] = 'Level minimum harus bilangan bulat 0–1000.';
if (!array_key_exists($theme, $catalog['themes'])) $errors[] = 'Tema warna tidak valid.';
if (!array_key_exists($icon, $catalog['icons'])) $errors[] = 'Ikon planet tidak valid.';

$rawMissions = $_POST['missions'] ?? [];
if (!is_array($rawMissions) || count($rawMissions) < 1 || count($rawMissions) > 30) {
    $errors[] = 'Jumlah misi harus antara 1–30.';
    $rawMissions = [];
}

$validMissions = [];
$wajibCount = 0;
$position = ['wajib' => 0, 'sampingan' => 0];
foreach ($rawMissions as $index => $mission) {
    if (!is_array($mission)) {
        $errors[] = 'Format data misi tidak valid.';
        continue;
    }
    $category = field_string($mission, 'category');
    $mode = field_string($mission, 'game_mode');
    $title = field_string($mission, 'title');
    $subtitle = field_string($mission, 'description');
    $reward = whole_number($mission['reward'] ?? null, 0, 10000);
    $timer = whole_number($mission['timer'] ?? null, 0, 7200);
    $questionCount = whole_number($mission['question_count'] ?? null, 1, 100);

    if (!in_array($category, ['wajib', 'sampingan'], true) || !array_key_exists($mode, $catalog['modes'])) {
        $errors[] = 'Kategori atau gamemode tidak valid pada salah satu misi.';
        continue;
    }
    if ($title === '' || length_of($title) > 120) $errors[] = 'Judul misi harus diisi dan maksimal 120 karakter.';
    if (length_of($subtitle) > 300) $errors[] = 'Deskripsi misi maksimal 300 karakter.';
    if ($reward === null || $timer === null || $questionCount === null) $errors[] = 'Reward, timer, dan jumlah soal harus berupa angka sesuai batasnya.';
    if ($mode === 'latihan' && ($reward !== 0 || $timer !== 0)) $errors[] = 'Latihan Bebas harus tanpa timer dan tanpa reward.';
    if ($mode !== 'latihan' && $timer !== null && $timer < 30) $errors[] = 'Gamemode dengan timer membutuhkan minimal 30 detik.';

    if ($category === 'wajib') $wajibCount++;
    $validMissions[] = [
        'category' => $category,
        'game_mode' => $mode,
        'title' => $title,
        'description' => $subtitle,
        'reward' => $reward,
        'timer' => $timer,
        'question_count' => $questionCount,
        'sort_order' => ++$position[$category],
    ];
}
if ($wajibCount < 1) $errors[] = 'Setidaknya satu misi wajib harus dibuat.';

if ($errors) {
    $_SESSION['planet_errors'] = array_values(array_unique($errors));
    $_SESSION['old_planet_form'] = $_POST;
    redirect('tambah-planet.php');
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    $planetStatement = $pdo->prepare(
        'INSERT INTO planets (slug, name, description, min_level, theme_key, icon_key, publication_status, created_by_user_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $planetStatement->execute([$slug, $name, $description, $minLevel, $theme, $icon, 'draft', $userId]);
    $planetId = (int) $pdo->lastInsertId();

    $missionStatement = $pdo->prepare(
        'INSERT INTO missions (planet_id, category, game_mode, title, description, reward_coins, timer_seconds, target_question_count, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($validMissions as $mission) {
        $missionStatement->execute([
            $planetId, $mission['category'], $mission['game_mode'], $mission['title'], $mission['description'],
            $mission['reward'], $mission['timer'], $mission['question_count'], $mission['sort_order'],
        ]);
    }
    $pdo->commit();
    $_SESSION['created_planet_id'] = $planetId;
    redirect('tambah-planet.php?created=1&planet_id=' . $planetId);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Gagal membuat planet: ' . $exception->getMessage());
    $_SESSION['old_planet_form'] = $_POST;
    // 23000 sering berarti UNIQUE slug, tetapi bisa juga constraint lain.
    $_SESSION['planet_errors'] = $exception->getCode() === '23000'
        ? ['Slug planet sudah digunakan atau ada konflik data. Coba slug lain.']
        : ['Gagal menyimpan ke database. Periksa koneksi dan log server.'];
    redirect('tambah-planet.php');
}
