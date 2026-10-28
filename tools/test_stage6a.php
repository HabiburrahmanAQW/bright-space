<?php
/** Pemeriksaan struktural Tahap 6A (tanpa mengubah DB). */
if(PHP_SAPI!=='cli') exit('Gunakan terminal.');
$root=dirname(__DIR__);
$patterns=[
 'mainkan-misi.php'=>['BS_STAGE6A_FINISH_CLOSE','close_finished','bs-mission-finished','bs-finish-form','bs-mission-done','bs_award'],
 'assets/js/student-mission-modal.js'=>['BS_STAGE6A_FINISHED_UI','isSavedFinish()','closeSavedFinish()','confirmEndMission()','close_finished'],
 'dashboard.php'=>['BS_STAGE6A_DASHBOARD','bs_mission_attempts','total_xp','Total XP'],
 'leaderboard.php'=>['BS_STAGE6A_LEADERBOARD','bs_students','total_xp'],
 'profile.php'=>['BS_STAGE6A_PROFILE','progres-misi.php'],
 'components/sidebar.php'=>['BS_STAGE6A_SIDEBAR','progres-misi.php'],
];
$fail=[];
foreach($patterns as $file=>$needles){
 $path=$root.'/'.$file;
 if(!is_file($path)){$fail[]="$file tidak ditemukan";continue;}
 $s=file_get_contents($path);
 foreach($needles as $needle){if(!str_contains($s,$needle))$fail[]="$file kehilangan: $needle";}
}
if($fail){fwrite(STDERR,"Pemeriksaan gagal:\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo "Struktur Tahap 6A lengkap.\nLanjutkan uji nyata dengan Laragon + MySQL dan checklist README.\n";
