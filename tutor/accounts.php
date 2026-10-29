<?php
/** Administrator meninjau pendaftaran tutor. Akun pending tidak dapat login. */
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/_layout.php';
$tutor=tutor_require_login();
if(($tutor['role']??'')!=='admin'){http_response_code(403);exit('Hanya administrator dapat menyetujui tutor.');}
$errors=[];
$flash=(string)($_SESSION['bs_admin_tutor_flash']??'');
unset($_SESSION['bs_admin_tutor_flash']);
if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!tutor_valid_csrf($_POST['csrf_token']??null)){
        http_response_code(403);$errors[]='Token keamanan tidak valid.';
    }else{
        $id=filter_var($_POST['tutor_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $action=$_POST['action']??null;
        if(!$id||$action!=='approve'){
            http_response_code(400);$errors[]='Aksi persetujuan tidak valid.';
        }else{
            try{
                $stmt=db()->prepare("UPDATE bs_tutors SET is_active=1 WHERE id=? AND role='tutor' AND is_active=0");
                $stmt->execute([(int)$id]);
                if($stmt->rowCount()!==1)throw new DomainException('Akun tidak ditemukan atau sudah diproses.');
                $_SESSION['bs_admin_tutor_flash']='Akun tutor berhasil disetujui dan dapat login.';
                header('Location: accounts.php',true,303);exit;
            }catch(DomainException $e){$errors[]=$e->getMessage();}
            catch(Throwable $e){error_log('Approval tutor: '.$e->getMessage());$errors[]='Persetujuan gagal. Periksa database/log server.';}
        }
    }
}
$rows=[];
try{
    $rows=db()->query('SELECT id,username,display_name,role,is_active,created_at
                       FROM bs_tutors ORDER BY is_active ASC,created_at DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){error_log('Admin tutor list: '.$e->getMessage());$errors[]='Daftar tutor gagal dimuat.';}
$pendingCount=count(array_filter($rows,fn($x)=>(int)$x['is_active']===0));
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Persetujuan Tutor — BRIGHT SPACE</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/editor.css"><link rel="stylesheet" href="assets/tutor-layout.css">
</head><body class="bg-sky-50 text-indigo-950 min-h-screen" style="font-family:Nunito,ui-rounded,system-ui,sans-serif">
<?php tutor_layout_start($tutor,'accounts','Persetujuan Tutor'); ?>
<main id="tutor-main" class="max-w-6xl mx-auto px-5 py-8 pb-20 space-y-6">
    <div><p class="uppercase tracking-widest text-xs text-violet-600 font-black">Administrator · Verifikasi Pengajar</p>
        <h1 class="text-3xl font-black mt-1">Persetujuan Tutor 📚</h1>
        <p class="text-slate-500 font-bold mt-2">Pendaftaran tutor masuk sebagai Pending. Hanya akun yang sudah disetujui boleh mengelola siswa dan planet.</p></div>
    <?php if($flash): ?><p class="notify notify-success" role="status"><?= tutor_e($flash) ?></p><?php endif; ?>
    <?php if($errors): ?><div class="notify notify-error" role="alert"><?php foreach($errors as $e):?><p><?= tutor_e($e) ?></p><?php endforeach; ?></div><?php endif; ?>
    <section class="editor-panel">
        <h2 class="text-xl font-black">Menunggu Persetujuan (<?= $pendingCount ?>)</h2>
        <div class="overflow-x-auto mt-4"><table class="w-full text-left text-sm">
            <thead class="text-violet-700 border-b-2 border-violet-200"><tr>
              <th class="p-3">Nama Pengajar</th><th class="p-3">Username</th><th class="p-3">Role</th><th class="p-3">Status/Aksi</th>
            </tr></thead><tbody>
            <?php foreach($rows as $r): ?><tr class="border-b border-violet-100">
                <td class="p-3 font-bold"><?= tutor_e($r['display_name']) ?></td>
                <td class="p-3"><?= tutor_e($r['username']) ?></td>
                <td class="p-3"><?= tutor_e($r['role']) ?></td>
                <td class="p-3">
                <?php if((int)$r['is_active']===0 && $r['role']==='tutor'): ?>
                    <form method="post" action="accounts.php" onsubmit="return confirm('Setujui akun tutor ini?');">
                        <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
                        <input type="hidden" name="tutor_id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" name="action" value="approve" class="rounded-xl bg-emerald-600 text-white px-4 py-2 font-black hover:bg-emerald-700">✓ Setujui Tutor</button>
                    </form>
                <?php elseif((int)$r['is_active']===0): ?>Pending
                <?php else: ?><span class="text-emerald-700 font-black">Aktif</span><?php endif; ?>
                </td>
            </tr><?php endforeach; ?>
            </tbody></table></div>
    </section>
</main>
<?php tutor_layout_end(); ?>
</body></html>
