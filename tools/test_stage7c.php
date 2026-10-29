<?php
/** Database-free Stage 7C admin/tutor/student role split validation. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
define('BRIGHT_SPACE',true);
require __DIR__.'/../config/registration.php';
function check(bool $yes,string $message): void {
    if(!$yes){fwrite(STDERR,"GAGAL: $message\n");exit(1);}
    fwrite(STDOUT,"OK: $message\n");
}
[$identity,$errors]=bs_account_validate_identity(['username'=>'Kapten_7','display_name'=>'Kapten Bintang']);
check(!$errors && $identity['username']==='kapten_7','identitas dinormalisasi');
[, $errors]=bs_account_validate_identity(['username'=>'123','display_name'=>'Kapten']);
check(count($errors)>0,'username salah ditolak');
[, $errors]=bs_registration_validate(['username'=>'guru_andi','display_name'=>'Guru Andi','password'=>'pendek','password_confirm'=>'pendek']);
check(count($errors)>0,'password pendek ditolak');
$root=dirname(__DIR__);
function src(string $p): string {
    global $root;
    $s=@file_get_contents($root.'/'.$p);
    if(!is_string($s))throw new RuntimeException('File tidak ditemukan: '.$p);
    return $s;
}
$login=src('login.php');
check(str_contains($login,'admin/index.php') && str_contains($login,'tutor/index.php') && str_contains($login,'dashboard.php'),'redirect login tiga peran');
check(!str_contains($login,'register-tutor.php') && !str_contains($login,'bs-auth-tabs'),'login tanpa register/tab role');
check(!is_file($root.'/register-tutor.php') && !is_file($root.'/register.php'),'register publik dihapus');
$guard=src('admin/_auth.php');
check(str_contains($guard,"'admin'") && str_contains($guard,'http_response_code(403)'),'proteksi admin di backend');
check(str_contains(src('tutor/_auth.php'),'tutor_require_teacher'),'proteksi tutor di backend');
foreach(['admin/index.php','admin/tutors.php','admin/students.php'] as $p)
    check(str_contains(src($p),'bs_admin_require_login'),'halaman admin dilindungi: '.$p);
$crud=src('admin/_accounts.php');
check(str_contains($crud,'password_hash') && str_contains($crud,'tutor_valid_csrf'),'CRUD dengan hash dan CSRF');
check(str_contains($crud,'is_active') && !str_contains($crud,'DELETE FROM'),'nonaktifkan tanpa hard delete');
foreach(['tutor/planet-form.php','tutor/planet-save.php','tutor/mission-list.php','tutor/planet-publication.php','tutor/questions.php','tutor/question-action.php'] as $p)
    check(str_contains(src($p),'tutor_require_teacher'),'hanya tutor yang boleh edit materi: '.$p);
check(!is_file($root.'/tutor/students.php') && !is_file($root.'/tutor/accounts.php'),'endpoint akun tutor lama dihapus');
echo "Pemeriksaan statis Tahap 7C selesai. Uji browser dan MySQL Laragon tetap diperlukan.\n";
