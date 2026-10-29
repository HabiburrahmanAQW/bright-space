<?php
/** Validasi akun baru. Tidak membuat akun tutor dari form publik. */
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');

function bs_registration_enabled(array $server): bool
{
    $setting = getenv('BS_TUTOR_REGISTRATION');
    if ($setting === '0') return false;
    if ($setting === '1') return true;

    // Default development only. Deployment publik harus memilih kebijakan registrasi sendiri.
    $address = (string)($server['REMOTE_ADDR'] ?? '');
    $host = strtolower((string)($server['SERVER_NAME'] ?? ''));
    return in_array($address, ['127.0.0.1', '::1'], true)
        && (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test'));
}

function bs_registration_validate(array $input): array
{
    $rawUsername = $input['username'] ?? '';
    $rawName = $input['display_name'] ?? '';
    $rawPassword = $input['password'] ?? '';
    $rawConfirm = $input['password_confirm'] ?? '';

    $username = is_string($rawUsername) ? strtolower(trim($rawUsername)) : '';
    $displayName = is_string($rawName) ? trim($rawName) : '';
    $password = is_string($rawPassword) ? $rawPassword : '';
    $confirm = is_string($rawConfirm) ? $rawConfirm : '';
    $errors = [];

    if (!preg_match('/^[a-z][a-z0-9_]{2,29}$/D', $username)) {
        $errors[] = 'Username: 3–30 karakter, diawali huruf; gunakan huruf kecil, angka, atau _.';
    }
    $len = function_exists('mb_strlen') ? mb_strlen($displayName, 'UTF-8') : preg_match_all('/./us', $displayName);
    if (!preg_match('//u', $displayName) || $len === false || $len < 2 || $len > 80
        || preg_match('/[\x00-\x1F\x7F]/u', $displayName)) {
        $errors[] = 'Nama tampilan harus 2–80 karakter dan tidak berisi karakter kontrol.';
    }
    $passwordChars = function_exists('mb_strlen') ? mb_strlen($password, 'UTF-8') : preg_match_all('/./us', $password);
    if ($passwordChars === false || $passwordChars < 12 || strlen($password) > 72) {
        $errors[] = 'Password minimal 12 karakter dan maksimal 72 byte.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Konfirmasi password belum cocok.';
    }
    return [['username' => $username, 'display_name' => $displayName, 'password' => $password], $errors];
}
