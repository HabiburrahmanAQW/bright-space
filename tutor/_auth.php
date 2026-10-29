<?php
/** Autentikasi khusus tutor - tidak memakai bypass login siswa. */
if (!defined('BRIGHT_SPACE')) {
    define('BRIGHT_SPACE', true);
}

if (session_status() === PHP_SESSION_NONE) {
    // Cookie berbeda dari sesi siswa: tutor dan siswa dapat diuji pada browser yang sama.
    session_name('BRIGHTSPACE_TUTOR');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function tutor_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tutor_csrf_token(): string
{
    if (empty($_SESSION['tutor_csrf'])) {
        $_SESSION['tutor_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['tutor_csrf'];
}

function tutor_valid_csrf($token): bool
{
    return is_string($token) && hash_equals(tutor_csrf_token(), $token);
}

function tutor_current(): ?array
{
    if (empty($_SESSION['tutor_id'])) {
        return null;
    }
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('MySQL belum tersedia. Periksa konfigurasi database.');
    }
    // Gunakan PDO secara langsung supaya error SQL tidak tersamarkan sebagai daftar kosong.
    $stmt = $pdo->prepare(
        "SELECT id, username, display_name, role FROM bs_tutors
         WHERE id = ? AND is_active = 1 AND role IN ('tutor', 'admin') LIMIT 1"
    );
    $stmt->execute([(int) $_SESSION['tutor_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function tutor_require_login(): array
{
    try {
        $tutor = tutor_current();
    } catch (Throwable $e) {
        error_log('Tutor database error: ' . $e->getMessage());
        http_response_code(503);
        exit('Database tutor belum siap. Periksa koneksi dan migrasi SQL.');
    }
    if (!$tutor) {
        unset($_SESSION['tutor_id']);
        header('Location: login.php');
        exit;
    }
    return $tutor;
}

/** Akses khusus tutor pengajar; administrator hanya mengelola akun dan pemantauan. */
function tutor_require_teacher(): array
{
    $account = tutor_require_login();
    if (($account['role'] ?? '') !== 'tutor') {
        http_response_code(403);
        exit('Akses hanya untuk Tutor. Administrator menggunakan Dashboard Admin.');
    }
    return $account;
}
