<?php
/** Jalankan dari Terminal Laragon: php tools/create_student.php kapten1 */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Hanya untuk terminal.');}
define('BRIGHT_SPACE',true);
require __DIR__.'/../config/database.php';
$username=trim((string)($argv[1]??''));
if(!preg_match('/^[A-Za-z0-9_]{3,40}$/D',$username)){fwrite(STDERR,"Format: php tools/create_student.php kapten1\nUsername 3-40 huruf/angka/underscore.\n");exit(1);}
function bs_read(string $prompt):string{echo $prompt;return trim((string)fgets(STDIN));}
$name=bs_read('Nama tampil: ');
if($name===''||strlen($name)>100){fwrite(STDERR,"Nama tampilan harus diisi, maksimal 100 byte.\n");exit(1);}
$pass=bs_read('Password siswa (min. 12 karakter, terlihat di terminal): ');
if(strlen($pass)<12){fwrite(STDERR,"Password terlalu pendek.\n");exit(1);}
$again=bs_read('Ulangi password: ');
if(!hash_equals($pass,$again)){fwrite(STDERR,"Konfirmasi password tidak cocok.\n");exit(1);}
try {
  $pdo=db();if(!$pdo)throw new RuntimeException('MySQL tidak terhubung.');
  $stmt=$pdo->prepare('INSERT INTO bs_students(username,display_name,password_hash) VALUES(?,?,?)');
  $stmt->execute([$username,$name,password_hash($pass,PASSWORD_DEFAULT)]);
  echo "Akun siswa berhasil dibuat. Login dengan USERNAME: {$username}\n";
} catch(Throwable $e){fwrite(STDERR,'Gagal membuat akun: '.$e->getMessage()."\n");exit(1);}
