<?php
/** BRIGHT SPACE 6B. Jalankan dengan php tools/apply_stage6b.php [--check] */
if(PHP_SAPI!=='cli')exit('Hanya dari Terminal Laragon.');
$root=dirname(__DIR__);$check=in_array('--check',$argv,true);
function bs6b_once(string $s,string $needle,string $replacement,string $path):string{
    $n=substr_count($s,$needle);
    if($n!==1)throw new RuntimeException("{$path}: penanda tidak cocok ({$n}x): ".substr($needle,0,105));
    return str_replace($needle,$replacement,$s);
}
function bs6b_between(string $s,string $start,string $end,string $replacement,string $file):string{
    $pos=strpos($s,$start);$stop=$pos===false?false:strpos($s,$end,$pos);
    if($pos===false||$stop===false||substr_count($s,$start)!==1||substr_count($s,$end)!==1)throw new RuntimeException("{$file}: batas blok lama tidak dikenali.");
    return substr($s,0,$pos).$replacement.substr($s,$stop);
}
function bs6b_patch(string $p,string $s,string $root):string{
 if(str_contains($s,'BS_STAGE6B_'))return $s;
 switch($p){
 case 'config/question_repository.php':
   $needle='    return $target >= 1 && bmq_count($missionId, $mode) >= $target;';
   $new='    // BS_STAGE6B_PUBLIC_COUNT — siswa hanya melihat soal yang telah dipublikasikan.' . "\n" .
   '    if (function_exists(\'bs_pub_player_count\')) return $target >= 1 && bs_pub_player_count($missionId,$mode) >= $target;' . "\n" . $needle;
   return bs6b_once($s,$needle,$new,$p);
 case 'mainkan-misi.php':
   $s=bs6b_once($s,"require_once __DIR__.'/config/question_repository.php';", "require_once __DIR__.'/config/question_repository.php';\n// BS_STAGE6B_PLAYER_SNAPSHOT\nrequire_once __DIR__.'/config/publication_repository.php';",$p);
   $s=bs6b_once($s,'$questions=bmq_list((int)$mission[\'id\'],$mode);','$questions=bs_pub_player_list((int)$mission[\'id\'],$mode);',$p);
   $needle='if(empty($_SESSION[\'bs_mission_run\'])){';
   $new=<<<'SRC'
// Jika tutor menerapkan Update Publikasi saat siswa mengerjakan misi, mulai ulang soal
// agar tidak ada ID soal lama atau jumlah target yang berbeda dari snapshot terbaru.
$bsSnapshotSig=hash('sha256',json_encode([$target,$questions],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
if(!empty($_SESSION['bs_mission_run']) && ($_SESSION['bs_mission_run']['bs_snapshot_sig']??'')!==$bsSnapshotSig)unset($_SESSION['bs_mission_run']);
SRC;
   $s=bs6b_once($s,$needle,$new."\n".$needle,$p);
   $needle="'matching_index'=>-1]";
   $s=bs6b_once($s,$needle,"'matching_index'=>-1,'bs_snapshot_sig'=>\$bsSnapshotSig]",$p);
   return $s;
 case 'planet-detail.php':
   $needle="require_once __DIR__ . '/config/question_repository.php';";
   if(!str_contains($s,$needle))throw new RuntimeException('planet-detail.php belum terpasang Tahap 4.');
   return bs6b_once($s,$needle,$needle."\n// BS_STAGE6B_STUDENT_READY\nrequire_once __DIR__ . '/config/publication_repository.php';",$p);
 case 'tutor/mission-list.php':
   $needle="require_once __DIR__.'/../config/question_repository.php';";
   $s=bs6b_once($s,$needle,$needle."\n// BS_STAGE6B_MISSION_LIST\nrequire_once __DIR__.'/../config/publication_repository.php';",$p);
   $needle='$flash = $_SESSION[\'bs_publication_flash\'] ?? null;';
   $s=bs6b_once($s,$needle,'$bsPubState=bs_pub_status($planetId);' . "\n" . $needle,$p);
   $needle='<h2 id="publish-title" class="font-black text-2xl mt-1">🚀 Publikasi Planet</h2>';
   $s=bs6b_once($s,$needle,$needle.'<p class="text-sm font-bold text-indigo-700 mt-2">📝 Soal editor: <?= (int)$bsPubState[\'live_questions\'] ?> · 🌍 Soal publik: <?= (int)$bsPubState[\'published_questions\'] ?> · Perubahan: <?= (int)$bsPubState[\'pending_missions\'] ?> misi</p>',$p);
   $needle='<button type="submit" name="action" value="draft" class="w-full px-6 py-3 rounded-2xl font-black bg-amber-100 border-2 border-amber-300 text-amber-900 hover:bg-amber-200">↩ Kembalikan ke Draft</button>';
   $addon=<<<'HTML'
<?php if ($bsPubState['has_changes']): ?>
<button type="submit" name="action" value="update" class="w-full mt-2 px-6 py-3 rounded-2xl font-black bg-emerald-600 text-white hover:bg-emerald-700">🔄 Update Publikasi (<?= (int)$bsPubState['pending_missions'] ?> misi berubah)</button>
<p class="text-xs font-bold mt-2 text-amber-800">Soal baru dan edit soal hanya terlihat siswa setelah Update Publikasi. Jika jumlah soal lebih banyak dari target, target dinaikkan otomatis saat update.</p>
<?php else: ?>
<p class="mt-2 text-center font-extrabold text-emerald-700 text-sm">✅ Publikasi sudah terbaru</p>
<?php endif; ?>
HTML;
   return bs6b_once($s,$needle,$needle."\n".$addon,$p);
 case 'tutor/questions.php':
   $needle="require_once __DIR__ . '/../config/question_repository.php';";
   $s=bs6b_once($s,$needle,$needle."\n// BS_STAGE6B_QUESTION_NOTE\nrequire_once __DIR__ . '/../config/publication_repository.php';",$p);
   $needle='<p class="font-bold text-slate-600 mt-1">';
   $s=bs6b_once($s,$needle,'<p class="mt-2 text-sm font-black text-amber-700">⚠️ Setelah menambah atau mengubah soal di planet Published, buka daftar misi dan klik Update Publikasi agar perubahan tampil untuk siswa. <a class="underline text-violet-700" href="mission-list.php?planet=<?= rawurlencode($mission[\'planet_id\']) ?>">← Ke Daftar Misi</a></p>' . "\n" . $needle,$p);
   return $s;
 case 'shop.php':
   $new=<<<'PHP_BLOCK'
// BS_STAGE6B_SHOP_MYSQL: saldo dan inventaris berasal dari bs_students.
require_once __DIR__.'/config/shop_inventory.php';
$studentId=bs_shop_student_id();
$pesanSukses='';$pesanError='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!bs_shop_valid_csrf($_POST['csrf_token']??null)){
        http_response_code(403);exit('Sesi keamanan tidak valid. Muat ulang toko.');
    }
    try{
        $id=is_string($_POST['item_id']??null)?trim($_POST['item_id']):'';
        bs_shop_buy($studentId,$id);
        $_SESSION['bs_shop_flash']=['ok'=>true,'msg'=>'🎉 Item berhasil dibeli dan disimpan di MySQL!'];
    }catch(DomainException $e){$_SESSION['bs_shop_flash']=['ok'=>false,'msg'=>$e->getMessage()];}
    catch(Throwable $e){error_log('Shop 6B: '.$e->getMessage());$_SESSION['bs_shop_flash']=['ok'=>false,'msg'=>'Pembelian gagal. Cek database dan log Laragon.'];}
    header('Location: shop.php',true,303);exit;
}
$flash=$_SESSION['bs_shop_flash']??null;unset($_SESSION['bs_shop_flash']);
if($flash){if($flash['ok'])$pesanSukses=$flash['msg'];else $pesanError=$flash['msg'];}
try{$state=bs_shop_state($studentId);}catch(Throwable $e){error_log('Shop state 6B: '.$e->getMessage());http_response_code(503);exit('Data toko tidak dapat dibaca. Impor SQL Tahap 6B.');}
$koinSekarang=$state['coins'];$koleksi=$state['collection'];$jumlahKoleksi=count($koleksi);
// Koleksi server tidak boleh diganti oleh localStorage/session demo.
PHP_BLOCK;
   $s=bs6b_between($s,'// INISIALISASI SESSION','// Helper: cek item sudah dimiliki',$new."\n",$p);
   return bs6b_once($s,'<input type="hidden" name="beli_item" value="1">', '<input type="hidden" name="beli_item" value="1">' . "\n" . '                                        <input type="hidden" name="csrf_token" value="<?= e(bs_shop_csrf()) ?>">',$p);
 case 'profile.php':
   $new=<<<'PHP_BLOCK'
// BS_STAGE6B_PROFILE_INVENTORY: baca inventaris MySQL, bukan session/localStorage.
require_once __DIR__.'/config/shop_inventory.php';
$studentId=bs_shop_student_id();
$pesanSukses='';$pesanError='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['aksi'])){
    if(!bs_shop_valid_csrf($_POST['csrf_token']??null)){http_response_code(403);exit('Token keamanan tidak valid.');}
    try {
        $id=is_string($_POST['item_id']??null)?trim($_POST['item_id']):'';
        $action=(string)$_POST['aksi'];
        if($action==='pakai')bs_shop_set_equipped($studentId,$id,true);
        elseif($action==='lepas')bs_shop_set_equipped($studentId,$id,false);
        else throw new DomainException('Aksi tidak dikenal.');
        $_SESSION['bs_profile_flash']=['ok'=>true,'msg'=>'Perlengkapan berhasil disimpan!'];
    }catch(DomainException $e){$_SESSION['bs_profile_flash']=['ok'=>false,'msg'=>$e->getMessage()];}
    catch(Throwable $e){error_log('Profile 6B: '.$e->getMessage());$_SESSION['bs_profile_flash']=['ok'=>false,'msg'=>'Perubahan gagal. Coba lagi.'];}
    header('Location: profile.php',true,303);exit;
}
$flash=$_SESSION['bs_profile_flash']??null;unset($_SESSION['bs_profile_flash']);
if($flash){if($flash['ok'])$pesanSukses=$flash['msg'];else $pesanError=$flash['msg'];}
try{$state=bs_shop_state($studentId);}catch(Throwable $e){error_log('Profile state 6B: '.$e->getMessage());http_response_code(503);exit('Inventaris tidak tersedia.');}
$koinSekarang=$state['coins'];$koleksi=$state['collection'];
$topiAktif=$state['topi'];$kacamataAktif=$state['kacamata'];
// Agar tampilan profil dan hero menggunakan item yang aktif dari DB.
PHP_BLOCK;
   $s=bs6b_between($s,'// INISIALISASI SESSION','// Helper: cek item sedang dipakai',$new."\n",$p);
   $s=bs6b_once($s,'<input type="hidden" name="aksi" value="pakai">','<input type="hidden" name="aksi" value="pakai">' . "\n" . '<input type="hidden" name="csrf_token" value="<?= e(bs_shop_csrf()) ?>">',$p);
   $s=bs6b_once($s,'<input type="hidden" name="aksi" value="lepas">','<input type="hidden" name="aksi" value="lepas">' . "\n" . '<input type="hidden" name="csrf_token" value="<?= e(bs_shop_csrf()) ?>">',$p);
   $s=str_replace('Penjelajah Galaksi Level 1','Penjelajah Galaksi Level <?= (int)($user[\'level\']??1) ?>',$s);
   return $s;
 case 'assets/js/app.js':
   // Fitur belanja lama via localStorage dibatasi ke link toko. Handler pembelian PHP tetap di shop.php.
   $s=bs6b_once($s, '        renderShopDashboard();', '        // BS_STAGE6B_SHOP_LINK: jangan buat pembelian lokal di dashboard.\n        renderShopDashboard6B();',$p);
   $needle='    // ============ PANGGIL INIT DI AKHIR ==========';
   $extra=<<<'JS'
    function renderShopDashboard6B() {
        const grid=document.getElementById('shop-grid');
        if(!grid)return;
        grid.innerHTML='<a href="shop.php" class="block col-span-full text-center rounded-2xl bg-violet-500 hover:bg-violet-600 px-5 py-5 text-white font-black">🛍️ Buka Toko Bintang — transaksi aman MySQL →</a>';
    }

JS;
   $s=bs6b_once($s,$needle,$extra.$needle,$p);
   $s=bs6b_once($s, "if (tokoBtn) tokoBtn.addEventListener('click', bukaToko);", "if (tokoBtn) tokoBtn.addEventListener('click', function(){ window.location.href='shop.php'; });",$p);
   return $s;
 default:throw new RuntimeException('Patch file tidak dikenali: '.$p);
 }
}
try{
    $targets=['config/question_repository.php','mainkan-misi.php','planet-detail.php','tutor/mission-list.php','tutor/questions.php','shop.php','profile.php','assets/js/app.js'];
    $addFiles=['config/shop_inventory.php','config/publication_repository.php','tutor/planet-publication.php'];
    $old=[];$new=[];
    foreach($targets as $p){$dest=$root.'/'.$p;if(!is_file($dest))throw new RuntimeException("File {$p} tidak ada. Periksa Tahap 4–6A.");
      $s=file_get_contents($dest);if($s===false)throw new RuntimeException("Tidak bisa membaca {$p}");
      $transformed=bs6b_patch($p,str_replace("\r\n","\n",$s),$root);
      $old[$p]=$s;$new[$p]=str_contains($s,"\r\n")?str_replace("\n","\r\n",$transformed):$transformed;
    }
    foreach($addFiles as $p){$src=($p==='tutor/planet-publication.php')?$root.'/tools/stage6b_assets/templates/planet-publication.php.txt':$root.'/tools/stage6b_assets/'.$p;
      if(!is_file($src))throw new RuntimeException("Asset tidak tersedia: {$src}. Salin seluruh folder tools!");
      $s=file_get_contents($src);$dest=$root.'/'.$p;$old[$p]=is_file($dest)?file_get_contents($dest):null;
      // Publication handler Tahap 4 memang harus diupgrade; file konfigurasi baru tidak ditimpa jika sudah berbeda.
      if($p!=='tutor/planet-publication.php' && $old[$p]!==null && $old[$p]!==$s)throw new RuntimeException("{$p} telah ada dan berbeda; dibatalkan.");
      $new[$p]=$s;
    }
    $changes=array_keys(array_filter($new,static fn($value,$path)=>$value!==$old[$path],ARRAY_FILTER_USE_BOTH));
    if(!$changes){echo "Tahap 6B sudah terpasang.\n";exit;}
    echo "Rencana patch:\n - ".implode("\n - ",$changes)."\n";
    if($check){echo "--check BERHASIL. Belum ada file aplikasi diubah.\n";exit;}
    $home=getenv('USERPROFILE')?:getenv('HOME')?:sys_get_temp_dir();
    $backup=rtrim($home,'/\\').'/BRIGHT_SPACE_stage6b_backups/'.basename($root).'_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
    if(!mkdir($backup,0700,true)&&!is_dir($backup))throw new RuntimeException('Folder backup gagal dibuat.');
    foreach($changes as $p){if($old[$p]===null)continue;$dest=$backup.'/'.$p;if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0700,true))throw new RuntimeException('Backup folder gagal.');
      if(file_put_contents($dest,$old[$p],LOCK_EX)!==strlen($old[$p]))throw new RuntimeException("Backup {$p} gagal.");}
    $written=[];
    try{
      foreach($changes as $p){$dest=$root.'/'.$p;if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0755,true))throw new RuntimeException('Folder tujuan tidak bisa dibuat.');
        if($old[$p]!==null && file_get_contents($dest)!==$old[$p])throw new RuntimeException("{$p} berubah selama pemasangan.");
        if(file_put_contents($dest,$new[$p],LOCK_EX)!==strlen($new[$p]))throw new RuntimeException("Gagal menulis {$p}");$written[]=$p;
      }
    }catch(Throwable $e){foreach(array_reverse($written) as $p){if($old[$p]===null)@unlink($root.'/'.$p);else @file_put_contents($root.'/'.$p,$old[$p],LOCK_EX);}throw $e;}
    echo "Tahap 6B selesai. Backup: {$backup}\nImpor sql/04_toko_dan_snapshot.sql lalu php tools/init_stage6b.php\n";
}catch(Throwable $e){fwrite(STDERR,'GAGAL: '.$e->getMessage()."\nTidak memaksakan perubahan.\n");exit(1);}
