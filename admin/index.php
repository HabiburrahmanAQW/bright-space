<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
$admin=bs_admin_require_login();
$stats=['tutors'=>0,'tutors_active'=>0,'students'=>0,'students_active'=>0];
$recentTutors=[];$recentStudents=[];$databaseError='';
try {
    $pdo=db();
    if (!$pdo) throw new RuntimeException('Database tidak terhubung.');
    $s=$pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(is_active=1),0) AS active FROM bs_tutors WHERE role='tutor'");
    $r=$s->fetch(PDO::FETCH_ASSOC);$stats['tutors']=(int)$r['total'];$stats['tutors_active']=(int)$r['active'];
    $s=$pdo->query('SELECT COUNT(*) AS total, COALESCE(SUM(is_active=1),0) AS active FROM bs_students');
    $r=$s->fetch(PDO::FETCH_ASSOC);$stats['students']=(int)$r['total'];$stats['students_active']=(int)$r['active'];
    $recentTutors=$pdo->query("SELECT username,display_name,is_active FROM bs_tutors WHERE role='tutor' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $recentStudents=$pdo->query("SELECT username,display_name,is_active,level FROM bs_students ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) {
    error_log('Admin dashboard: '.$e->getMessage());
    $databaseError='Data akun tidak dapat dimuat. Periksa koneksi MySQL dan tabel siswa/tutor.';
}
?>
<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Admin — BRIGHT SPACE</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../tutor/assets/editor.css">
<link rel="stylesheet" href="../tutor/assets/tutor-layout.css">
</head>
<body class="bg-sky-50 text-indigo-950 min-h-screen antialiased" style="font-family:Nunito,ui-rounded,system-ui,sans-serif">
<?php bs_admin_layout_start($admin,'dashboard','Dashboard Admin'); ?>
<main id="tutor-main" class="max-w-6xl mx-auto px-5 py-8 pb-20">
    <div class="flex flex-wrap justify-between items-end gap-4">
       <div><p class="text-xs text-violet-600 uppercase tracking-wider font-black">🏫 Administrasi Sekolah</p>
           <h1 class="text-3xl font-black mt-1">Halo, <?= tutor_e($admin['display_name']) ?>! 👋</h1>
           <p class="mt-2 text-slate-600 font-bold">Kelola akun tutor dan siswa BRIGHT SPACE dari satu tempat.</p>
       </div>
       <div class="flex flex-wrap gap-3">
           <a href="tutors.php" class="rounded-2xl bg-violet-600 text-white px-5 py-3 font-black hover:bg-violet-700">＋ Kelola Tutor</a>
           <a href="students.php" class="rounded-2xl bg-white border-2 border-violet-200 text-violet-700 px-5 py-3 font-black hover:bg-violet-50">＋ Kelola Siswa</a>
       </div>
    </div>
    <?php if($databaseError): ?><p role="alert" class="notify notify-error mt-5"><?= tutor_e($databaseError) ?></p><?php endif; ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-7">
        <div class="editor-panel !p-5"><p class="text-sm font-bold text-slate-600">Total Tutor</p><p class="text-3xl font-black mt-2"><?= $stats['tutors'] ?></p></div>
        <div class="editor-panel !p-5"><p class="text-sm font-bold text-slate-600">Tutor Aktif</p><p class="text-3xl font-black mt-2"><?= $stats['tutors_active'] ?></p></div>
        <div class="editor-panel !p-5"><p class="text-sm font-bold text-slate-600">Total Siswa</p><p class="text-3xl font-black mt-2"><?= $stats['students'] ?></p></div>
        <div class="editor-panel !p-5"><p class="text-sm font-bold text-slate-600">Siswa Aktif</p><p class="text-3xl font-black mt-2"><?= $stats['students_active'] ?></p></div>
    </div>
    <div class="grid lg:grid-cols-2 gap-5 mt-7">
        <section class="editor-panel">
            <div class="flex justify-between items-center gap-3"><h2 class="font-black text-xl">Tutor Terbaru</h2><a class="text-violet-700 text-sm font-black" href="tutors.php">Lihat semua →</a></div>
            <?php if(!$recentTutors): ?><p class="text-sm font-bold text-slate-500 mt-4">Belum ada akun tutor.</p><?php endif; ?>
            <div class="mt-4 space-y-3">
                <?php foreach($recentTutors as $one): ?>
                <div class="flex justify-between items-center gap-3 border border-violet-100 rounded-xl px-4 py-3">
                    <div class="min-w-0"><p class="font-black break-words"><?= tutor_e($one['display_name']) ?></p>
                         <p class="text-xs text-slate-500 font-bold"><?= tutor_e($one['username']) ?></p></div>
                    <span class="text-xs font-black <?= (int)$one['is_active']===1?'text-emerald-700':'text-amber-700' ?>"><?= (int)$one['is_active']===1?'Aktif':'Nonaktif' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="editor-panel">
            <div class="flex justify-between items-center gap-3"><h2 class="font-black text-xl">Siswa Terbaru</h2><a class="text-violet-700 text-sm font-black" href="students.php">Lihat semua →</a></div>
            <?php if(!$recentStudents): ?><p class="text-sm font-bold text-slate-500 mt-4">Belum ada akun siswa.</p><?php endif; ?>
            <div class="mt-4 space-y-3">
                <?php foreach($recentStudents as $one): ?>
                <div class="flex justify-between items-center gap-3 border border-violet-100 rounded-xl px-4 py-3">
                    <div class="min-w-0"><p class="font-black break-words"><?= tutor_e($one['display_name']) ?></p>
                         <p class="text-xs text-slate-500 font-bold"><?= tutor_e($one['username']) ?></p></div>
                    <span class="text-xs font-black text-violet-700">Lv. <?= (int)$one['level'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <p class="text-xs font-bold text-slate-500 mt-6">Penonaktifan akun menjaga riwayat XP, koin, dan progres misi. Penghapusan permanen tidak disediakan.</p>
</main>
<?php bs_admin_layout_end(); ?>
</body></html>
