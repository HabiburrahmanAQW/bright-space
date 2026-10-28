<?php
require __DIR__.'/_auth.php';
require_once __DIR__.'/../config/question_repository.php';
// BS_STAGE6B_MISSION_LIST
require_once __DIR__.'/../config/publication_repository.php';
$tutor=tutor_require_login();
$planetId=(string)($_GET['planet']??'');
if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$planetId) || strlen($planetId)>64){http_response_code(400);exit('ID planet tidak valid.');}
$stmt=mission_db()->prepare('SELECT id, name, created_by, publication_status FROM bs_planets WHERE id=?');$stmt->execute([$planetId]);$planet=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$planet || ($tutor['role']!=='admin' && (int)$planet['created_by']!==(int)$tutor['id'])){http_response_code(404);exit('Planet tidak ditemukan.');}
$missions=mission_list_for_planet($planetId);
$readyCounts = [];
$allQuestionCount = 0;
$readyMissionCount = 0;
foreach ($missions as $oneMission) {
    $num = bmq_count((int)$oneMission['id'], (string)$oneMission['mode_type']);
    $readyCounts[(int)$oneMission['id']] = $num;
    $allQuestionCount += $num;
    if ($num >= (int)$oneMission['question_target']) $readyMissionCount++;
}
$bsPubState=bs_pub_status($planetId);
$flash = $_SESSION['bs_publication_flash'] ?? null;
unset($_SESSION['bs_publication_flash']);
$labels=['kuis'=>'⚡ Kuis','story'=>'📖 Cerita','puzzle'=>'🔤 Susun Kata','matching'=>'🧩 Mencocokkan','latihan'=>'🎓 Latihan'];
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kelola Soal — BRIGHT SPACE</title><link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><script>tailwind.config={theme:{extend:{fontFamily:{nunito:['Nunito','sans-serif']}}}};</script><link rel="stylesheet" href="assets/editor.css"></head>
<body class="bg-sky-50 text-indigo-950 font-nunito min-h-screen"><header class="bg-white border-b-4 border-amber-200 px-5 py-4"><div class="max-w-6xl mx-auto flex justify-between flex-wrap gap-3"><a href="index.php" class="font-black text-lg">🪐 BRIGHT SPACE Tutor</a><a class="font-black text-violet-700" href="planet-form.php?id=<?= rawurlencode($planetId) ?>">← Edit Planet</a></div></header><main class="max-w-6xl mx-auto p-5 py-8"><p class="font-black uppercase text-sm text-violet-600">Bank Soal · <?= tutor_e($planet['publication_status']) ?></p><h1 class="font-black text-3xl mt-2">Misi <?= tutor_e($planet['name']) ?></h1><p class="mt-2 text-slate-600 font-bold">Pilih misi untuk mengelola soal. Misi siap dimainkan jika jumlah soal format saat ini sudah memenuhi target dan planetnya published.</p><?php if (is_array($flash)): ?><div role="status" class="mt-5 rounded-2xl border-2 <?= !empty($flash['ok'])?'bg-emerald-50 border-emerald-300 text-emerald-800':'bg-rose-50 border-rose-300 text-rose-800' ?> px-5 py-4 font-black"><?= tutor_e($flash['message']??'') ?></div><?php endif; ?>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
<?php foreach($missions as $m): $n=$readyCounts[(int)$m['id']];$goal=(int)$m['question_target'];?><article class="bg-white border-2 border-violet-200 rounded-3xl p-5 flex flex-col shadow-sm"><p class="font-black text-sm text-violet-600"><?= $m['category']==='wajib' ? '⭐ Misi Wajib' : '💎 Sampingan' ?> · <?= tutor_e($labels[$m['mode_type']]??$m['mode_type']) ?></p><h2 class="font-black text-xl mt-2"><?= tutor_e($m['title']) ?></h2><p class="font-bold text-sm text-slate-600 mt-2"><?= $n ?>/<?= $goal ?> soal · <span class="<?= $n>=$goal?'text-emerald-700':'text-amber-700' ?>"><?= $n>=$goal?'✓ Cukup soal':'Belum cukup soal' ?></span></p><a class="mt-5 block text-center bg-violet-600 hover:bg-violet-700 text-white font-black rounded-xl px-3 py-3" href="questions.php?mission=<?= (int)$m['id'] ?>">Kelola Soal →</a></article><?php endforeach ?>
<?php if(!$missions): ?><p class="font-bold text-slate-600">Planet ini belum memiliki misi. Tambahkan melalui Edit Planet.</p><?php endif ?></div>
<section class="mt-8 bg-white border-2 border-violet-200 rounded-3xl p-5 sm:p-7 shadow-sm" aria-labelledby="publish-title">
  <div class="flex flex-wrap justify-between gap-4 items-start">
    <div class="max-w-2xl">
      <p class="uppercase tracking-widest text-violet-600 text-xs font-black">Langkah Terakhir</p>
      <h2 id="publish-title" class="font-black text-2xl mt-1">🚀 Publikasi Planet</h2><p class="text-sm font-bold text-indigo-700 mt-2">📝 Soal editor: <?= (int)$bsPubState['live_questions'] ?> · 🌍 Soal publik: <?= (int)$bsPubState['published_questions'] ?> · Perubahan: <?= (int)$bsPubState['pending_missions'] ?> misi</p>
      <p class="text-slate-600 font-bold mt-2">Simpan semua perubahan misi melalui Edit Planet terlebih dahulu. Planet dapat dipublikasikan setelah memiliki minimal satu soal sesuai gamemode saat ini. Misi yang belum memenuhi target soal tetap tidak dapat dimainkan.</p>
      <div class="flex flex-wrap gap-2 mt-4 font-black text-xs">
        <span class="bg-violet-100 rounded-full px-3 py-2">📝 <?= $allQuestionCount ?> soal valid</span>
        <span class="bg-emerald-100 rounded-full px-3 py-2">✅ <?= $readyMissionCount ?>/<?= count($missions) ?> misi siap dimainkan</span>
        <span class="<?= $planet['publication_status']==='published'?'bg-emerald-100':'bg-amber-100' ?> rounded-full px-3 py-2"><?= $planet['publication_status']==='published'?'● Published':'◌ Draft' ?></span>
      </div>
    </div>
    <form method="post" action="planet-publication.php" class="w-full sm:w-auto">
      <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
      <input type="hidden" name="planet_id" value="<?= tutor_e($planetId) ?>">
      <?php if ($planet['publication_status']==='published'): ?>
        <button type="submit" name="action" value="draft" class="w px-6 py-3 rounded-2xl font-black bg-amber-100 border-2 border-amber-300 text-amber-900 hover:bg-amber-200">↩ Kembalikan ke Draft</button>
