<?php
/** BRIGHT SPACE — login siswa terverifikasi MySQL (menggantikan bypass demo). */
define('BRIGHT_SPACE',true);
if(session_status()===PHP_SESSION_NONE){
    ini_set('session.use_strict_mode','1');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
require __DIR__.'/config/student_progress.php';
function student_e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$error='';
try {if(bs_verified_student()){header('Location: dashboard.php');exit;}}catch(Throwable $e){error_log('BS auth: '.$e->getMessage());$error='MySQL belum siap. Periksa migrasi Tahap 5.';}
if(empty($_SESSION['bs_login_csrf']))$_SESSION['bs_login_csrf']=bin2hex(random_bytes(32));
if($_SERVER['REQUEST_METHOD']==='POST'){
    $token=$_POST['csrf_token']??null;
    if(!is_string($token)||!hash_equals($_SESSION['bs_login_csrf'],$token)){
        http_response_code(403);$error='Form kedaluwarsa. Segarkan halaman dan coba lagi.';
    } else {
        $username=is_string($_POST['username']??null)?trim($_POST['username']):'';
        $password=is_string($_POST['password']??null)?$_POST['password']:'';
        $count=(int)($_SESSION['bs_login_fail_count']??0);
        $since=(int)($_SESSION['bs_login_fail_since']??0);
        if($since+900<time()){$count=0;$since=time();}
        if($count>=8){http_response_code(429);$error='Terlalu banyak percobaan. Coba lagi 15 menit kemudian.';}
        elseif(strlen($username)>40 || strlen($password)>4096 || $username==='' || $password===''){
            $error='Masukkan username dan password siswa.';
        } else {
            try {
                $pdo=mission_db();
                $stmt=$pdo->prepare('SELECT id,password_hash FROM bs_students WHERE username=? AND is_active=1 LIMIT 1');
                $stmt->execute([$username]);$account=$stmt->fetch(PDO::FETCH_ASSOC);
                if($account && password_verify($password,(string)$account['password_hash'])){
                    session_regenerate_id(true);
                    $_SESSION=[];
                    $_SESSION['bs_student_id']=(int)$account['id'];
                    $_SESSION['user_id']=(int)$account['id'];
                    $_SESSION['bs_login_csrf']=bin2hex(random_bytes(32));
                    header('Location: dashboard.php',true,303);exit;
                }
                $_SESSION['bs_login_fail_count']=$count+1;
                $_SESSION['bs_login_fail_since']=$since;
                $error='Username atau password tidak sesuai.';
            }catch(Throwable $e){error_log('BS auth DB: '.$e->getMessage());http_response_code(503);$error='Database siswa tidak tersedia. Periksa Laragon dan migrasi Tahap 5.';}
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Masuk — BRIGHT SPACE</title><link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><script>tailwind.config={theme:{extend:{colors:{cosmic:{bg:'#E0F2FE',card:'#EDE9FE',accent:'#FEF3C7',text:'#1E1B4B'}},fontFamily:{nunito:['Nunito','sans-serif']}}}};</script></head>
<body class="font-nunito min-h-screen bg-cosmic-bg text-cosmic-text flex items-center justify-center p-4"><main class="w-full max-w-md bg-white border-4 border-amber-200 rounded-3xl shadow-xl p-8"><div class="text-center"><div class="text-5xl mb-3">🚀</div><h1 class="font-black text-3xl">BRIGHT <span class="text-violet-600">SPACE</span></h1><p class="font-bold text-sm mt-1 text-slate-500">Masuk sebagai Kapten Siswa</p></div>
<?php if($error):?><p role="alert" class="mt-5 rounded-2xl bg-rose-50 border-2 border-rose-200 text-rose-800 p-3 font-extrabold text-sm"><?=student_e($error)?></p><?php endif;?>
<form method="post" class="mt-6 space-y-4"><input type="hidden" name="csrf_token" value="<?=student_e($_SESSION['bs_login_csrf'])?>"><div><label class="block font-extrabold text-sm mb-2" for="username">Username Siswa</label><input required autofocus maxlength="40" autocomplete="username" class="w-full border-2 border-violet-200 rounded-2xl p-3 font-bold focus:outline-none focus:ring-4 focus:ring-violet-200" id="username" name="username" value="<?=student_e(is_string($_POST['username']??null)?$_POST['username']:'')?>" placeholder="contoh: kapten1"></div><div><label class="block font-extrabold text-sm mb-2" for="password">Password Siswa</label><input required autocomplete="current-password" type="password" name="password" id="password" class="w-full border-2 border-violet-200 rounded-2xl p-3 font-bold focus:outline-none focus:ring-4 focus:ring-violet-200" placeholder="Password akun siswa"></div><button class="w-full bg-violet-600 hover:bg-violet-700 text-white font-black rounded-full py-3 shadow" type="submit">Mulai Petualangan 🚀</button></form>
<p class="text-xs font-bold text-slate-500 text-center mt-5">Akun siswa dibuat oleh pengelola melalui Terminal Laragon. Akun tutor tetap memakai <code>tutor/login.php</code>.</p></main></body></html>
