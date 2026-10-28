<?php
define('BRIGHT_SPACE', true);
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$itemId = trim($input['item_id'] ?? '');
$slot   = trim($input['slot'] ?? '');
$userId = (int) $_SESSION['user_id'];

if (!$itemId) { echo json_encode(['ok' => false, 'error' => 'No item']); exit; }

// Cari item di config
require __DIR__ . '/../config/planets.php';
$itemData = null;
foreach ($items as $it) {
    if ($it['id'] === $itemId) { $itemData = $it; break; }
}
if (!$itemData) { echo json_encode(['ok' => false, 'error' => 'Item tidak ditemukan']); exit; }

$slotItem = $itemData['slot'] ?? '';

try {
    // Reset slot yang sesuai
    if ($slotItem === 'topi') {
        db_exec('UPDATE users SET avatar_topi = NULL WHERE id = ?', [$userId]);
    } elseif ($slotItem === 'kacamata') {
        db_exec('UPDATE users SET avatar_kacamata = NULL WHERE id = ?', [$userId]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Item ini tidak bisa dilepas']);
        exit;
    }

    // Update is_equipped di user_items
    db_exec('UPDATE user_items SET is_equipped = 0 WHERE user_id = ? AND item_id = ?', [$userId, $itemId]);

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}