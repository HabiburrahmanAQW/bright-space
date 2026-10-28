<?php
/** Uji ringan tanpa DB untuk validasi fungsi snapshot dan prosedur instalasi. */
if(PHP_SAPI!=='cli')exit('Jalankan melalui Laragon terminal');
define('BRIGHT_SPACE',true);
require __DIR__.'/../config/publication_repository.php';
// Merender signature pada input identik harus deterministik.
$q=['source_question_id'=>7,'mode_type'=>'kuis','prompt'=>'2+2?','payload_json'=>'{"choices":{"A":"3","B":"4","C":"5"},"correct":"B"}','explanation'=>'4','sort_order'=>1];
$base=bs_pub_signature([$q]);
assert($base===bs_pub_signature([$q]));
$q['prompt']='2+3?';
if($base===bs_pub_signature([$q]))throw new RuntimeException('Digest tidak mendeteksi edit.');
if($base===bs_pub_signature([]))throw new RuntimeException('Digest tidak mendeteksi soal ditambahkan.');
echo "Uji ringan Tahap 6B LOLOS. Pengujian transaksi MySQL harus dilakukan di Laragon.\n";