<?php if ($bsPubState['has_changes']): ?>
<button type="submit" name="action" value="update" class="w mt-2 px-6 py-3 rounded-2xl font-black bg-emerald-600 text-white hover:bg-emerald-700">🔄 Update Publikasi (<?= (int)$bsPubState['pending_missions'] ?> misi berubah)</button>
<p class="text-xs font-bold mt-2 text-amber-800">Soal baru dan edit soal hanya terlihat siswa setelah Update Publikasi. Jika jumlah soal lebih banyak dari target, target dinaikkan otomatis saat update.</p>
<?php else: ?>
<p class="mt-2 text-center font-extrabold text-emerald-700 text-sm">✅ Publikasi sudah terbaru</p>
<?php endif; ?>
      <?php else: ?>
        <button type="submit" name="action" value="publish" <?= $allQuestionCount===0?'disabled aria-disabled="true"':'' ?> class="w-full px-6 py-3 rounded-2xl font-black text-white bg-violet-600 hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed">🚀 Publikasikan Planet</button>
      <?php endif; ?>
    </form>
  </div>
  <?php if ($allQuestionCount===0): ?><p class="mt-4 font-extrabold text-amber-700">🔒 Tambahkan minimal satu soal terlebih dahulu agar tombol Publikasikan aktif.</p><?php endif; ?>
</section>
</main></body></html>
