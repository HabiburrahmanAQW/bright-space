<?php
require __DIR__ . '/_auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !tutor_valid_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Permintaan tidak valid.');
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();
header('Location: login.php');
exit;
