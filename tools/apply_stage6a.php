<?php
/** BRIGHT SPACE Tahap 6A. Patch konservatif untuk instalasi Tahap 5. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Hanya Terminal Laragon.'); }
$root = dirname(__DIR__);
$check = in_array('--check', $argv, true);
function s6_once(string $body, string $needle, string $replace, string $path): string {
    $num = substr_count($body, $needle);
    if ($num !== 1) throw new RuntimeException("{$path}: penanda tidak cocok (ditemukan {$num}x): " . substr($needle, 0, 110));
    return str_replace($needle, $replace, $body);
}
function s6_player(string $body): string {
    $file='mainkan-misi.php';
    if (str_contains($body, 'BS_STAGE6A_FINISH_CLOSE')) return $body;
    if (!str_contains($body, 'BS_PROGRESS_STAGE5') || !str_contains($body, "if(\$action==='abandon')"))
        throw new RuntimeException('mainkan-misi.php harus sudah memasang Tahap 5 dan hotfix tombol X.');
    $extra = <<<'PHP_ACTION'
    // BS_STAGE6A_FINISH_CLOSE: hanya hapus sesi sementara yang SUDAH tersimpan permanen.
    if($action==='close_finished'){
        if(($run['stage']??'')!=='finished' || !isset($run['bs_award'])){
            http_response_code(409);header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>false,'error'=>'Hasil misi belum selesai disimpan.']);exit;
        }
        unset($_SESSION['bs_mission_run']);
        header('Cache-Control: no-store');
        if(str_contains((string)($_SERVER['HTTP_ACCEPT']??''),'application/json')){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>true]);exit;
        }
        header('Location: planet-detail.php?id='.rawurlencode($mission['planet_id']), true, 303);exit;
    }
PHP_ACTION;
    $body=s6_once($body,"    if(\$action==='restart'){",$extra."\n    if(\$action==='restart'){",$file);
    $body=s6_once($body, '<meta name="bs-mission-csrf" content="<?= play_e($csrf) ?>">',
        '<meta name="bs-mission-csrf" content="<?= play_e($csrf) ?>">' . "\n" . '<?php if ($finished && isset($run[\'bs_award\'])): ?><meta name="bs-mission-finished" content="1"><?php endif; ?>', $file);
    $old = '<form method="post" class="mt-5"><input type="hidden" name="csrf_token" value="<?= play_e($csrf) ?>"><button class="w-full py-3 bg-violet-600 hover:bg-violet-700 text-white rounded-full font-black" type="submit" name="action" value="restart">🔄 Main Lagi</button></form>';
    $new = <<<'PHP_HTML'
<div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3" role="group" aria-label="Tindakan setelah misi selesai">
  <form method="post"><input type="hidden" name="csrf_token" value="<?= play_e($csrf) ?>"><button class="w-full py-3 bg-violet-600 hover:bg-violet-700 text-white rounded-full font-black" type="submit" name="action" value="restart">🔄 Main Lagi</button></form>
  <form method="post" id="bs-finish-form"><input type="hidden" name="csrf_token" value="<?= play_e($csrf) ?>"><input type="hidden" name="action" value="close_finished"><button class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-black" type="submit">✅ Selesai</button></form>
</div>
PHP_HTML;
    $body=s6_once($body,$old,$new,$file);
    // Selesai di iframe mengirim pesan; jika JS tidak aktif, submit form native tetap bekerja.
    $injection= <<<'JS'
<?php if ($embed && $finished && isset($run['bs_award'])): ?>
const completedForm = document.getElementById('bs-finish-form');
completedForm?.addEventListener('submit', function(event) {
  if (window.parent === window) return;
  event.preventDefault();
  window.parent.postMessage({ type: 'bs-mission-done' }, location.origin);
});
<?php endif; ?>
JS;
    $body=s6_once($body,"<?php if (\$embed): ?>\ndocument.addEventListener('keydown'",$injection."<?php if (\$embed): ?>\ndocument.addEventListener('keydown'",$file);
    return $body;
}
function s6_modal(string $body): string {
    $file='assets/js/student-mission-modal.js';
    if(str_contains($body,'BS_STAGE6A_FINISHED_UI'))return $body;
    if(!str_contains($body,'async function confirmEndMission()') || !str_contains($body,'BS_MISSION_BALANCE_STAGE5'))
        throw new RuntimeException('student-mission-modal.js harus sudah menggunakan hotfix X dan Tahap 5.');
    $functions = <<<'JS'
  // BS_STAGE6A_FINISHED_UI — status selesai hanya dipercaya setelah halaman PHP
  // selesai menyimpan hasil ke MySQL dan menandai metadata server-side.
  function isSavedFinish() {
    try {
      return !!frame?.contentDocument?.querySelector('meta[name="bs-mission-finished"][content="1"]');
    } catch (_) { return false; }
  }

  async function closeSavedFinish() {
    if (!dialog || endingMission || !isSavedFinish()) return;
    const currentFrame=frame;
    const csrf=currentFrame?.contentDocument?.querySelector('meta[name="bs-mission-csrf"]')?.content || '';
    if (!csrf) { window.alert('Halaman hasil belum selesai dimuat. Coba lagi.'); return; }
    endingMission=true;
    const btn=dialog.querySelector('[data-bs-mission-close]');
    if(btn)btn.disabled=true;
    currentFrame.style.pointerEvents='none';
    try {
      const response=await fetch(currentFrame.src,{
        method:'POST',credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json'},
        body:new URLSearchParams({csrf_token:csrf,action:'close_finished'})
      });
      if(!response.ok)throw new Error('HTTP '+response.status);
      const data=await response.json();
      if(data?.ok!==true)throw new Error('Server belum mengonfirmasi hasil tersimpan.');
      closeMission();
    } catch(error){
      window.alert('Tidak dapat menutup hasil misi dengan aman. Coba tekan Selesai lagi.\n'+error.message);
    } finally {
      endingMission=false;
      if(dialog && btn) btn.disabled=false;
      if(dialog && frame===currentFrame) currentFrame.style.pointerEvents='';
    }
  }

JS;
    $body=s6_once($body, '  async function confirmEndMission() {', $functions.'  async function confirmEndMission() {', $file);
    $body=s6_once($body,
        "    if (!dialog || endingMission) return;\n    const confirmed = window.confirm(",
        "    if (!dialog || endingMission) return;\n    if (isSavedFinish()) return closeSavedFinish(); // Sudah tersimpan: tanpa alert hangus progres.\n    const confirmed = window.confirm(",$file);
    $body=s6_once($body,
        "    if (event.data && event.data.type === 'bs-mission-close') confirmEndMission();",
        "    if (event.data && event.data.type === 'bs-mission-done') closeSavedFinish();\n    if (event.data && event.data.type === 'bs-mission-close') confirmEndMission();",$file);
    return $body;
}
function s6_dashboard(string $body): string {
    $file='dashboard.php';
    if(str_contains($body,'BS_STAGE6A_DASHBOARD'))return $body;
    $start=strpos($body, '// Data dummy (bisa diganti query DB nanti)');
    $end=$start===false?false:strpos($body,'function e($t)', $start);
    if($start===false || $end===false || substr_count($body,'function e($t)')!==1 || !str_contains(substr($body,$start,$end-$start),'$leaderboard = ['))
        throw new RuntimeException('dashboard.php: blok data dummy tidak sesuai versi acuan; tidak ditimpa.');
    $new= <<<'PHP_DATA'
// BS_STAGE6A_DASHBOARD: statistik MySQL, tanpa riwayat/leaderboard fiktif.
require_once __DIR__ . '/config/student_progress.php';
if(!bs_verified_student() || !($bsPdo=db())) { http_response_code(503); exit('Akun siswa atau database tidak tersedia.'); }
$total_bintang = (int)($user['total_xp'] ?? 0); // Kartu pertama: XP, bukan koin duplikat.
$star_coins = (int)$user['star_coins'];
$bsCount=$bsPdo->prepare("SELECT COUNT(*) FROM bs_student_mission_progress p
 JOIN bs_missions m ON m.id=p.mission_id JOIN bs_planets pl ON pl.id=m.planet_id
 WHERE p.student_id=? AND m.publication_status='published' AND pl.publication_status='published'");
$bsCount->execute([$user_id]);$misi_selesai=(int)$bsCount->fetchColumn();
$total_misi=(int)$bsPdo->query("SELECT COUNT(*) FROM bs_missions m JOIN bs_planets p ON p.id=m.planet_id
 WHERE m.publication_status='published' AND p.publication_status='published'")->fetchColumn();
$daily_progress=$total_misi>0?(int)round(100*$misi_selesai/$total_misi):0;
$daily_progress=min(100,$daily_progress);
$bsDates=$bsPdo->prepare('SELECT DISTINCT DATE(completed_at) AS day FROM bs_mission_attempts WHERE student_id=? ORDER BY day DESC LIMIT 60');
$bsDates->execute([$user_id]);
$daySet=array_flip(array_column($bsDates->fetchAll(PDO::FETCH_ASSOC),'day'));
$streak_hari=0;
// Menghitung hari beruntun berdasarkan tanggal server, tidak lagi angka dummy.
$today=new DateTimeImmutable('today');
if(!isset($daySet[$today->format('Y-m-d')]))$today=$today->modify('-1 day');
while(isset($daySet[$today->format('Y-m-d')]) && $streak_hari<60){$streak_hari++;$today=$today->modify('-1 day');}
$bsHistory=$bsPdo->prepare('SELECT pl.id AS planet_id,pl.name AS planet_name,m.title AS mission_title,m.mode_type,
 a.correct_count AS soal_benar,a.question_count AS soal_total,
 ROUND(100 * a.correct_count / NULLIF(a.question_count,0)) AS akurasi,
 a.awarded_coins AS reward,a.completed_at
 FROM bs_mission_attempts a JOIN bs_missions m ON m.id=a.mission_id
 JOIN bs_planets pl ON pl.id=m.planet_id WHERE a.student_id=? ORDER BY a.completed_at DESC,a.id DESC LIMIT 3');
$bsHistory->execute([$user_id]);$riwayat=$bsHistory->fetchAll(PDO::FETCH_ASSOC);
$topRows=$bsPdo->query('SELECT id,display_name,total_xp FROM bs_students WHERE is_active=1 ORDER BY total_xp DESC,id ASC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
$leaderboard=[];
foreach($topRows as $i=>$row){$leaderboard[]=['nama'=>$row['display_name'],'bintang'=>(int)$row['total_xp'],
 'rank'=>$i+1,'medal'=>['🥇','🥈','🥉'][$i],'is_me'=>(int)$row['id']===$user_id];}

PHP_DATA;
    $body=substr($body,0,$start).$new.substr($body,$end);
    $body=s6_once($body,'Selesaikan 1 Kuis Matematika & Kumpulkan 20 Koin Bintang!',
      'Lanjutkan Misi dan Kumpulkan XP untuk Naik Level!',$file);
    $body=s6_once($body,'1 dari 2 Misi Selesai',
      '<?= (int) $misi_selesai ?> dari <?= (int) $total_misi ?> Misi Selesai',$file);
    $body=s6_once($body,'Total Bintang','Total XP',$file);
    $body=s6_once($body,'Top Kapten Minggu Ini','Top Kapten (Total XP)',$file);
    $body=s6_once($body,'<?= (int) $lb[\'bintang\'] ?> ⭐','<?= (int) $lb[\'bintang\'] ?> ✨ XP',$file);
    $body=s6_once($body, "<?= e(namaPlanet(\$r['planet_id'] ?? '')) ?> — <?= e(namaMode(\$r['mode_type'] ?? '')) ?>",
        "<?= e(\$r['planet_name'] ?? '') ?> — <?= e(\$r['mission_title'] ?? '') ?>",$file);
    return $body;
}
function s6_leaderboard(string $body): string {
    $file='leaderboard.php';
    if(str_contains($body,'BS_STAGE6A_LEADERBOARD'))return $body;
    $start=strpos($body,'// DUMMY DATA LEADERBOARD');
    $end=$start===false?false:strpos($body,'function e($t)', $start);
    if($start===false || $end===false || substr_count($body,'function e($t)')!==1 || !str_contains(substr($body,$start,$end-$start),'$top3'))
        throw new RuntimeException('leaderboard.php: blok dummy berbeda. Tidak akan menimpa.');
    $startLine=strrpos(substr($body,0,$start),'// ============================================================');
    if($startLine!==false && $start-$startLine<100)$start=$startLine;
    $data= <<<'PHP_DATA'
// BS_STAGE6A_LEADERBOARD — berdasarkan total XP siswa di MySQL.
// Koin dapat dibelanjakan, jadi peringkat memakai XP kumulatif (bukan saldo koin).
require_once __DIR__.'/config/student_progress.php';
if(!bs_verified_student() || !($bsPdo=db())){http_response_code(503);exit('Akun siswa atau database tidak tersedia.');}
$rows=$bsPdo->query('SELECT id,display_name,total_xp,level FROM bs_students WHERE is_active=1 ORDER BY total_xp DESC,id ASC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
$leaderboard=[];
foreach($rows as $row){
    $leaderboard[]=[
        'id'=>(int)$row['id'],'nama'=>$row['display_name'],'kelas'=>'Lv. '.(int)$row['level'],
        'bintang'=>(int)$row['total_xp'],'avatar'=>'🧑‍🚀','is_me'=>(int)$row['id']===$user_id
    ];
}
$top3=array_slice($leaderboard,0,3);
$rest=array_slice($leaderboard,3);

PHP_DATA;
    $body=substr($body,0,$start).$data.substr($body,$end);
    $body=s6_once($body,'Top Kapten dengan Bintang Terbanyak Minggu Ini!','Peringkat berdasarkan total XP sepanjang waktu, bukan saldo koin.',$file);
    $body=str_replace('Kelas <?= e($u[\'kelas\']) ?>','<?= e($u[\'kelas\']) ?>',$body);
    $body=str_replace('SDN BRIGHT SPACE','Komunitas BRIGHT SPACE',$body);
    $body=str_replace('Papan Skor Sekolah','Papan Skor Kapten',$body);
    $body=str_replace('Data peringkat berdasarkan total Bintang (⭐) yang dikumpulkan minggu ini.',
        'Data peringkat berdasarkan total XP sepanjang waktu. Koin tidak menentukan peringkat.',$body);
    $body=str_replace('<?= (int) $u[\'bintang\'] ?> ⭐','<?= (int) $u[\'bintang\'] ?> XP',$body);
    return $body;
}
function s6_profile(string $body): string {
    $file='profile.php';if(str_contains($body,'BS_STAGE6A_PROFILE'))return $body;
    $body=s6_once($body, '$koinSekarang = (int) $_SESSION[\'koin\'];',
      '// BS_STAGE6A_PROFILE: saldo berasal dari bs_students, bukan sesi demo.' . "\n" . '$koinSekarang = (int) ($user[\'star_coins\'] ?? 0);',$file);
    $needle='<main class="max-w-5xl mx-auto px-4 py-6 w-full flex-1">';
    $add= <<<'HTML'
<aside class="mb-4 bg-white border-2 border-violet-200 rounded-2xl p-4 text-sm font-bold flex flex-wrap justify-between gap-2">
  <span>✨ Total XP: <?= (int)($user['total_xp'] ?? 0) ?> &nbsp; · &nbsp; ⭐ Level <?= (int)($user['level'] ?? 1) ?></span>
  <a href="progres-misi.php" class="text-violet-700 font-black underline">Lihat progres misi →</a>
</aside>
HTML;
    return s6_once($body,$needle,$needle."\n".$add,$file);
}
function s6_sidebar(string $body): string {
    $file='components/sidebar.php';if(str_contains($body,'BS_STAGE6A_SIDEBAR'))return $body;
    $needle="    ['href' => 'leaderboard.php',  'icon' => '🏆', 'label' => 'Papan Skor'],";
    return s6_once($body,$needle,$needle."\n    // BS_STAGE6A_SIDEBAR: progres nyata siswa\n    ['href' => 'progres-misi.php', 'icon' => '✨', 'label' => 'Progres Misi'],",$file);
}
$targets=[
 'mainkan-misi.php'=>'s6_player',
 'assets/js/student-mission-modal.js'=>'s6_modal',
 'dashboard.php'=>'s6_dashboard',
 'leaderboard.php'=>'s6_leaderboard',
 'profile.php'=>'s6_profile',
 'components/sidebar.php'=>'s6_sidebar'
];
try {
    $old=[];$new=[];$changed=[];
    foreach($targets as $p=>$fn){
        $full=$root.'/'.$p;
        if(!is_file($full))throw new RuntimeException("File {$p} tidak ditemukan. Pastikan Tahap 5 sudah terpasang.");
        $original=file_get_contents($full);if($original===false)throw new RuntimeException("Tidak dapat membaca {$p}");
        $normalized=str_replace("\r\n","\n",$original);
        $updated=$fn($normalized);
        $updated=str_contains($original,"\r\n")?str_replace("\n","\r\n",$updated):$updated;
        $old[$p]=$original;$new[$p]=$updated;
        if($original!==$updated)$changed[]=$p;
    }
    if(!$changed){echo "Tahap 6A sudah terpasang; tidak ada perubahan.\n";exit(0);}
    echo "File yang akan diubah:\n - ".implode("\n - ",$changed)."\n";
    if($check){echo "--check BERHASIL. Belum ada file yang diubah.\n";exit(0);}
    $home=getenv('USERPROFILE')?:getenv('HOME')?:sys_get_temp_dir();
    $backup=rtrim($home,'/\\').'/BRIGHT_SPACE_stage6a_backups/'.basename($root).'_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
    if(!mkdir($backup,0700,true) && !is_dir($backup))throw new RuntimeException('Gagal membuat folder backup.');
    foreach($changed as $p){$dest=$backup.'/'.$p;if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0700,true))throw new RuntimeException('Gagal membuat folder backup.');
        if(file_put_contents($dest,$old[$p],LOCK_EX)!==strlen($old[$p]))throw new RuntimeException("Backup {$p} gagal.");}
    $written=[];
    try{
        foreach($changed as $p){$target=$root.'/'.$p;
            if(file_get_contents($target)!==$old[$p])throw new RuntimeException("File {$p} berubah selama pemasangan. Dibatalkan.");
            if(file_put_contents($target,$new[$p],LOCK_EX)!==strlen($new[$p]))throw new RuntimeException("Gagal menulis {$p}");
            $written[]=$p;
        }
    }catch(Throwable $e){foreach(array_reverse($written) as $p)@file_put_contents($root.'/'.$p,$old[$p],LOCK_EX);throw $e;}
    echo "Tahap 6A BERHASIL dipasang. Backup: {$backup}\nRefresh browser (Ctrl+F5). Tidak perlu impor SQL.\n";
}catch(Throwable $e){fwrite(STDERR,"GAGAL: ".$e->getMessage()."\nTidak ada perubahan paksa.\n");exit(1);}
