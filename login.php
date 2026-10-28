<?php
/**
 * Pintu masuk terpadu BRIGHT SPACE.
 * Siswa dan tutor memiliki cookie/session PHP yang BERBEDA.
 */
define('BRIGHT_SPACE', true);
$role = (isset($_GET['role']) && $_GET['role'] === 'tutor') ? 'tutor' : 'siswa';
$isTutor = $role === 'tutor';

if ($isTutor) {
    require_once __DIR__ . '/tutor/_auth.php'; // BRIGHTSPACE_TUTOR
} else {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true, 'samesite' => 'Lax'
        ]);
        session_start(); // sesi siswa lama (PHPSESSID)
    }
    require_once __DIR__ . '/config/student_progress.php';
    require_once __DIR__ . '/config/registration.php';
}
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function bs_login_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$error = '';
$notice = '';
try {
    if ($isTutor && tutor_current()) {
        header('Location: tutor/index.php', true, 303); exit;
    }
    if (!$isTutor && bs_verified_student()) {
        header('Location: dashboard.php', true, 303); exit;
    }
} catch (Throwable $e) {
    error_log('BRIGHT SPACE login status check: ' . $e->getMessage());
    $error = 'Database belum siap. Periksa koneksi MySQL.';
}

if ($isTutor) {
    $csrfToken = tutor_csrf_token();
} else {
    if (empty($_SESSION['bs_login_csrf'])) {
        $_SESSION['bs_login_csrf'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['bs_login_csrf'];
    if (isset($_SESSION['bs_register_flash'])) {
        $notice = (string)$_SESSION['bs_register_flash'];
        unset($_SESSION['bs_register_flash']);
    }
}
$registrationEnabled = !$isTutor && bs_registration_enabled($_SERVER);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validToken = $isTutor
        ? tutor_valid_csrf($_POST['csrf_token'] ?? null)
        : (is_string($_POST['csrf_token'] ?? null)
            && hash_equals((string)$_SESSION['bs_login_csrf'], $_POST['csrf_token']));
    if (!$validToken) {
        http_response_code(403);
        $error = 'Form sudah kedaluwarsa. Muat ulang halaman.';
    } else {
        $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        if (!$isTutor) $username = strtolower($username);
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $key = $isTutor ? 'bs_tutor_login_fail' : 'bs_student_login_fail';
        $count = (int)($_SESSION[$key . '_count'] ?? 0);
        $since = (int)($_SESSION[$key . '_since'] ?? 0);
        if ($since === 0 || $since + 900 < time()) { $count = 0; $since = time(); }
        if ($count >= 8) {
            http_response_code(429);
            $error = 'Terlalu banyak percobaan. Coba lagi setelah 15 menit.';
        } elseif ($username === '' || strlen($username) > 40 || $password === '' || strlen($password) > 4096) {
            $error = 'Masukkan username dan password yang benar.';
        } else {
            try {
                $pdo = db();
                if (!$pdo) throw new RuntimeException('Koneksi database gagal.');
                if ($isTutor) {
                    $stmt = $pdo->prepare(
                        "SELECT id, password_hash FROM bs_tutors WHERE username=? AND is_active=1 AND role IN ('tutor','admin') LIMIT 1"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        'SELECT id,password_hash FROM bs_students WHERE username=? AND is_active=1 LIMIT 1'
                    );
                }
                $stmt->execute([$username]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($account && password_verify($password, (string)$account['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION = [];
                    if ($isTutor) {
                        $_SESSION['tutor_id'] = (int)$account['id'];
                        $_SESSION['tutor_csrf'] = bin2hex(random_bytes(32));
                        header('Location: tutor/index.php', true, 303);
                    } else {
                        $_SESSION['bs_student_id'] = (int)$account['id'];
                        $_SESSION['user_id'] = (int)$account['id'];
                        $_SESSION['bs_login_csrf'] = bin2hex(random_bytes(32));
                        header('Location: dashboard.php', true, 303);
                    }
                    exit;
                }
                $_SESSION[$key . '_count'] = $count + 1;
                $_SESSION[$key . '_since'] = $since;
                $error = 'Username atau password tidak sesuai.';
            } catch (Throwable $e) {
                error_log('BRIGHT SPACE login: ' . $e->getMessage());
                http_response_code(503);
                $error = 'Login belum tersedia. Periksa koneksi MySQL dan tabel akun.';
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
    <title>Masuk — BRIGHT SPACE</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="bs-auth-page">
<main class="bs-auth-card">
    <div class="bs-auth-logo" aria-hidden="true">🚀</div>
    <h1>BRIGHT <span>SPACE</span></h1>
    <p class="bs-auth-intro"><?= $isTutor ? 'Masuk sebagai Tutor atau Administrator' : 'Masuk dan lanjutkan petualanganmu, Kapten!' ?></p>
    <nav class="bs-auth-tabs" aria-label="Pilih jenis akun">
        <a href="login.php?role=siswa" <?= !$isTutor ? 'aria-current="page"' : '' ?>
           class="<?= !$isTutor ? 'is-active' : '' ?>">🚀 Siswa</a>
        <a href="login.php?role=tutor" <?= $isTutor ? 'aria-current="page"' : '' ?>
           class="<?= $isTutor ? 'is-active' : '' ?>">📚 Tutor</a>
    </nav>
    <?php if ($notice !== ''): ?><p class="bs-auth-success" role="status"><?= bs_login_e($notice) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="bs-auth-error" role="alert"><?= bs_login_e($error) ?></p><?php endif; ?>
    <form method="post" action="login.php?role=<?= bs_login_e($role) ?>" class="bs-auth-form" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= bs_login_e($csrfToken) ?>">
        <label for="username">Username <?= $isTutor ? 'Tutor' : 'Siswa' ?></label>
        <input id="username" name="username" type="text" required autofocus maxlength="40"
               autocomplete="username" value="<?= bs_login_e(is_string($_POST['username'] ?? null) ? $_POST['username'] : '') ?>"
               placeholder="<?= $isTutor ? 'Username pengajar' : 'Username Kapten' ?>">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password"
               placeholder="Masukkan password akun">
        <button type="submit" class="bs-auth-submit">Masuk sebagai <?= $isTutor ? 'Tutor 📚' : 'Kapten 🚀' ?></button>
    </form>
    <?php if ($isTutor): ?>
        <p class="bs-auth-footnote">Akun tutor hanya dibuat oleh administrator BRIGHT SPACE.</p>
    <?php elseif ($registrationEnabled): ?>
        <p class="bs-auth-footnote">Belum memiliki akun? <a href="register.php">Daftar sebagai Kapten</a></p>
    <?php else: ?>
        <p class="bs-auth-footnote">Pendaftaran siswa belum dibuka. Hubungi pengelola.</p>
    <?php endif; ?>
</main>
</body>
</html>
