<?php
define('BRIGHT_SPACE', true);
session_start();
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = (int) $_SESSION['user_id'];

if (isset($input['umur'])) {
    $umur = (int) $input['umur'];
    if ($umur >= 5 && $umur <= 15) {
        db_exec('UPDATE users SET umur = ? WHERE id = ?', [$umur, $userId]);
    }
}

if (isset($input['pelajaran'])) {
    $pel = substr(trim($input['pelajaran']), 0, 30);
    db_exec('UPDATE users SET pelajaran_favorit = ? WHERE id = ?', [$pel, $userId]);
}

echo json_encode(['ok' => true]);