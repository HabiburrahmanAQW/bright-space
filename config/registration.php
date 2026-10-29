<?php
/** Validasi akun baru. Tidak membuat akun tutor dari form publik. */
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');

/** Normalisasi identitas untuk pembuatan dan penyuntingan akun. */
function bs_account_validate_identity(array $input): array
{
    $username = is_string($input['username'] ?? null) ? strtolower(trim($input['username'])) : '';
    $name = is_string($input['display_name'] ?? null) ? trim($input['display_name']) : '';
    $errors = [];
    if (!preg_match('/^[a-z][a-z0-9_]{2,29}$/D', $username)) {
        $errors[] = 'Username: 3–30 karakter, diawali huruf; gunakan huruf kecil, angka, atau _.';
    }
    $len = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : preg_match_all('/./us', $name);
    if (!preg_match('//u', $name) || $len===false || $len<2 || $len>80 || preg_match('/[\x00-\x1F\x7F]/u',$name)) {
        $errors[] = 'Nama tampilan harus 2–80 karakter tanpa karakter kontrol.';
    }
    return [['username'=>$username,'display_name'=>$name], $errors];
}

function bs_registration_validate(array $input): array
{
    [$identity, $errors] = bs_account_validate_identity($input);
    $rawPassword=$input['password']??'';
    $rawConfirm=$input['password_confirm']??'';
    $password=is_string($rawPassword)?$rawPassword:'';
    $confirm=is_string($rawConfirm)?$rawConfirm:'';
    $length=function_exists('mb_strlen')?mb_strlen($password,'UTF-8'):preg_match_all('/./us',$password);
    if ($length===false || $length<12 || strlen($password)>72) {
        $errors[]='Password minimal 12 karakter dan maksimal 72 byte.';
    }
    if ($password!==$confirm) $errors[]='Konfirmasi password belum cocok.';
    return [$identity+['password'=>$password], $errors];
}
