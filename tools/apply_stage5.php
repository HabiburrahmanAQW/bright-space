<?php
/** BRIGHT SPACE Tahap 5 — patch PHP konservatif; --check read-only, backup saat apply. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Jalankan dari Terminal Laragon.');}
$root=dirname(__DIR__);$asset=__DIR__.'/stage5_assets';$check=in_array('--check',$argv,true);
function s5_once(string $content,string $needle,string $replace,string $file):string{
    $count=substr_count($content,$needle);
    if($count!==1)throw new RuntimeException("{$file}: penanda tidak cocok ({$count}x), kemungkinan file lokal sudah berubah. Tidak akan dipaksakan.");
    return str_replace($needle,$replace,$content);
}
function s5_transform(string $path,string $body,string $assets):string{
    if($path==='login.php'){
        if(str_contains($body,'BRIGHT SPACE — login siswa terverifikasi MySQL'))return $body;
        if(!str_contains($body,'BYPASS MODE: Terima username & password apa saja')){
            throw new RuntimeException('login.php: bukan versi bypass standar. Jangan menimpanya; minta patch sesuai login lokal.');
        }
        return (string)file_get_contents($assets.'/login.php.txt');
    }
    if($path==='config/database.php'){
        if(str_contains($body,'// BS_STUDENT_AUTH_STAGE5'))return $body;
        $body=s5_once($body,
            "function current_user()\n{\n",
            "function current_user()\n{\n    // BS_STUDENT_AUTH_STAGE5: akun siswa MySQL, bukan user demo fallback.\n    if (!empty(\$_SESSION['bs_student_id'])) {\n        require_once __DIR__ . '/student_progress.php';\n        \$s = bs_verified_student();\n        if (!\$s) return null;\n        return [\n            'id' => (int)\$s['id'], 'nama' => \$s['display_name'],\n            'star_coins' => (int)\$s['star_coins'], 'level' => (int)\$s['level'],\n            'total_xp' => (int)\$s['total_xp'],\n            'avatar_topi' => null, 'avatar_kacamata' => null,\n        ];\n    }\n",
            $path);
        $body=s5_once($body,
            "function require_login()\n{\n",
            "function require_login()\n{\n    // Tahap 5: sesi demo lama tidak diizinkan memasuki halaman siswa.\n    if (empty(\$_SESSION['bs_student_id']) || (int)\$_SESSION['bs_student_id'] !== (int)(\$_SESSION['user_id'] ?? 0)) {\n        header('Location: login.php'); exit;\n    }\n",
            $path);
        return $body;
    }
    if($path==='mainkan-misi.php'){
        if(str_contains($body,'// BS_PROGRESS_STAGE5'))return $body;
        if(!str_contains($body,"if(\$action==='abandon')"))throw new RuntimeException('mainkan-misi.php: hotfix tombol X belum terpasang.');
        $body=s5_once($body,
            '$user=current_user();',
            <<<'CODE'
$user=current_user();
// BS_PROGRESS_STAGE5: pastikan siswa punya akun yang benar-benar terverifikasi.
require_once __DIR__.'/config/student_progress.php';
$verified=bs_verified_student();
if(!$verified){http_response_code(403);exit('Login siswa diperlukan untuk bermain. <a href="login.php">Masuk</a>');}
CODE,
            $path);
        $body=s5_once($body,
            "\$idx=(int)\$run['index'];\$finished=\$run['stage']==='finished';\$question=null;",
            <<<'CODE'
// Setelah hasil soal diverifikasi server, simpan penyelesaian sekali secara atomik.
if($run['stage']==='finished' && !isset($run['bs_award'])){
    try {
        $reason = $limit > 0 && time() >= (int)$run['started'] + $limit ? 'timeout' : 'completed';
        $run['bs_award']=bs_finish_mission((int)$verified['id'],$missionId,(int)$run['correct'],count($run['ids']),$reason);
    } catch(Throwable $e){
        error_log('BS mission finish failed: '.$e->getMessage());
        http_response_code(503);exit('Hasil belum berhasil disimpan. Jangan ulangi misi dulu; coba muat ulang halaman untuk menyimpan ulang.');
    }
}
$idx=(int)$run['index'];$finished=$run['stage']==='finished';$question=null;
CODE,
            $path);
        $body=s5_once($body,
            '<p class="text-xs font-bold mt-3 text-slate-600">Hasil masih evaluasi. Koin dan XP belum diberikan pada Tahap 4.</p>',
            <<<'CODE'
<?php $award=$run['bs_award']??['first'=>false,'coins'=>0,'xp'=>0,'balance'=>0,'total_xp'=>0,'level'=>1]; ?>
    <div class="mt-4 rounded-xl border-2 border-amber-200 bg-amber-50 p-3 font-black text-sm">
      <?php if($award['first']): ?>🎉 Reward pertama: +<?= (int)$award['coins'] ?> Koin · +<?= (int)$award['xp'] ?> XP
      <?php else: ?>🔄 Misi dimainkan ulang: tidak ada tambahan koin/XP (nilai terbaik tetap dicatat).
      <?php endif; ?>
      <div class="text-xs text-slate-600 mt-2">Saldo: <?= (int)$award['balance'] ?> koin · Total XP: <?= (int)$award['total_xp'] ?> · Level <?= (int)$award['level'] ?></div>
    </div><a href="progres-misi.php" target="_top" class="block mt-3 text-violet-700 font-black text-sm underline">Lihat Progres Kapten →</a>
CODE,
            $path);
        $body=s5_once($body,
            '<p class="mt-4 text-[11px] text-slate-500 font-bold text-center">Mode evaluasi · belum menambah koin atau XP</p>',
            '<p class="mt-4 text-[11px] text-slate-500 font-bold text-center">Koin/XP diberikan sekali saat penyelesaian pertama. Bermain ulang memperbarui skor terbaik.</p>',
            $path);
        $body=s5_once($body,
            '  tick();setInterval(tick,1000);',
            <<<'CODE'
  tick();setInterval(tick,1000);
CODE,
            $path);
        $body=s5_once($body,
            "<?php if (\$embed): ?>\ndocument.addEventListener('keydown', function(event) {",
            <<<'CODE'
<?php if ($embed && $finished && isset($run['bs_award'])): ?>
window.parent.postMessage({ type: 'bs-mission-reward', balance: <?= (int)$run['bs_award']['balance'] ?>, level: <?= (int)$run['bs_award']['level'] ?> }, location.origin);
<?php endif; ?>
<?php if ($embed): ?>
document.addEventListener('keydown', function(event) {
CODE,
            $path);
        return $body;
    }
    if($path==='assets/js/student-mission-modal.js'){
        if(str_contains($body,'// BS_MISSION_BALANCE_STAGE5'))return $body;
        if(!str_contains($body,'async function confirmEndMission()'))throw new RuntimeException('student-mission-modal.js: hotfix tombol X belum terpasang.');
        $target="    if (event.data && event.data.type === 'bs-mission-close') confirmEndMission();";
        $replacement=<<<'JS'
    // BS_MISSION_BALANCE_STAGE5: refresh badge koin dari hasil server, tidak dari browser.
    if (event.data && event.data.type === 'bs-mission-reward') {
      const balance=Number(event.data.balance);
      if (Number.isSafeInteger(balance) && balance>=0) {
        document.querySelectorAll('#sidebar-koin,#star-coin-count').forEach(el=>el.textContent=String(balance));
        if (window.BRIGHT_SPACE?.user) window.BRIGHT_SPACE.user.star_coins=balance;
      }
    }
    if (event.data && event.data.type === 'bs-mission-close') confirmEndMission();
JS;
        return s5_once($body,$target,$replacement,$path);
    }
    throw new RuntimeException('File tidak didukung.');
}
try {
  $patch=['config/database.php','mainkan-misi.php','assets/js/student-mission-modal.js','login.php'];
  $adds=['config/student_progress.php','tools/create_student.php','tools/test_stage5.php','sql/03_progress_misi.sql','progres-misi.php'];
  $old=[];$new=[];
  foreach($patch as $p){
     $fp=$root.'/'.$p;if(!is_file($fp))throw new RuntimeException("File {$p} tidak ada. Periksa Tahap 1–4.");
     $orig=file_get_contents($fp);if($orig===false)throw new RuntimeException("Tidak bisa membaca {$p}");
     $fixed=s5_transform($p,str_replace("\r\n","\n",$orig),$asset);
     $old[$p]=$orig;$new[$p]=str_contains($orig,"\r\n")?str_replace("\n","\r\n",$fixed):$fixed;
  }
  foreach($adds as $p){
     $src=$asset.'/'.$p;
     if(!is_file($src))throw new RuntimeException("Template {$src} tidak ada. Pastikan salin seluruh folder tools.");
     $dest=$root.'/'.$p;
     $orig=is_file($dest)?file_get_contents($dest):null;
     $body=file_get_contents($src);
     if($orig!==null && $orig!==$body)throw new RuntimeException("{$p} sudah ada dengan konten berbeda. Tidak ditimpa.");
     $old[$p]=$orig;$new[$p]=$body;
  }
  $changes=[];foreach($new as $p=>$body)if($old[$p]!==$body)$changes[]=$p;
  if(!$changes){echo "Tahap 5 sudah terpasang, tidak ada perubahan.\n";exit(0);}
  echo "Rencana perubahan:\n - ".implode("\n - ",$changes)."\n";
  if($check){echo "--check BERHASIL. Tidak ada file aplikasi yang diubah.\n";exit(0);}
  $home=getenv('USERPROFILE')?:getenv('HOME')?:sys_get_temp_dir();
  $backup=rtrim($home,'/\\').'/BRIGHT_SPACE_stage5_backups/'.basename($root).'_'.date('Ymd_His').'_'.bin2hex(random_bytes(2));
  if(!mkdir($backup,0700,true) && !is_dir($backup))throw new RuntimeException('Gagal membuat folder backup.');
  foreach($changes as $p)if($old[$p]!==null){$dest=$backup.'/'.$p;if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0700,true))throw new RuntimeException('Backup folder gagal.');if(file_put_contents($dest,$old[$p],LOCK_EX)===false)throw new RuntimeException("Backup {$p} gagal.");}
  $written=[];
  try{
    foreach($changes as $p){$target=$root.'/'.$p;if(!is_dir(dirname($target))&&!mkdir(dirname($target),0755,true))throw new RuntimeException('Folder tujuan tidak dapat dibuat.');
      if($old[$p]!==null && file_get_contents($target)!==$old[$p])throw new RuntimeException("{$p} berubah saat proses, dibatalkan.");
      if(file_put_contents($target,$new[$p],LOCK_EX)===false)throw new RuntimeException("Gagal menulis {$p}");$written[]=$p;
    }
  }catch(Throwable $e){foreach(array_reverse($written) as $p){if($old[$p]===null)@unlink($root.'/'.$p);else @file_put_contents($root.'/'.$p,$old[$p],LOCK_EX);}throw $e;}
  echo "Tahap 5 BERHASIL. Backup: {$backup}\nImpor sql/03_progress_misi.sql, lalu buat akun siswa baru dengan php tools/create_student.php kapten1\n";
}catch(Throwable $e){fwrite(STDERR,"GAGAL: ".$e->getMessage()."\nTidak ada perubahan paksa.\n");exit(1);}
