<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/question_repository.php';
// BS_STAGE6B_QUESTION_NOTE
require_once __DIR__ . '/../config/publication_repository.php';
$tutor = tutor_require_login();
$missionId = filter_input(INPUT_GET, 'mission', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$mission = $missionId ? bmq_owned_mission((int)$missionId, $tutor) : null;
if (!$mission) { http_response_code(404); exit('Misi tidak ditemukan atau bukan milik akun ini.'); }
$mode = $mission['mode_type'];
$questions = bmq_list((int)$mission['id'], $mode);
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$question = $editId ? bmq_find((int)$editId, (int)$mission['id'], $mode) : null;
if ($editId && !$question) { http_response_code(404); exit('Soal tidak ditemukan.'); }
$old = $_SESSION['bmq_old'] ?? [];
$errors = $_SESSION['bmq_errors'] ?? [];
unset($_SESSION['bmq_old'],$_SESSION['bmq_errors']);
$payload = $question['payload'] ?? [];
$val = fn(string $key, $default='') => tutor_e($old[$key] ?? $default);
$back = 'mission-list.php?planet=' . rawurlencode($mission['planet_id']);
$modeNames = ['kuis'=>'⚡ Kuis Pilihan Ganda','story'=>'📖 Cerita dan Keputusan','puzzle'=>'🔤 Susun Kata','matching'=>'🧩 Cocokkan Pasangan','latihan'=>'🎓 Latihan Bebas'];
$count = count($questions); $target = (int)$mission['question_target'];
$ready = $count >= $target;
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Editor Soal · BRIGHT SPACE</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{nunito:['Nunito','sans-serif']}}}};</script>
<link rel="stylesheet" href="assets/editor.css">
<style>.bs-input{display:block;width:100%;border:2px solid #ddd6fe;background:white;border-radius:14px;padding:11px 13px;font-weight:700}.bs-input:focus{outline:3px solid #c4b5fd}.bs-label{display:block;font-size:14px;font-weight:900;margin-bottom:6px}.bs-panel{background:#fff;border:2px solid #ddd6fe;border-radius:24px;padding:22px;box-shadow:0 5px 15px #312e8110}.bs-btn{background:#7c3aed;color:white;padding:10px 18px;border-radius:14px;font-weight:900}</style>
</head><body class="bg-sky-50 text-indigo-950 font-nunito min-h-screen"><header class="bg-white border-b-4 border-amber-200 px-5 py-4"><div class="max-w-6xl mx-auto flex flex-wrap gap-2 items-center justify-between"><a href="index.php" class="font-black text-lg">🪐 BRIGHT SPACE · Tutor</a><a href="<?= tutor_e($back) ?>" class="font-black text-sm text-violet-700">← Kembali ke Misi</a></div></header>
<main class="max-w-6xl mx-auto p-5 py-8 space-y-5">
<div><p class="font-black text-violet-600 text-xs uppercase">Bank Soal / <?= tutor_e($mission['planet_name']) ?></p><h1 class="text-3xl font-black mt-1"><?= tutor_e($mission['title']) ?></h1><p class="mt-2 text-sm font-black text-amber-700">⚠️ Setelah menambah atau mengubah soal di planet Published, buka daftar misi dan klik Update Publikasi agar perubahan tampil untuk siswa. <a class="underline text-violet-700" href="mission-list.php?planet=<?= rawurlencode($mission['planet_id']) ?>">← Ke Daftar Misi</a></p>
<p class="font-bold text-slate-600 mt-1"><?= tutor_e($modeNames[$mode] ?? $mode) ?> · <?= tutor_e($mission['category']) ?> · Target <?= $target ?> soal</p></div>
<div class="bs-panel"><div class="flex flex-wrap gap-3 justify-between items-center"><div><h2 class="font-black text-xl">Kesiapan Misi</h2><p class="font-bold text-slate-600"><?= $count ?>/<?= $target ?> soal format sesuai <span class="<?= $ready ? 'text-emerald-700' : 'text-amber-700' ?> font-black"><?= $ready ? '✓ Siap dimainkan saat published' : '· Belum cukup soal' ?></span></p></div><a href="questions.php?mission=<?= (int)$mission['id'] ?>" class="bs-btn">＋ Tambah Soal Baru</a></div><p class="mt-3 text-sm text-slate-500 font-bold">Planet dan misi harus berstatus published agar siswa dapat bermain. Soal disimpan per ID misi; jumlah target diatur pada Editor Planet.</p></div>
<?php if ($errors): ?><div class="p-4 rounded-xl border-2 border-rose-300 bg-rose-50" role="alert"><p class="font-black">Periksa input berikut:</p><?php foreach($errors as $error): ?><p class="font-bold text-sm mt-1">• <?= tutor_e($error) ?></p><?php endforeach ?></div><?php endif ?>
<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px] gap-5 items-start"><section class="bs-panel"><h2 class="font-black text-xl mb-4"><?= $question ? 'Edit Soal #'.(int)$question['id'] : 'Tambah Soal Baru' ?></h2>
<form action="question-action.php" method="post" class="space-y-4"><input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>"><input type="hidden" name="mission_id" value="<?= (int)$mission['id'] ?>"><input type="hidden" name="question_id" value="<?= (int)($question['id'] ?? 0) ?>"><input type="hidden" name="action" value="save">
<div><label class="bs-label" for="prompt"><?= $mode==='story' ? 'Skenario Cerita dan Pertanyaan' : ($mode==='matching' ? 'Petunjuk Pencocokan' : 'Pertanyaan / Instruksi') ?></label><textarea class="bs-input" id="prompt" name="prompt" rows="3" maxlength="1500" required placeholder="Tuliskan instruksi dengan jelas..."><?= $val('prompt',$question['prompt'] ?? '') ?></textarea></div>
<?php if (in_array($mode,['kuis','story','latihan'],true)): ?>
<div class="p-4 rounded-xl bg-indigo-50 border border-indigo-200 space-y-3"><p class="font-black text-sm">Tiga pilihan jawaban</p>
<?php foreach(['A','B','C'] as $letter): ?><div><label class="bs-label" for="choice-<?= $letter ?>">Pilihan <?= $letter ?></label><input class="bs-input" id="choice-<?= $letter ?>" name="choice_<?= $letter ?>" maxlength="300" required value="<?= $val('choice_'.$letter, $payload['choices'][$letter] ?? '') ?>"></div><?php endforeach ?>
<div><label class="bs-label" for="correct">Kunci Jawaban</label><select class="bs-input" id="correct" name="correct" required><option value="">Pilih jawaban benar</option><?php foreach(['A','B','C'] as $letter): ?><option value="<?= $letter ?>" <?= (($old['correct'] ?? $payload['correct'] ?? '')===$letter) ? 'selected':'' ?>>Pilihan <?= $letter ?></option><?php endforeach ?></select></div></div>
<?php elseif ($mode==='puzzle'): ?>
<div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 space-y-3"><p class="font-black">Susun kata</p><div><label class="bs-label" for="answer">Jawaban yang benar</label><input class="bs-input" id="answer" name="answer" maxlength="100" required value="<?= $val('answer', $payload['answer'] ?? '') ?>" placeholder="Contoh: GALAKSI"></div><div><label class="bs-label" for="scramble">Huruf/Kata Acak untuk Siswa</label><input class="bs-input" id="scramble" name="scramble" maxlength="100" required value="<?= $val('scramble', $payload['scramble'] ?? '') ?>" placeholder="Contoh: SIKALAG"></div><div><label class="bs-label" for="hint">Petunjuk opsional</label><input class="bs-input" id="hint" name="hint" maxlength="250" value="<?= $val('hint', $payload['hint'] ?? '') ?>"></div></div>
<?php elseif ($mode==='matching'): ?>
<div class="p-4 rounded-xl bg-amber-50 border border-amber-200 space-y-3"><p class="font-black">Tiga pasangan yang benar</p><p class="text-sm font-bold text-slate-600">Isi kolom kiri dan pasangannya di kanan. Pilihan kanan akan diacak saat siswa bermain.</p><?php for($i=0;$i<3;$i++): ?><div class="grid sm:grid-cols-2 gap-2"><div><label class="bs-label">Kiri <?= $i+1 ?></label><input class="bs-input" name="left[]" required maxlength="120" value="<?= tutor_e($old['left'][$i] ?? $payload['pairs'][$i]['left'] ?? '') ?>"></div><div><label class="bs-label">Pasangan Kanan <?= $i+1 ?></label><input class="bs-input" name="right[]" required maxlength="120" value="<?= tutor_e($old['right'][$i] ?? $payload['pairs'][$i]['right'] ?? '') ?>"></div></div><?php endfor ?></div>
<?php endif ?>
<div><label class="bs-label" for="explanation">Penjelasan / Feedback Setelah Menjawab (opsional)</label><textarea class="bs-input" name="explanation" id="explanation" rows="3" maxlength="1500"><?= $val('explanation',$question['explanation'] ?? '') ?></textarea></div>
<div class="flex flex-wrap gap-3"><button type="submit" class="bs-btn">💾 <?= $question ? 'Simpan Perubahan' : 'Simpan Soal' ?></button><?php if($question): ?><a href="questions.php?mission=<?= (int)$mission['id'] ?>" class="font-black text-violet-700 px-4 py-3">Batal Edit</a><?php endif ?></div>
</form></section>
<aside class="bs-panel"><h2 class="font-black text-xl mb-3">Daftar Soal <span class="text-sm text-slate-500">(<?= $count ?>)</span></h2>
<?php if(!$questions): ?><p class="text-sm font-bold text-slate-500">Belum ada soal. Mulai dengan mengisi formulir di sebelah kiri.</p><?php endif ?>
<div class="space-y-3"><?php foreach($questions as $i=>$q): ?><article class="bg-sky-50 rounded-xl border border-violet-200 p-3"><p class="text-xs text-violet-600 font-black">Soal <?= $i+1 ?></p><p class="font-bold text-sm mt-1 break-words"><?= tutor_e($q['prompt']) ?></p><div class="flex flex-wrap gap-2 mt-2 items-center"><a href="questions.php?mission=<?= (int)$mission['id'] ?>&amp;edit=<?= (int)$q['id'] ?>" class="rounded-lg bg-violet-100 px-3 py-1 text-sm font-black">Edit</a><form action="question-action.php" method="post" onsubmit="return confirm('Hapus soal ini?');"><input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="mission_id" value="<?= (int)$mission['id'] ?>"><input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>"><button class="rounded-lg bg-rose-100 text-rose-700 px-3 py-1 font-black text-sm" type="submit">Hapus</button></form></div></article><?php endforeach ?></div></aside></div>
</main></body></html>
