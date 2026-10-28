<?php
// ============================================================
// simpan_koin.php — Sync koin dari kuis ke session
// ============================================================
define('BRIGHT_SPACE', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$tambahan = isset($_POST['koin_tambahan']) ? (int) $_POST['koin_tambahan'] : 0;

if (!isset($_SESSION['koin'])) {
    $_SESSION['koin'] = 120;
}

$_SESSION['koin'] += $tambahan;
$_SESSION['star_coins'] = $_SESSION['koin'];

echo json_encode(['ok' => true, 'koin' => $_SESSION['koin']]);