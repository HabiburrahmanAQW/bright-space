<?php
/** Pendaftaran tutor mandiri, tetapi akun belum aktif sampai disetujui admin. */
define('BRIGHT_SPACE',true);
if (session_status()===PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode','1');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
require_once __DIR__.'/config/student_progress.php';
require_once __DIR__.'/config/registration.php';
header('Cache-Control: no-store');
function bs_tutor_reg_e($v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
if (!bs_registration_enabled($_SERVER)) {
    http_response_code(403);
    exit('Pendaftaran tutor belum dibuka. Hubungi pengelola BRIGHT SPACE.');
}
if (empty($_SESSION['bs_tutor_reg_csrf']))$_SESSION['bs_tutor_reg_csrf']=bin2hex(random_bytes(32));
$errors=[];$success='';
$data=['username'=>'','display_name'=>''];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $since=(int)($_SESSION['bs_tutor_reg_since']??0);
    $attempts=(int)($_SESSION['bs_tutor_reg_attempts']??0);
    if (!$since || $since+900<time()){$since=time();$attempts=0;}
    $token=$_POST['csrf_token']??null;
    if(!is_string($token)||!hash_equals((string)$_SESSION['bs_tutor_reg_csrf'],$token)){
        http_response_code(403);$errors[]='Form kedaluwarsa. Segarkan halaman.';
    }elseif($attempts>=6){
        http_response_code(429);$errors[]='Terlalu banyak percobaan. Coba lagi setelah 15 menit.';
    }else{
        $_SESSION['bs_tutor_reg_since']=$since;
        $_SESSION['bs_tutor_reg_attempts']=$attempts+1;
        [$data,$errors]=bs_registration_validate($_POST);
        if (!$errors) {
            try {
                $pdo=mission_db();
                // Username tidak boleh sama dengan username siswa.
                $check=$pdo->prepare('SELECT id FROM bs_students WHERE username=? LIMIT 1');
                $check->execute([$data['username']]);
                if ($check->fetchColumn()) {
                    $errors[]='Username sudah digunakan. Silakan pilih yang lain.';
                } else {
                    $stmt=$pdo->prepare("INSERT INTO bs_tutors(username,display_name,password_hash,role,is_active) VALUES (?,?,?,'tutor',0)");
                    $stmt->execute([$data['username'],$data['display_name'],password_hash($data['password'],PASSWORD_DEFAULT)]);
                    $_SESSION['bs_tutor_reg_notice']='Pendaftaran tutor diterima. Akun menunggu persetujuan administrator sebelum bisa digunakan.';
                    header('Location: register-tutor.php',true,303);exit;
                }
            } catch(PDOException $e) {
                if((string)$e->getCode()==='23000')$errors[]='Username sudah digunakan. Silakan pilih yang lain.';
                else {error_log('Tutor register: '.$e->getMessage());http_response_code(503);$errors[]='Database tidak dapat menerima pendaftaran.';}
            } catch(Throwable $e) {
                error_log('Tutor register: '.$e->getMessage());http_response_code(503);
                $errors[]='Pendaftaran belum berhasil. Periksa koneksi MySQL.';
            }
        }
    }
}
if(isset($_SESSION['bs_tutor_reg_notice'])) {
    $success=(string)$_SESSION['bs_tutor_reg_notice'];
    unset($_SESSION['bs_tutor_reg_notice']);
}
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar Tutor — BRIGHT SPACE</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/auth.css">
</head><body class="bs-auth-page"><main class="bs-auth-card">
    <div class="bs-auth-logo" aria-hidden="true">📚</div>
    <h1>Daftar <span>Tutor</span></h1>
    <p class="bs-auth-intro">Ajukan akun pengajar BRIGHT SPACE. Admin perlu menyetujui akun sebelum kamu bisa mengelola siswa.</p>
    <?php if ($success): ?><p class="bs-auth-success" role="status"><?= bs_tutor_reg_e($success) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="bs-auth-error" role="alert"><?php foreach($errors as $e): ?><p><?= bs_tutor_reg_e($e) ?></p><?php endforeach; ?></div><?php endif; ?>
    <form method="post" action="register-tutor.php" class="bs-auth-form">
        <input type="hidden" name="csrf_token" value="<?= bs_tutor_reg_e($_SESSION['bs_tutor_reg_csrf']) ?>">
        <label for="display_name">Nama Pengajar</label>
        <input id="display_name" name="display_name" required minlength="2" maxlength="80"
          value="<?= bs_tutor_reg_e($data['display_name']) ?>" autocomplete="name">
        <label for="username">Username Tutor</label>
        <input id="username" name="username" required minlength="3" maxlength="30"
          pattern="[a-zA-Z][a-zA-Z0-9_]{2,29}" value="<?= bs_tutor_reg_e($data['username']) ?>" autocomplete="username">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required minlength="12" autocomplete="new-password" placeholder="Minimal 12 karakter">
        <label for="password_confirm">Konfirmasi Password</label>
        <input id="password_confirm" name="password_confirm" type="password" required minlength="12" autocomplete="new-password">
        <button type="submit" class="bs-auth-submit">Ajukan Pendaftaran Tutor</button>
    </form>
    <p class="bs-auth-footnote">Sudah terdaftar? <a href="login.php">Kembali ke Login</a></p>
</main></body></html>
