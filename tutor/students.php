<?php
/** Siswa hanya boleh dibuat dan dilihat oleh tutor/admin yang sudah terautentikasi. */
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/_layout.php';
require_once __DIR__.'/../config/registration.php';
$tutor=tutor_require_login();
$errors=[];
$old=['username'=>'','display_name'=>''];
$created=$_SESSION['bs_new_student_once']??null;
unset($_SESSION['bs_new_student_once']);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if(!tutor_valid_csrf($_POST['csrf_token']??null)){
        http_response_code(403);$errors[]='Token keamanan tidak valid.';
    }else{
        // Buat kata sandi kuat secara acak, bukan diserahkan pada browser.
        $initialPassword=rtrim(strtr(base64_encode(random_bytes(18)),'+/','-_'),'=');
        $form=['username'=>$_POST['username']??'','display_name'=>$_POST['display_name']??'',
               'password'=>$initialPassword,'password_confirm'=>$initialPassword];
        [$data,$errors]=bs_registration_validate($form);
        $old=['username'=>$data['username'],'display_name'=>$data['display_name']];
        if(!$errors){
            $pdo=null;
            try {
                $pdo=db();
                if(!$pdo)throw new RuntimeException('Koneksi MySQL belum siap.');
                $pdo->beginTransaction();
                // Jangan gunakan username tutor agar halaman login tanpa pemilihan role tidak ambigu.
                $q=$pdo->prepare('SELECT id FROM bs_tutors WHERE username=? LIMIT 1');
                $q->execute([$data['username']]);
                if($q->fetchColumn())throw new DomainException('Username sudah digunakan. Pilih yang lain.');
                $q=$pdo->prepare('INSERT INTO bs_students(username,display_name,password_hash) VALUES(?,?,?)');
                $q->execute([$data['username'],$data['display_name'],password_hash($initialPassword,PASSWORD_DEFAULT)]);
                $studentId=(int)$pdo->lastInsertId();
                $q=$pdo->prepare('INSERT INTO bs_tutor_students(student_id,tutor_id) VALUES(?,?)');
                $q->execute([$studentId,(int)$tutor['id']]);
                $pdo->commit();
                // Diperlihatkan sekali setelah redirect, tidak disimpan sebagai plaintext di DB.
                $_SESSION['bs_new_student_once']=['username'=>$data['username'],'password'=>$initialPassword];
                header('Location: students.php',true,303);exit;
            }catch(DomainException $e){
                if($pdo&&$pdo->inTransaction())$pdo->rollBack();
                $errors[]=$e->getMessage();
            }catch(PDOException $e){
                if($pdo&&$pdo->inTransaction())$pdo->rollBack();
                if((string)$e->getCode()==='23000')$errors[]='Username sudah digunakan. Pilih username lain.';
                else{error_log('Tutor create student: '.$e->getMessage());$errors[]='Database belum siap. Pastikan migrasi SQL 05 sudah diimpor.';}
            }catch(Throwable $e){
                if($pdo&&$pdo->inTransaction())$pdo->rollBack();
                error_log('Tutor create student: '.$e->getMessage());
                $errors[]='Pembuatan siswa gagal. Periksa database/log Laragon.';
            }
        }
    }
}
$students=[];
try{
    $stmt=db()->prepare('SELECT s.id,s.username,s.display_name,s.level,s.total_xp,s.is_active
                         FROM bs_tutor_students ts JOIN bs_students s ON s.id=ts.student_id
                         WHERE ts.tutor_id=? ORDER BY s.id DESC');
    $stmt->execute([(int)$tutor['id']]);$students=$stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){error_log('List students: '.$e->getMessage());$errors[]='Daftar siswa belum tersedia. Impor migrasi SQL 05.';}
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola Siswa — BRIGHT SPACE</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/editor.css"><link rel="stylesheet" href="assets/tutor-layout.css">
</head><body class="bg-sky-50 text-indigo-950 min-h-screen antialiased" style="font-family:Nunito,ui-rounded,system-ui,sans-serif">
<?php tutor_layout_start($tutor,'students','Kelola Siswa'); ?>
<main id="tutor-main" class="max-w-6xl mx-auto px-5 py-8 pb-20 space-y-6">
    <div><p class="text-xs uppercase tracking-widest text-violet-700 font-black">Tutor Studio · Akun Siswa</p>
        <h1 class="text-3xl font-black mt-1">Tambah Siswa 🚀</h1>
        <p class="text-sm font-bold text-slate-500 mt-2">Buat akun Kapten untuk siswa yang kamu dampingi. Password awal dibuat otomatis oleh sistem.</p></div>
    <?php if (is_array($created)): ?>
        <section role="status" class="editor-panel border-emerald-300 bg-emerald-50">
            <h2 class="text-xl font-black text-emerald-800">Akun siswa berhasil dibuat!</h2>
            <p class="mt-2 text-sm font-bold">Username: <code class="select-all"><?= tutor_e($created['username']) ?></code></p>
            <p class="mt-2 text-sm font-bold">Password awal (hanya muncul sekali):
                <code class="select-all break-all"><?= tutor_e($created['password']) ?></code></p>
            <p class="mt-2 text-sm font-bold text-emerald-900">Salin dan berikan kepada siswa/walinya secara privat. Password tidak dapat dilihat lagi setelah halaman ini dimuat ulang.</p>
        </section>
    <?php endif; ?>
    <?php if ($errors): ?><div class="notify notify-error" role="alert"><?php foreach($errors as $e): ?><p><?= tutor_e($e) ?></p><?php endforeach; ?></div><?php endif; ?>
    <section class="editor-panel max-w-2xl">
        <h2 class="text-xl font-black">Form Siswa Baru</h2>
        <form method="post" action="students.php" class="space-y-4 mt-5">
            <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
            <div><label class="field-label" for="display_name">Nama Tampilan Siswa</label>
                <input class="field-input" id="display_name" name="display_name" required minlength="2" maxlength="80"
                  value="<?= tutor_e($old['display_name']) ?>" placeholder="Contoh: Kapten Bintang"></div>
            <div><label class="field-label" for="username">Username Siswa</label>
                <input class="field-input" id="username" name="username" required minlength="3" maxlength="30"
                  pattern="[a-zA-Z][a-zA-Z0-9_]{2,29}" value="<?= tutor_e($old['username']) ?>"
                  placeholder="Contoh: kapten_bintang"></div>
            <p class="field-help">Gunakan nama tampilan atau nama panggilan. Hindari data pribadi siswa.</p>
            <button type="submit" class="rounded-2xl bg-violet-600 text-white px-6 py-3 font-black hover:bg-violet-700">＋ Buat Akun Siswa</button>
        </form>
    </section>
    <section class="editor-panel">
        <h2 class="text-xl font-black">Siswa yang Dikelola <span class="text-slate-500 text-sm">(<?= count($students) ?>)</span></h2>
        <?php if(!$students): ?><p class="text-sm text-slate-500 font-bold mt-3">Belum ada siswa yang ditambahkan pada akun tutor ini.</p><?php endif; ?>
        <div class="overflow-x-auto mt-4"><table class="min-w-full text-sm text-left">
            <thead class="text-violet-700 border-b-2 border-violet-200"><tr>
                <th class="px-3 py-3">Nama</th><th class="px-3 py-3">Username</th>
                <th class="px-3 py-3">Level</th><th class="px-3 py-3">Total XP</th><th class="px-3 py-3">Status</th>
            </tr></thead><tbody>
            <?php foreach($students as $s): ?><tr class="border-b border-violet-100">
                <td class="px-3 py-3 font-bold"><?= tutor_e($s['display_name']) ?></td>
                <td class="px-3 py-3"><?= tutor_e($s['username']) ?></td>
                <td class="px-3 py-3"><?= (int)$s['level'] ?></td>
                <td class="px-3 py-3"><?= (int)$s['total_xp'] ?></td>
                <td class="px-3 py-3"><?= (int)$s['is_active']===1?'Aktif':'Nonaktif' ?></td>
            </tr><?php endforeach; ?>
            </tbody></table></div>
    </section>
</main>
<?php tutor_layout_end(); ?>
</body></html>
