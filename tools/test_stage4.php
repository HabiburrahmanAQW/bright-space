<?php
if(PHP_SAPI!=='cli')exit("Gunakan terminal.\n");
define('BRIGHT_SPACE',true);
require __DIR__.'/../config/question_repository.php';
$cases=[
 ['kuis',['prompt'=>'2 + 2?', 'choice_A'=>'3','choice_B'=>'4','choice_C'=>'5','correct'=>'B','explanation'=>'Empat'],true],
 ['story',['prompt'=>'Arazka harus memilih...', 'choice_A'=>'Berhenti','choice_B'=>'Bertanya','choice_C'=>'Membiarkan','correct'=>'B'],true],
 ['latihan',['prompt'=>'Uji cepat', 'choice_A'=>'satu','choice_B'=>'dua','choice_C'=>'tiga','correct'=>'A'],true],
 ['puzzle',['prompt'=>'Susun kata antariksa','answer'=>'GALAKSI','scramble'=>'SIKALAG','hint'=>'Kumpulan bintang'],true],
 ['matching',['prompt'=>'Pasangkan simbol', 'left'=>['A','B','C'], 'right'=>['1','2','3']],true],
 ['kuis',['prompt'=>'', 'choice_A'=>'sama','choice_B'=>'sama','choice_C'=>'lain','correct'=>'X'],false],
 ['matching',['prompt'=>'Pasangkan', 'left'=>['A','A','C'], 'right'=>['1','2','3']],false],
 ['puzzle',['prompt'=>'Test','answer'=>'','scramble'=>'X'],false],
 ['puzzle',['prompt'=>'Test','answer'=>'GALAKSI','scramble'=>'MATAHARI'],false],
];
$n=0;
foreach($cases as [$mode,$input,$expected]){
    [$data,$errors]=bmq_validate($mode,$input);
    if((count($errors)===0)!==$expected){fwrite(STDERR,"GAGAL test #".($n+1)." ($mode): ".json_encode($errors)."\n");exit(1);}
    $n++;
}
if(bmq_norm('   Me  RAH   ')!=='me rah'){fwrite(STDERR,"GAGAL normalisasi jawaban\n");exit(1);}
echo "LULUS: 10 pengujian fungsi editor soal.\n";
echo "Catatan: uji ini tidak menghubungi database MySQL.\n";
