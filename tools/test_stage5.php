<?php
if(PHP_SAPI!=='cli')exit('CLI saja.');
define('BRIGHT_SPACE',true);
require __DIR__.'/../config/student_progress.php';
$checks=[
 [bs_score_reward('kuis',50,10,10),['coins'=>50,'xp'=>60]],
 [bs_score_reward('kuis',50,5,10),['coins'=>25,'xp'=>35]],
 [bs_score_reward('latihan',999,10,10),['coins'=>0,'xp'=>60]],
 [bs_score_reward('kuis',50,0,10),['coins'=>0,'xp'=>10]],
];
foreach($checks as $i=>[$got,$want])if($got!==$want)throw new RuntimeException('Skor test '.($i+1).' gagal.');
if(bs_level_for_xp(0)!==1||bs_level_for_xp(99)!==1||bs_level_for_xp(100)!==2||bs_level_for_xp(230)!==3)throw new RuntimeException('Level test gagal.');
echo "PASS: 4 uji skor, 4 uji level.\nCatatan: transaksi MySQL perlu diuji di Laragon.\n";
