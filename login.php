<?php
/**
 * Satu form login untuk siswa dan tutor. Role dikenali dari akun terverifikasi,
 * bukan dari pilihan atau parameter yang dikirim browser.
 */
define('BRIGHT_SPACE', true);
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime'=>0, 'path'=>'/',
        'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off',
        'httponly'=>true, 'samesite'=>'Lax'
    ]);
    session_start();
}
require_once __DIR__.'/config/student_progress.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function bs_login_e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
}
$error='';
try {
    if (bs_verified_student()) {header('Location: dashboard.php', true, 303);exit;}
} catch(Throwable $e) {error_log('Login status: '.$e->getMessage());}
if (empty($_SESSION['bs_login_csrf'])) {
    $_SESSION['bs_login_csrf']=bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $token=$_POST['csrf_token']??null;
    if (!is_string($token)||!hash_equals((string)$_SESSION['bs_login_csrf'],$token)) {
        http_response_code(403);$error='Form kedaluwarsa. Segarkan halaman.';
    } else {
        $username=is_string($_POST['username']??null)?strtolower(trim($_POST['username'])):'';
        $password=is_string($_POST['password']??null)?$_POST['password']:'';
        $count=(int)($_SESSION['bs_login_fail_count']??0);
        $since=(int)($_SESSION['bs_login_fail_since']??0);
        if (!$since||$since+900<time()) {$since=time();$count=0;}
        if ($count>=8) {
            http_response_code(429);$error='Terlalu banyak percobaan. Coba lagi setelah 15 menit.';
        } elseif ($username===''||strlen($username)>40||$password===''||strlen($password)>4096) {
            $error='Isi username dan password dengan benar.';
        } else {
            try {
                $pdo=mission_db();
                $s=$pdo->prepare('SELECT id,password_hash FROM bs_students WHERE username=? AND is_active=1 LIMIT 1');
                $t=$pdo->prepare("SELECT id,password_hash FROM bs_tutors WHERE username=? AND is_active=1 AND role IN ('tutor','admin') LIMIT 1");
                $s->execute([$username]);$student=$s->fetch(PDO::FETCH_ASSOC);
                $t->execute([$username]);$tutor=$t->fetch(PDO::FETCH_ASSOC);
                $studentOk=$student&&password_verify($password,(string)$student['password_hash']);
                $tutorOk=$tutor&&password_verify($password,(string)$tutor['password_hash']);

                // Bila username/password identik terdapat pada dua tabel legacy, hentikan
                // daripada mengarahkan ke role yang tidak diharapkan.
                if ($studentOk xor $tutorOk) {
                    if ($studentOk) {
                        session_regenerate_id(true);
                        $_SESSION=[];
                        $_SESSION['bs_student_id']=(int)$student['id'];
                        $_SESSION['user_id']=(int)$student['id'];
                        $_SESSION['bs_login_csrf']=bin2hex(random_bytes(32));
                        header('Location: dashboard.php',true,303);exit;
                    }
                    // Tutup session siswa dulu, lalu buka cookie session tutor yang terpisah.
                    session_write_close();
                    session_name('BRIGHTSPACE_TUTOR');
                    ini_set('session.use_strict_mode','1');
                    session_set_cookie_params([
                        'lifetime'=>0,'path'=>'/',
                        'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off',
                        'httponly'=>true,'samesite'=>'Lax'
                    ]);
                    session_start();
                    session_regenerate_id(true);
                    $_SESSION=[];
                    $_SESSION['tutor_id']=(int)$tutor['id'];
                    $_SESSION['tutor_csrf']=bin2hex(random_bytes(32));
                    header('Location: tutor/index.php',true,303);exit;
                }
                if ($studentOk && $tutorOk) {
                    error_log('BRIGHT SPACE: ambiguous cross-role legacy credentials for username '.$username);
                }
                $_SESSION['bs_login_fail_count']=$count+1;
                $_SESSION['bs_login_fail_since']=$since;
                $error='Username atau password tidak sesuai.';
            } catch(Throwable $e) {
                error_log('BRIGHT SPACE login DB: '.$e->getMessage());
                http_response_code(503);
                $error='Login belum tersedia. Periksa koneksi MySQL.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk — BRIGHT SPACE</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/auth.css">
</head><body class="bs-auth-page">
<main class="bs-auth-card">
    <div class="bs-auth-logo" aria-hidden="true">🚀</div>
    <h1>BRIGHT <span>SPACE</span></h1>
    <p class="bs-auth-intro">Masukkan akun BRIGHT SPACE untuk melanjutkan petualangan atau mengelola pembelajaran.</p>
    <?php if ($error!==''): ?><p class="bs-auth-error" role="alert"><?= bs_login_e($error) ?></p><?php endif; ?>
    <form method="post" action="login.php" class="bs-auth-form">
        <input type="hidden" name="csrf_token" value="<?= bs_login_e($_SESSION['bs_login_csrf']) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" required autofocus maxlength="40" autocomplete="username"
            value="<?= bs_login_e(is_string($_POST['username']??null)?$_POST['username']:'') ?>"
            placeholder="Username akunmu">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password"
            placeholder="Masukkan password">
        <button type="submit" class="bs-auth-submit">Masuk ke BRIGHT SPACE 🚀</button>
    </form>
    <p class="bs-auth-footnote">Akun siswa diberikan oleh tutor atau sekolah.</p>
    <p class="bs-auth-footnote">Pengajar baru? <a href="register-tutor.php">Daftar sebagai tutor</a></p>
</main></body></html>
