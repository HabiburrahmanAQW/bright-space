<?php
/** Test validasi register tanpa koneksi MySQL. Jalankan: php tools/test_stage7c.php */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
define('BRIGHT_SPACE', true);
require __DIR__ . '/../config/registration.php';
function check(bool $ok, string $name): void {
    if (!$ok) { fwrite(STDERR, "GAGAL: ".$name."\n"); exit(1); }
    fwrite(STDOUT, "OK: ".$name."\n");
}
$input=['username'=>'Kapten_7','display_name'=>'Kapten Bintang','password'=>'rahasia-panjang-123','password_confirm'=>'rahasia-panjang-123'];
[$data,$errors]=bs_registration_validate($input);
check(!$errors && $data['username']==='kapten_7','input siswa valid dan username dinormalisasi');
[$data,$errors]=bs_registration_validate($input+[]);
$bad=$input; $bad['username']='1admin'; [, $e]=bs_registration_validate($bad);
check(count($e)>0,'username tidak valid ditolak');
$bad=$input; $bad['password']='pendek'; $bad['password_confirm']='pendek'; [, $e]=bs_registration_validate($bad);
check(count($e)>0,'password pendek ditolak');
$bad=$input; $bad['password']='😀😀😀😀'; $bad['password_confirm']='😀😀😀😀'; [, $e]=bs_registration_validate($bad);
check(count($e)>0,'password beberapa emoji tidak lolos batas minimal karakter');
$bad=$input; $bad['password_confirm']='beda'; [, $e]=bs_registration_validate($bad);
check(count($e)>0,'konfirmasi password divalidasi');
check(bs_registration_enabled(['REMOTE_ADDR'=>'127.0.0.1','SERVER_NAME'=>'localhost']) || getenv('BS_TUTOR_REGISTRATION')==='0','localhost mengizinkan registrasi default');
if (getenv('BS_TUTOR_REGISTRATION') === false || getenv('BS_TUTOR_REGISTRATION') === '') {
    check(!bs_registration_enabled(['REMOTE_ADDR'=>'10.1.2.3','SERVER_NAME'=>'example.org']),'registrasi tutor non-lokal tertutup');
}
echo "Validasi Tahap 7C berhasil. Pengujian login dan database tetap dilakukan di Laragon.\n";
