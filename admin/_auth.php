<?php
/** Administrator guard: uses the same BRIGHTSPACE_TUTOR cookie as tutor, but strictly role=admin. */
require_once __DIR__.'/../tutor/_auth.php';

function bs_admin_require_login(): array
{
    try {
        $account=tutor_current();
    } catch(Throwable $e) {
        error_log('Admin authentication DB: '.$e->getMessage());
        http_response_code(503);
        exit('Database belum tersedia untuk autentikasi admin.');
    }
    if (!$account) {
        unset($_SESSION['tutor_id']);
        header('Location: ../login.php', true, 303);
        exit;
    }
    if (($account['role']??'')!=='admin') {
        http_response_code(403);
        exit('Akses ditolak. Halaman ini hanya untuk Administrator Sekolah.');
    }
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    return $account;
}
