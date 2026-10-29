<?php
/** Authentication for school administrators. Shares the secured tutor session cookie. */
require_once __DIR__ . '/../tutor/_auth.php';

function bs_admin_require_login(): array
{
    $account = tutor_require_login();
    if (($account['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak. Halaman ini khusus Administrator Sekolah.');
    }
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    return $account;
}
