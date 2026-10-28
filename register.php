<?php
/** Registrasi siswa via web; akun tutor hanya melalui admin yang sudah masuk. */
define('BRIGHT_SPACE', true);
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax'
    ]);
    session_start();
}
require_once __DIR__ . '/config/student_progress.php';
require_once __DIR__ . '/config/registration.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function bs_register_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!bs_registration_enabled($_SERVER)) {
    http_response_code(403);
    exit('Pendaftaran publik belum diaktifkan. Hubungi pengelola BRIGHT SPACE.');
}
try {
    if (bs_verified_student()) {
        header('Location: dashboard.php', true, 303); exit;
    }
} catch (Throwable $e) {
    error_log('Registrasi session: '.$e->getMessage());
}
if (empty($_SESSION['bs_register_csrf'])) $_SESSION['bs_register_csrf'] = bin2hex(random_bytes(32));
$errors = [];
$data = ['username' => '', 'display_name' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $since = (int)($_SESSION['bs_register_since'] ?? 0);
    $attempts = (int)($_SESSION['bs_register_attempts'] ?? 0);
    if ($since === 0 || $since + 900 < time()) { $since = time(); $attempts = 0; }
    if (!is_string($token) || !hash_equals((string)$_SESSION['bs_register_csrf'], $token)) {
        http_response_code(403);
        $errors[] = 'Sesi formulir tidak valid. Segarkan halaman.';
    } elseif ($attempts >= 6) {
        http_response_code(429);
        $errors[] = 'Terlalu banyak percobaan. Coba lagi setelah 15 menit.';
    } else {
        $_SESSION['bs_register_since'] = $since;
        $_SESSION['bs_register_attempts'] = $attempts + 1;
        [$data, $errors] = bs_registration_validate($_POST);
        if (!$errors) {
            try {
                $pdo = mission_db();
                $stmt = $pdo->prepare(
                    'INSERT INTO bs_students(username,display_name,password_hash) VALUES(?,?,?)'
                );
                $stmt->execute([
                    $data['username'], $data['display_name'],
                    password_hash($data['password'], PASSWORD_DEFAULT)
                ]);
                session_regenerate_id(true);
                $_SESSION['bs_register_flash'] = 'Akun Kapten berhasil dibuat. Silakan masuk!';
                header('Location: login.php?role=siswa', true, 303);
                exit;
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '23000') {
                    $errors[] = 'Username tersebut sudah digunakan. Coba nama lain.';
                } else {
                    error_log('Registrasi siswa gagal: '.$e->getMessage());
                    http_response_code(503);
                    $errors[] = 'Database belum dapat menyimpan akun. Hubungi pengelola.';
                }
            } catch (Throwable $e) {
                error_log('Registrasi siswa: '.$e->getMessage());
                http_response_code(503);
                $errors[] = 'Pendaftaran gagal. Silakan coba lagi.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Kapten — BRIGHT SPACE</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="bs-auth-page">
<main class="bs-auth-card">
    <div class="bs-auth-logo" aria-hidden="true">🌟</div>
    <h1>Jadi <span>Kapten!</span></h1>
    <p class="bs-auth-intro">Buat akun untuk menjelajahi planet-planet pembelajaran.</p>
    <?php if ($errors): ?>
        <div class="bs-auth-error" role="alert">
            <?php foreach ($errors as $message): ?><p><?= bs_register_e($message) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="register.php" autocomplete="on" class="bs-auth-form">
        <input type="hidden" name="csrf_token" value="<?= bs_register_e($_SESSION['bs_register_csrf']) ?>">
        <label for="display_name">Nama tampilan Kapten</label>
        <input id="display_name" name="display_name" type="text" required minlength="2" maxlength="80"
               autocomplete="nickname" value="<?= bs_register_e($data['display_name']) ?>"
               placeholder="Nama yang muncul di papan skor">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required minlength="3" maxlength="30"
               pattern="[a-zA-Z][a-zA-Z0-9_]{2,29}" autocomplete="username"
               value="<?= bs_register_e($data['username']) ?>" placeholder="Contoh: kapten_bintang">
        <p class="bs-auth-hint">Gunakan huruf, angka, atau garis bawah (_). Tidak perlu nama asli.</p>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="12"
               autocomplete="new-password" placeholder="Minimal 12 karakter">
        <label for="password_confirm">Ulangi password</label>
        <input id="password_confirm" name="password_confirm" type="password" required minlength="12"
               autocomplete="new-password" placeholder="Ketik ulang password">
        <button type="submit" class="bs-auth-submit">Buat Akun Kapten 🚀</button>
    </form>
    <p class="bs-auth-footnote">Sudah punya akun? <a href="login.php?role=siswa">Masuk di sini</a></p>
    <p class="bs-auth-hint bs-auth-center">Gunakan akun bersama orang tua/wali atau sekolah bila diperlukan. Jangan bagikan password.</p>
</main>
</body>
</html>
