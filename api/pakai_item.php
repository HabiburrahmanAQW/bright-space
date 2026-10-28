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
$userId = (int) $_SESSION['user_id'];

if (!$itemId) { echo json_encode(['ok' => false, 'error' => 'No item']); exit; }

// Cek user punya item ini
$ada = db_one('SELECT id FROM user_items WHERE user_id = ? AND item_id = ?', [$userId, $itemId]);
if (!$ada) { echo json_encode(['ok' => false, 'error' => 'Item belum dimiliki']); exit; }

// Cari info slot dari config
require __DIR__ . '/../config/planets.php';
$itemData = null;
foreach ($items as $it) {
    if ($it['id'] === $itemId) { $itemData = $it; break; }
}
if (!$itemData) { echo json_encode(['ok' => false, 'error' => 'Item tidak ditemukan']); exit; }

// Update slot
try {
    if (($itemData['slot'] ?? '') === 'topi') {
        db_exec('UPDATE users SET avatar_topi = ? WHERE id = ?', [$itemId, $userId]);
    } elseif (($itemData['slot'] ?? '') === 'kacamata') {
        db_exec('UPDATE users SET avatar_kacamata = ? WHERE id = ?', [$itemId, $userId]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Item ini tidak bisa dipakai']);
        exit;
    }
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}