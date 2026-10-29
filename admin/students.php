<?php
require_once __DIR__.'/_auth.php';
require_once __DIR__.'/_layout.php';
require_once __DIR__.'/_accounts.php';
$admin=bs_admin_require_login();
$errors=bs_admin_account_action('student');
$flash=bs_admin_account_flash('student');
$list=['rows'=>[],'total'=>0,'page'=>1,'pages'=>1,'q'=>''];
try{$list=bs_admin_account_list('student');}
catch(Throwable $e){error_log('Admin list: '.$e->getMessage());$errors[]='Daftar akun gagal dimuat. Periksa database.';}
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola Akun Siswa — BRIGHT SPACE</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../tutor/assets/editor.css">
<link rel="stylesheet" href="../tutor/assets/tutor-layout.css">
</head>
<body class="bg-sky-50 text-indigo-950 min-h-screen antialiased" style="font-family:Nunito,ui-rounded,system-ui,sans-serif">
<?php bs_admin_layout_start($admin,'students','Kelola Akun Siswa'); ?>
<main id="tutor-main" class="max-w-6xl mx-auto px-5 py-8 pb-20 space-y-6">
    <div>
      <p class="uppercase tracking-wider text-violet-600 text-xs font-black">Administrator · Akun Sekolah</p>
      <h1 class="text-3xl font-black mt-1">Kelola Akun Siswa</h1>
      <p class="font-bold text-slate-600 mt-2">Buat, lihat, edit, nonaktifkan, aktifkan kembali, dan reset password Siswa. Progres belajar tidak akan dihapus.</p>
    </div>
    <?php if($errors): ?><div class="notify notify-error" role="alert">
        <?php foreach($errors as $err): ?><p><?= tutor_e($err) ?></p><?php endforeach; ?>
    </div><?php endif; ?>
    <?php if($flash): ?><div class="editor-panel border-emerald-200 bg-emerald-50" role="status">
       <p class="text-lg font-black text-emerald-900"><?= tutor_e($flash['message']) ?></p>
       <?php if(is_array($flash['secret']??null)): ?>
         <p class="mt-2 text-sm font-bold">Username: <code class="select-all"><?= tutor_e($flash['secret']['username']) ?></code></p>
         <p class="mt-2 text-sm font-bold">Password awal baru (ditampilkan satu kali):
            <code class="select-all break-all"><?= tutor_e($flash['secret']['password']) ?></code></p>
         <p class="text-xs font-bold text-emerald-800 mt-2">Salin dan sampaikan secara privat kepada pemilik akun. Jangan kirim di grup umum.</p>
       <?php endif; ?>
    </div><?php endif; ?>
    <section class="editor-panel max-w-2xl">
        <h2 class="font-black text-xl">＋ Tambah Akun Siswa</h2>
        <form action="students.php" method="post" autocomplete="off" class="space-y-4 mt-4">
            <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
            <input type="hidden" name="action" value="create">
            <div><label class="field-label" for="new-name">Nama Tampilan</label>
              <input class="field-input" id="new-name" name="display_name" minlength="2" maxlength="80" required placeholder="Nama Siswa"></div>
            <div><label class="field-label" for="new-username">Username</label>
              <input class="field-input" id="new-username" name="username" minlength="3" maxlength="30" required
                 pattern="[a-zA-Z][a-zA-Z0-9_]{2,29}" placeholder="kapten_bintang"></div>
            <p class="field-help">Password awal dibuat otomatis dan hanya ditampilkan sekali. Gunakan username yang tidak dipakai akun lain.</p>
            <button class="rounded-2xl bg-violet-600 text-white hover:bg-violet-700 px-6 py-3 font-black" type="submit">＋ Buat Akun Siswa</button>
        </form>
    </section>
    <section class="editor-panel">
        <div class="flex flex-wrap gap-3 items-center justify-between">
            <h2 class="font-black text-xl">Daftar Siswa <span class="text-sm text-slate-500">(<?= (int)$list['total'] ?>)</span></h2>
            <form action="students.php" method="get" class="flex flex-wrap gap-2">
                <input class="field-input !w-auto" name="q" maxlength="60" value="<?= tutor_e($list['q']) ?>"
                       aria-label="Cari akun Siswa" placeholder="Cari username/nama">
                <button type="submit" class="rounded-xl px-4 py-2 bg-violet-100 text-violet-800 font-black">Cari</button>
            </form>
        </div>
        <?php if(!$list['rows']): ?><p class="mt-4 text-sm text-slate-500 font-bold">Akun tidak ditemukan.</p><?php endif; ?>
        <div class="space-y-4 mt-5">
        <?php foreach($list['rows'] as $a): ?>
            <article class="rounded-2xl border-2 border-violet-100 bg-violet-50/50 p-4">
                <div class="flex flex-wrap justify-between items-start gap-2">
                    <div class="min-w-0">
                        <p class="font-black text-lg break-words"><?= tutor_e($a['display_name']) ?></p>
                        <p class="text-xs font-bold text-slate-600 break-all">@<?= tutor_e($a['username']) ?> · ID <?= (int)$a['id'] ?> · Level <?= (int)$a['level'] ?> · XP <?= (int)$a['total_xp'] ?></p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-black <?= (int)$a['is_active']===1?'bg-emerald-100 text-emerald-800':'bg-amber-100 text-amber-900' ?>">
                        <?= (int)$a['is_active']===1?'Aktif':'Nonaktif' ?>
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 mt-4">
                    <details class="w-full bg-white rounded-xl border border-violet-200 p-3">
                        <summary class="cursor-pointer text-violet-700 font-black">✏️ Edit Nama dan Username</summary>
                        <form action="students.php" method="post" class="grid sm:grid-cols-2 gap-3 mt-3">
                            <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="account_id" value="<?= (int)$a['id'] ?>">
                            <div><label class="field-label">Nama Tampilan</label>
                                <input class="field-input" name="display_name" minlength="2" maxlength="80" required
                                       value="<?= tutor_e($a['display_name']) ?>"></div>
                            <div><label class="field-label">Username</label>
                                <input class="field-input" name="username" minlength="3" maxlength="30" required
                                       value="<?= tutor_e($a['username']) ?>"></div>
                            <div class="sm:col-span-2"><button class="px-5 py-2 rounded-xl bg-violet-600 text-white font-black" type="submit">Simpan Perubahan</button></div>
                        </form>
                    </details>
                    <form action="students.php" method="post" onsubmit="return confirm('Buat ulang password? Password lama tidak berlaku lagi.');">
                        <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
                        <input type="hidden" name="account_id" value="<?= (int)$a['id'] ?>">
                        <button class="px-4 py-2 rounded-xl bg-sky-100 text-sky-800 font-black text-sm" type="submit"
                                name="action" value="reset_password">🔑 Reset Password</button>
                    </form>
                    <form action="students.php" method="post" onsubmit="return confirm('Ubah status akun ini?');">
                        <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
                        <input type="hidden" name="account_id" value="<?= (int)$a['id'] ?>">
                        <?php if((int)$a['is_active']===1): ?>
                            <button class="px-4 py-2 rounded-xl bg-rose-100 text-rose-800 font-black text-sm"
                                    type="submit" name="action" value="disable">🚫 Nonaktifkan</button>
                        <?php else: ?>
                            <button class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-800 font-black text-sm"
                                    type="submit" name="action" value="enable">✓ Aktifkan</button>
                        <?php endif; ?>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
        <?php if($list['pages']>1): ?>
        <nav class="flex items-center gap-4 mt-6" aria-label="Halaman daftar akun">
            <?php if($list['page']>1): ?><a href="?page=<?= $list['page']-1 ?>&amp;q=<?= rawurlencode($list['q']) ?>" class="font-black text-violet-700">← Sebelumnya</a><?php endif; ?>
            <span class="text-sm font-bold text-slate-600">Halaman <?= $list['page'] ?> dari <?= $list['pages'] ?></span>
            <?php if($list['page']<$list['pages']): ?><a href="?page=<?= $list['page']+1 ?>&amp;q=<?= rawurlencode($list['q']) ?>" class="font-black text-violet-700">Berikutnya →</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </section>
</main>
<?php bs_admin_layout_end(); ?>
</body></html>
