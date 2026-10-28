<?php
define('BRIGHT_SPACE', true);
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {
    $rows = db_all('SELECT item_id, is_equipped FROM user_items WHERE user_id = ?', [$userId]);
    $user = db_one('SELECT star_coins, avatar_topi, avatar_kacamata FROM users WHERE id = ?', [$userId]);

    echo json_encode([
        'ok' => true,
        'items' => $rows,
        'star_coins' => (int) $user['star_coins'],
        'topi' => $user['avatar_topi'],
        'kacamata' => $user['avatar_kacamata'],
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}