<?php
/**
 * Jalankan HANYA dari terminal, misalnya:
 * D:\Xampp\php\php.exe tools\create_tutor.php tutor1
 * Password diminta melalui stdin; jangan simpan password di command line.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Hanya dapat dijalankan melalui PHP CLI.');
}
define('BRIGHT_SPACE', true);
require __DIR__ . '/../config/database.php';

$username = trim((string) ($argv[1] ?? ''));
if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username)) {
    fwrite(STDERR, "Cara pakai: php tools/create_tutor.php username_tutor (3-40 karakter: huruf, angka, _).\n");
    exit(1);
}

fwrite(STDOUT, 'Nama tampilan tutor: ');
$name = trim((string) fgets(STDIN));
if (!preg_match('//u', $name) || preg_match_all('/./us', $name) < 2 || preg_match_all('/./us', $name) > 100) {
    fwrite(STDERR, "Nama harus 2-100 karakter.\n");
    exit(1);
}

fwrite(STDOUT, 'Password tutor (minimal 12 karakter; mungkin terlihat saat diketik): ');
$password = rtrim((string) fgets(STDIN), "\r\n");
if (strlen($password) < 12 || strlen($password) > 256) {
    fwrite(STDERR, "Password harus 12-256 byte.\n");
    exit(1);
}

$pdo = db();
if (!$pdo) {
    fwrite(STDERR, "MySQL tidak terhubung. Periksa XAMPP dan config/database.php.\n");
    exit(1);
}
try {
    $stmt = $pdo->prepare('INSERT INTO bs_tutors (username, display_name, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $name, password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Akun tutor berhasil dibuat: $username\n");
} catch (PDOException $e) {
    fwrite(STDERR, "Gagal membuat tutor. Periksa migrasi SQL atau username sudah terpakai.\n");
    error_log($e->getMessage());
    exit(1);
}
