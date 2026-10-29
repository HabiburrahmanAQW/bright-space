<?php
require __DIR__ . '/_auth.php';
$tutor = tutor_require_teacher();
if (($tutor['role'] ?? '') === 'admin') {
    header('Location: ../admin/index.php', true, 303);
    exit;
}
$planets = [];
$databaseError = null;
try {
    $sql = "SELECT p.id, p.name, p.description, p.required_level, p.publication_status, p.updated_at,
                   COALESCE(m.total,0) AS mission_count, COALESCE(m.wajib,0) AS wajib_count,
                   COALESCE(m.side_count,0) AS side_count
            FROM bs_planets AS p
            LEFT JOIN (
                SELECT planet_id, COUNT(*) AS total,
                       SUM(CASE WHEN category = 'wajib' THEN 1 ELSE 0 END) AS wajib,
                       SUM(CASE WHEN category = 'side' THEN 1 ELSE 0 END) AS side_count
                FROM bs_missions GROUP BY planet_id
            ) AS m ON m.planet_id = p.id";
    $params = [];
    if ($tutor['role'] !== 'admin') {
        $sql .= ' WHERE p.created_by = ?';
        $params[] = (int) $tutor['id'];
    }
    $sql .= ' ORDER BY p.updated_at DESC, p.id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $planets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Tutor dashboard error: ' . $e->getMessage());
    $databaseError = 'Tidak dapat membaca planet. Pastikan MySQL aktif dan tabel bs_planets/bs_missions tersedia.';
}
$draftCount = count(array_filter($planets, fn($p) => $p['publication_status'] === 'draft'));
$publishedCount = count($planets) - $draftCount;
$missionCount = array_sum(array_map(fn($p) => (int) $p['mission_count'], $planets));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Tutor — BRIGHT SPACE</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{nunito:['Nunito','ui-rounded','sans-serif']}}}};</script>
    <link rel="stylesheet" href="assets/editor.css">
    <link rel="stylesheet" href="assets/tutor-layout.css">
</head>
<body class="bg-sky-50 text-indigo-950 font-nunito min-h-screen antialiased">
<?php require_once __DIR__ . '/_layout.php'; tutor_layout_start($tutor, 'dashboard', 'Dashboard Tutor'); ?>
    
    <main id="tutor-main" class="max-w-6xl mx-auto px-5 py-8 pb-20">
        <div class="flex flex-wrap justify-between items-end gap-4">
            <div>
                <p class="text-xs font-black tracking-wider text-violet-600 uppercase">✨ Mission Control</p>
                <h1 class="mt-1 text-3xl font-black">Halo, <?= tutor_e($tutor['display_name']) ?>! 👋</h1>
                <p class="mt-2 text-slate-600 font-bold">Kelola planet, misi wajib, dan side quest dari sini.</p>
            </div>
            <a href="planet-form.php" class="bg-violet-600 hover:bg-violet-700 rounded-2xl text-white px-5 py-3 font-black shadow">＋ Tambah Planet Baru</a>
        </div>
        <?php if ($databaseError): ?>
            <div class="notify notify-error mt-6" role="alert"><?= tutor_e($databaseError) ?></div>
        <?php endif; ?>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-7">
            <div class="editor-panel !p-5"><p class="text-sm text-slate-500 font-bold">Total Planet</p><p class="text-3xl font-black mt-2"><?= count($planets) ?></p></div>
            <div class="editor-panel !p-5"><p class="text-sm text-slate-500 font-bold">Draft</p><p class="text-3xl font-black mt-2"><?= $draftCount ?></p></div>
            <div class="editor-panel !p-5"><p class="text-sm text-slate-500 font-bold">Terpublikasi</p><p class="text-3xl font-black mt-2"><?= $publishedCount ?></p></div>
            <div class="editor-panel !p-5"><p class="text-sm text-slate-500 font-bold">Total Misi</p><p class="text-3xl font-black mt-2"><?= $missionCount ?></p></div>
        </div>
        <section class="editor-panel mt-7" aria-labelledby="planet-list-title">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
                <div><h2 id="planet-list-title" class="text-xl font-black">Planet yang Dikelola</h2><p class="text-sm font-bold text-slate-500">Klik Edit untuk melanjutkan draft atau memperbarui planet.</p></div>
            </div>
            <?php if (!$planets): ?>
                <div class="text-center rounded-2xl bg-violet-50 border-2 border-dashed border-violet-200 p-9">
                    <span class="text-5xl" aria-hidden="true">🛸</span>
                    <h3 class="text-lg font-black mt-3">Belum ada planet buatan tutor</h3>
                    <p class="text-sm font-bold text-slate-500 mt-1">Buat planet pertamamu dan simpan sebagai draft.</p>
                    <a class="inline-block mt-4 text-violet-700 underline font-black" href="planet-form.php">Mulai membuat planet</a>
                </div>
            <?php else: ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach ($planets as $p): ?>
                    <article class="rounded-2xl border-2 border-violet-100 p-5 bg-gradient-to-br from-white to-violet-50 flex flex-col gap-3">
                        <div class="flex justify-between gap-3 items-start">
                            <div class="min-w-0"><h3 class="text-lg font-black break-words"><?= tutor_e($p['name']) ?></h3><p class="text-xs text-slate-500 font-bold break-all"><?= tutor_e($p['id']) ?></p></div>
                            <span class="text-xs shrink-0 font-black px-3 py-1 rounded-full <?= $p['publication_status'] === 'draft' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' ?>"><?= $p['publication_status'] === 'draft' ? '◌ Draft' : '● Terpublikasi' ?></span>
                        </div>
                        <p class="text-sm font-bold text-slate-500 break-words"><?= tutor_e($p['description'] ?: 'Belum ada deskripsi.') ?></p>
                        <div class="flex flex-wrap gap-2 text-xs font-extrabold">
                            <span class="bg-white border border-slate-200 rounded-full px-3 py-1">🔒 Level <?= (int)$p['required_level'] ?></span>
                            <span class="bg-white border border-slate-200 rounded-full px-3 py-1">🎯 <?= (int)$p['mission_count'] ?> misi</span>
                            <span class="bg-white border border-slate-200 rounded-full px-3 py-1">⭐ <?= (int)$p['wajib_count'] ?> wajib</span>
                            <span class="bg-white border border-slate-200 rounded-full px-3 py-1">💎 <?= (int)$p['side_count'] ?> side</span>
                        </div>
                        <a class="mt-auto block text-center py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-black" href="planet-form.php?id=<?= rawurlencode($p['id']) ?>">✏️ Edit Planet</a>
                        <a class="mt-2 block text-center py-2.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-indigo-900 font-black" href="mission-list.php?planet=<?= rawurlencode($p['id']) ?>">📝 Kelola Soal Misi</a>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <p class="text-xs text-slate-500 font-bold mt-6">Planet Draft tidak tampil untuk siswa. Planet Published dengan soal siap dimainkan dapat diakses sesuai level siswa.</p>
    </main>
<?php tutor_layout_end(); ?>
</body>
</html>
