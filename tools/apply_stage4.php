<?php
/** Patch aman Tahap 4: hanya dua file lama, dengan --check dan backup sebelum menulis. */
if (PHP_SAPI!=='cli') { http_response_code(403); exit('Gunakan CLI'); }
$root=dirname(__DIR__);$check=in_array('--check',$argv,true);
function s4_once(string $body,string $before,string $after,string $file):string {
    $n=substr_count($body,$before);
    if($n!==1)throw new RuntimeException("{$file}: penanda tidak cocok ({$n}x). Simpan perubahan; kirim error ini, jangan paksa patch.");
    return str_replace($before,$after,$body);
}
function s4_patch(string $file,string $s):string {
    if($file==='planet-detail.php') {
        if(str_contains($s,'bmq_ready('))return $s;
        $s=s4_once($s,"require_once __DIR__ . '/config/student_mission_bridge.php';", "require_once __DIR__ . '/config/student_mission_bridge.php';\nrequire_once __DIR__ . '/config/question_repository.php';",$file);
        $before='<?php $canPlay = ($planet[\'source\'] ?? \'legacy\') === \'legacy\' && in_array($mode[\'type\'], [\'kuis\', \'story\', \'latihan\'], true); ?>';
        $after=$before."\n                        <?php \$dbPlayable = (\$planet['source'] ?? 'legacy') === 'database'\n                            && !empty(\$mode['mission_id'])\n                            && bmq_ready((int)\$mode['mission_id'], (string)\$mode['type'], (int)\$mode['jumlah_soal']); ?>\n                        <?php if (\$dbPlayable): ?>\n                        <a href=\"mainkan-misi.php?mission=<?= (int)\$mode['mission_id'] ?>\" class=\"block w-full mt-4 text-center text-white font-extrabold text-base py-3 rounded-full shadow <?= e(\$mode['warna']['tombol']) ?> hover:scale-105\">Mulai Misi →</a>\n                        <?php else: ?>";
        $s=s4_once($s,$before,$after,$file);
        $buttonClose="<?= \$canPlay ? 'Mulai Game →' : '⏳ Soal belum tersedia' ?>\n                        </button>";
        $s=s4_once($s,$buttonClose,$buttonClose."\n                        <?php endif; ?>",$file);
        return $s;
    }
    if($file==='tutor/index.php') {
        if(str_contains($s,'mission-list.php?planet='))return $s;
        $needle='<a class="mt-auto block text-center py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-black" href="planet-form.php?id=<?= rawurlencode($p[\'id\']) ?>">✏️ Edit Planet</a>';
        $new=$needle."\n                        <a class=\"mt-2 block text-center py-2.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-indigo-900 font-black\" href=\"mission-list.php?planet=<?= rawurlencode(\$p['id']) ?>\">📝 Kelola Soal Misi</a>";
        return s4_once($s,$needle,$new,$file);
    }
    throw new LogicException('File tidak dikenal');
}
$files=['planet-detail.php','tutor/index.php'];
try{
    foreach(['config/question_repository.php','mainkan-misi.php','tutor/questions.php','tutor/mission-list.php','tutor/question-action.php'] as $f){if(!is_file($root.'/'.$f))throw new RuntimeException("File baru {$f} belum disalin.");}
    $old=[];$new=[];
    foreach($files as $file){$raw=file_get_contents($root.'/'.$file);if($raw===false)throw new RuntimeException("Gagal membaca {$file}");$crlf=str_contains($raw,"\r\n");$trans=s4_patch($file,str_replace("\r\n","\n",$raw));$old[$file]=$raw;$new[$file]=$crlf?str_replace("\n","\r\n",$trans):$trans;}
    $changed=array_values(array_filter($files,fn($file)=>$old[$file]!==$new[$file]));
    if(!$changed){echo "Tahap 4 sudah terpasang; tidak perlu patch ulang.\n";exit;}
    echo 'Siap mengubah: '.implode(', ',$changed)."\n";
    if($check){echo "--check berhasil: belum ada file yang diubah.\n";exit;}
    $home=getenv('USERPROFILE')?:getenv('HOME')?:sys_get_temp_dir();
    $dir=rtrim($home,'/\\').'/BRIGHT_SPACE_stage4_backups/'.basename($root).'_'.date('Ymd_His');
    if(!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Tidak dapat membuat folder backup');
    foreach($changed as $file){$dst=$dir.'/'.$file;if(!is_dir(dirname($dst)))mkdir(dirname($dst),0700,true);if(file_put_contents($dst,$old[$file])===false)throw new RuntimeException("Backup gagal {$file}");}
    $written=[];
    try{
      foreach($changed as $file){if(file_put_contents($root.'/'.$file,$new[$file],LOCK_EX)===false)throw new RuntimeException("Gagal menulis {$file}");$written[]=$file;}
    }catch(Throwable $e){foreach($written as $file)file_put_contents($root.'/'.$file,$old[$file],LOCK_EX);throw $e;}
    echo "Tahap 4 berhasil diterapkan.\nBackup: {$dir}\n";
}catch(Throwable $e){fwrite(STDERR,'GAGAL: '.$e->getMessage()."\nTidak memaksa perubahan.\n");exit(1);}
