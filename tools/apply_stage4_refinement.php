<?php
/** BRIGHT SPACE: refinement Tahap 4. Hanya CLI, --check tidak menulis. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Jalankan hanya dari Terminal Laragon.'); }
$root = dirname(__DIR__);
$check = in_array('--check', $argv, true);
$templates = __DIR__ . '/stage4_refinement_assets';

function fix_once(string $s, string $old, string $new, string $path): string {
    $n = substr_count($s, $old);
    if ($n !== 1) throw new RuntimeException("{$path}: penanda tidak cocok ({$n}x). Tidak dipaksakan. Kirim pesan ini untuk disesuaikan.");
    return str_replace($old, $new, $s);
}
function fix_transform(string $file, string $body): string {
    if ($file === 'tutor/_planet-editor.php') {
        if (str_contains($body, "['draft', 'draft_next']")) return $body;
        $body=fix_once($body, "['draft', 'published']", "['draft', 'draft_next']", $file);
        $body=fix_once($body, "    if (\$action === 'published' && count(\$missions) === 0) {\n        \$errors[] = 'Tambahkan minimal satu misi sebelum dipublikasikan.';\n    }\n", "", $file);
        return fix_once($body, "'publication_status' => \$action, 'missions' => \$normalized,", "'publication_status' => 'draft', 'missions' => \$normalized,", $file);
    }
    if ($file === 'tutor/planet-save.php') {
        if (str_contains($body, '$requestedAction = planet_editor_string(')) return $body;
        $body=fix_once($body, "[\$input, \$errors] = planet_editor_validate(\$_POST, \$isEdit);", "\$requestedAction = planet_editor_string(\$_POST, 'save_action');\n[\$input, \$errors] = planet_editor_validate(\$_POST, \$isEdit);", $file);
        $start = strpos($body, "    \$_SESSION['planet_saved_message'] =");
        $end = strpos($body, "    unset(\$_SESSION['planet_editor_flash']);", $start===false?0:$start);
        if ($start===false || $end===false || $end<=$start) throw new RuntimeException("{$file}: pesan keberhasilan tidak ditemukan.");
        $body=substr($body,0,$start)."    \$_SESSION['planet_saved_message'] = 'Planet dan misinya disimpan sebagai Draft. Kelola soal lalu publikasikan dari daftar misi.';\n".substr($body,$end);
        $body=fix_once($body, "header('Location: planet-form.php?id=' . rawurlencode(\$planetId), true, 303);", "header('Location: ' . (\$requestedAction === 'draft_next' ? 'mission-list.php?planet=' : 'planet-form.php?id=') . rawurlencode(\$planetId), true, 303);", $file);
        return $body;
    }
    if ($file === 'tutor/planet-form.php') {
        if (str_contains($body, 'value="draft_next"')) return $body;
        $body = fix_once($body,
            '<button type="submit" name="save_action" value="published" class="w-full rounded-2xl bg-violet-600 hover:bg-violet-700 text-white py-3 font-black shadow-sm">🚀 Publikasikan Planet</button>',
            '<button type="submit" name="save_action" value="draft_next" class="w-full rounded-2xl bg-violet-600 hover:bg-violet-700 text-white py-3 font-black shadow-sm">📝 Simpan &amp; Kelola Soal →</button>', $file);
        $body=fix_once($body,
            'Draft belum terlihat oleh siswa. Publikasikan setelah konfigurasi misinya siap.',
            'Planet hanya disimpan sebagai Draft dari halaman ini. Tambahkan soal, lalu publikasikan melalui halaman Kelola Soal.', $file);
        // Tahap 3: deskripsi sudah diubah lewat skrip apply_stage3.
        $needle = 'Planet published akan tampil di Peta Misi siswa sesuai level. Misi baru ditampilkan sebagai belum siap dimainkan sampai editor soal tersedia.';
        $alternative='Publikasi saat ini hanya menyimpan status di MySQL. Menampilkan dan memainkan misi di halaman siswa dilakukan pada Tahap 3; editor soal juga belum tersedia.';
        $desc='Misi harus tersimpan sebelum menambah soal. Klik Simpan &amp; Kelola Soal untuk lanjut. Publikasikan setelah sedikitnya satu soal dibuat.';
        if (substr_count($body,$needle)===1) $body=str_replace($needle,$desc,$body);
        elseif(substr_count($body,$alternative)===1) $body=str_replace($alternative,$desc,$body);
        else throw new RuntimeException("{$file}: keterangan lama tidak ditemukan. File lokal kemungkinan berbeda.");
        return $body;
    }
    if ($file === 'planet-detail.php') {
        if (str_contains($body, 'student-mission-modal.js')) return $body;
        if (!str_contains($body, 'bmq_ready(') || !str_contains($body, 'mainkan-misi.php?mission=')) {
            throw new RuntimeException("{$file}: integrasi Tahap 4 belum cocok.");
        }
        return fix_once($body,'<script src="assets/js/student-missions.js" defer></script>', '<script src="assets/js/student-missions.js" defer></script>' . "\n" . '<script src="assets/js/student-mission-modal.js" defer></script>', $file);
    }
    throw new LogicException('File tidak didukung: ' . $file);
}
try {
    $patches = ['tutor/_planet-editor.php','tutor/planet-save.php','tutor/planet-form.php','planet-detail.php'];
    $replace = ['tutor/mission-list.php','mainkan-misi.php'];
    $create = ['tutor/planet-publication.php','assets/js/student-mission-modal.js'];
    $old=[];$new=[];
    foreach($patches as $file){
        $full=$root.'/'.$file;
        if(!is_file($full))throw new RuntimeException("File {$file} belum ada. Pastikan Tahap 1–4 sudah terpasang.");
        $raw=file_get_contents($full);
        if($raw===false)throw new RuntimeException("Gagal membaca {$file}.");
        $fixed=fix_transform($file,str_replace("\r\n","\n",$raw));
        $old[$file]=$raw;
        $new[$file]=str_contains($raw,"\r\n")?str_replace("\n","\r\n",$fixed):$fixed;
    }
    foreach(array_merge($replace,$create) as $file){
        $full=$root.'/'.$file;
        $src=$templates.'/'.$file.'.txt';
        if(!is_file($src))throw new RuntimeException("Template {$src} tidak ditemukan. Pastikan isi ZIP sudah tersalin lengkap.");
        $new[$file]=file_get_contents($src);
        $old[$file]=is_file($full)?file_get_contents($full):null;
        if($old[$file]!==null && $old[$file]!==$new[$file] && in_array($file,$replace,true)){
            $known=$templates.'/expected-sha256.json';
            $manifest=json_decode((string)file_get_contents($known), true);
            if(!is_array($manifest) || !in_array(($manifest[$file]??''), [hash('sha256',$old[$file]),hash('sha256',str_replace("\r\n","\n",$old[$file]))], true))
                throw new RuntimeException("{$file} sudah berbeda dari versi Tahap 4 standar. Tidak ditimpa demi keamanan; kirim file ini untuk dibuat patch khusus.");
        }
        if($old[$file]!==null && $old[$file]!==$new[$file] && in_array($file,$create,true))
            throw new RuntimeException("{$file} sudah ada dengan isi berbeda. Tidak ditimpa.");
    }
    $changed = array_values(array_filter(array_keys($new),static fn($k)=>$old[$k]!==$new[$k]));
    if(!$changed){echo "Penyempurnaan Tahap 4 sudah terpasang. Tidak ada yang diubah.\n";exit(0);}
    echo "File yang akan diubah/ditambah:\n - ".implode("\n - ",$changed)."\n";
    if($check){echo "--check BERHASIL. Belum ada perubahan yang ditulis.\n";exit(0);}
    $home=getenv('USERPROFILE')?:getenv('HOME')?:sys_get_temp_dir();
    $backup=rtrim($home,'/\\').'/BRIGHT_SPACE_stage4_refinement_backups/'.basename($root).'_'.date('Ymd_His');
    if(!is_dir($backup) && !mkdir($backup,0700,true))throw new RuntimeException('Gagal membuat direktori backup.');
    foreach($changed as $file){
        if($old[$file]===null)continue;
        $dst=$backup.'/'.$file;
        if(!is_dir(dirname($dst)) && !mkdir(dirname($dst),0700,true))throw new RuntimeException('Gagal membuat subfolder backup.');
        if(file_put_contents($dst,$old[$file])===false)throw new RuntimeException("Gagal membuat backup {$file}.");
    }
    $written=[];
    try{
        foreach($changed as $file){
            $dest=$root.'/'.$file;
            if(!is_dir(dirname($dest)) && !mkdir(dirname($dest),0755,true))throw new RuntimeException("Gagal membuat folder untuk {$file}.");
            if(file_put_contents($dest,$new[$file],LOCK_EX)===false)throw new RuntimeException("Gagal menulis {$file}.");
            $written[]=$file;
        }
    } catch(Throwable $e){
        foreach($written as $file){
            if($old[$file]===null)unlink($root.'/'.$file);
            else file_put_contents($root.'/'.$file,$old[$file],LOCK_EX);
        }
        throw $e;
    }
    echo "Penyempurnaan Tahap 4 BERHASIL.\nBackup: {$backup}\n";
} catch(Throwable $e){fwrite(STDERR,"GAGAL: ".$e->getMessage()."\nTidak ada perubahan paksa.\n");exit(1);}
