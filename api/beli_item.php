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

// Cek sudah dimiliki?
$ada = db_one('SELECT id FROM user_items WHERE user_id = ? AND item_id = ?', [$userId, $itemId]);
if ($ada) { echo json_encode(['ok' => false, 'error' => 'Sudah dimiliki']); exit; }

// Ambil data item dari config
require __DIR__ . '/../config/planets.php';
$itemData = null;
foreach ($items as $it) {
    if ($it['id'] === $itemId) { $itemData = $it; break; }
}
if (!$itemData) { echo json_encode(['ok' => false, 'error' => 'Item tidak ditemukan']); exit; }

// Cek koin cukup
$user = db_one('SELECT star_coins FROM users WHERE id = ?', [$userId]);
if (!$user) { echo json_encode(['ok' => false, 'error' => 'User tidak ditemukan']); exit; }
if ((int) $user['star_coins'] < (int) $itemData['harga']) {
    echo json_encode(['ok' => false, 'error' => 'Koin tidak cukup', 'kurang' => (int) $itemData['harga'] - (int) $user['star_coins']]);
    exit;
}

// Proses beli (transaksi biar aman)
try {
    db()->beginTransaction();
    db_exec('UPDATE users SET star_coins = star_coins - ? WHERE id = ?', [$itemData['harga'], $userId]);
    db_exec('INSERT INTO user_items (user_id, item_id, is_equipped) VALUES (?, ?, 1)', [$userId, $itemId]);
    db()->commit();

    // Ambil koin terbaru
    $userBaru = db_one('SELECT star_coins FROM users WHERE id = ?', [$userId]);

    echo json_encode([
        'ok' => true,
        'star_coins' => (int) $userBaru['star_coins'],
        'item' => [
            'id' => $itemData['id'],
            'nama' => $itemData['nama'],
            'harga' => (int) $itemData['harga'],
        ]
    ]);
} catch (Exception $e) {
    db()->rollBack();
    echo json_encode(['ok' => false, 'error' => 'Gagal simpan: ' . $e->getMessage()]);
}