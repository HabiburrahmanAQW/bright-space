<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
// require_tutor();

$old = $_SESSION['old_planet_form'] ?? [];
$errors = $_SESSION['planet_errors'] ?? [];
unset($_SESSION['old_planet_form'], $_SESSION['planet_errors']);
$default = [
    'name' => '', 'slug' => '', 'description' => '', 'min_level' => 0,
    'theme_key' => 'indigo', 'icon_key' => 'matematika',
];
$form = array_merge($default, array_intersect_key(is_array($old) ? $old : [], $default));
$justCreated = filter_var($_GET['planet_id'] ?? null, FILTER_VALIDATE_INT);
$success = ($_GET['created'] ?? null) === '1' && $justCreated !== false && $justCreated !== null && $justCreated > 0 && (int) ($_SESSION['created_planet_id'] ?? 0) === $justCreated;
if ($success) unset($_SESSION['created_planet_id']);
$oldMissions = isset($old['missions']) && is_array($old['missions']) ? array_values($old['missions']) : [];
$encodeOptions = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>Tambah Misi Planet — Tutor Studio</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <header class="mb-7 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-200">
          <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true"><circle cx="16" cy="16" r="9" fill="white"/><ellipse cx="16" cy="16" rx="15" ry="5" stroke="white" stroke-width="2" transform="rotate(-25 16 16)"/><circle cx="16" cy="16" r="9" fill="#818CF8"/></svg>
        </div>
        <div><p class="eyebrow">Tutor Studio</p><p class="text-lg font-extrabold tracking-tight">Planet Mission Builder</p></div>
      </div>
      <span class="rounded-full border border-indigo-100 bg-white px-4 py-2 text-xs font-semibold text-slate-500">Editor Misi • Draft</span>
    </header>

    <div class="mb-8 max-w-3xl">
      <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-indigo-600">
        <span class="rounded-lg bg-indigo-100 px-2 py-1">01</span>
        <span>Identitas Planet</span><span class="text-slate-300">/</span>
        <span class="rounded-lg bg-white px-2 py-1 text-slate-600">02</span><span class="text-slate-500">Daftar Misi</span>
      </div>
      <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Buat planet pembelajaran <span class="text-indigo-600">baru.</span></h1>
      <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-500">Atur identitas dan level akses, kemudian tambahkan aktivitas untuk misi wajib maupun sampingan. Semua disimpan sebagai draft sampai soal disiapkan.</p>
    </div>

    <?php if ($success): ?>
      <div class="success-box mb-5" role="status">Planet berhasil dibuat sebagai <strong>draft</strong>. <a class="font-bold underline" href="lihat-planet.php?id=<?= $justCreated ?>">Lihat ringkasan planet →</a></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="error-box mb-5" role="alert"><strong>Periksa data berikut:</strong>
        <ul class="mt-2 list-inside list-disc text-sm">
          <?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="planet-form" method="post" action="simpan-planet.php" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_330px]">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <main class="space-y-6">
        <section class="panel p-5 sm:p-7" aria-labelledby="identitas-title">
          <div class="mb-6 flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-xl">🪐</div><div><p class="eyebrow">Langkah 01</p><h2 id="identitas-title" class="text-xl font-extrabold">Identitas Planet</h2></div></div>
          <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="name" class="field-label">Nama Planet <span class="text-red-500">*</span></label><input class="field" id="name" name="name" maxlength="120" required placeholder="Contoh: Planet Matematika" value="<?= h($form['name']) ?>"></div>
            <div class="sm:col-span-2"><label for="description" class="field-label">Deskripsi Planet <span class="text-red-500">*</span></label><textarea class="field" id="description" name="description" maxlength="500" required placeholder="Petualangan angka dan logika... "><?= h($form['description']) ?></textarea></div>
            <div><label for="slug" class="field-label">ID / Slug <span class="text-red-500">*</span></label><input class="field" id="slug" name="slug" maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*" required placeholder="planet-matematika" value="<?= h($form['slug']) ?>"><p class="helper mt-1.5">Otomatis dari nama, boleh disesuaikan.</p></div>
            <div><label for="min_level" class="field-label">Level Minimum <span class="text-red-500">*</span></label><div class="relative"><input class="field pr-18" type="number" id="min_level" name="min_level" min="0" max="1000" step="1" required value="<?= h($form['min_level']) ?>"><span class="pointer-events-none absolute right-3 top-3 text-xs font-bold text-slate-400">LEVEL</span></div><p class="helper mt-1.5">Level 0 = dapat diakses semua siswa (setelah dipublikasi).</p></div>
            <div><label for="theme_key" class="field-label">Tema Warna</label><select class="field" id="theme_key" name="theme_key"><?php foreach ($catalog['themes'] as $key => $label): ?><option value="<?= h($key) ?>" <?= $form['theme_key'] === $key ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
            <div><label for="icon_key" class="field-label">Ilustrasi Planet</label><select class="field" id="icon_key" name="icon_key"><?php foreach ($catalog['icons'] as $key => $icon): ?><option value="<?= h($key) ?>" <?= $form['icon_key'] === $key ? 'selected' : '' ?>><?= h($icon['label']) ?></option><?php endforeach; ?></select></div>
          </div>
        </section>

        <section class="panel p-5 sm:p-7" aria-labelledby="misi-title">
          <div class="mb-5 flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-xl">🚀</div><div><p class="eyebrow">Langkah 02</p><h2 id="misi-title" class="text-xl font-extrabold">Susun Daftar Misi</h2></div></div>
          <p class="mb-6 text-sm leading-6 text-slate-500">Satu kartu adalah satu kuis/aktivitas. Kamu bisa menambahkan beberapa kartu, bahkan dengan gamemode yang sama. Wajib ada setidaknya satu misi wajib.</p>

          <div class="mb-7">
            <div class="mb-3 flex items-center justify-between"><h3 class="flex items-center gap-2 text-base font-extrabold"><span class="category-flag flag-wajib">⭐ WAJIB</span><span id="count-wajib" class="text-xs text-slate-400">0 misi</span></h3></div>
            <div id="missions-wajib" aria-label="Daftar misi wajib" class="mb-3" aria-live="polite"></div>
            <button class="btn-outline w-full" type="button" id="add-wajib">＋ Tambah Misi Wajib</button>
          </div>
          <div>
            <div class="mb-3 flex items-center justify-between"><h3 class="flex items-center gap-2 text-base font-extrabold"><span class="category-flag flag-sampingan">✦ SAMPINGAN</span><span id="count-sampingan" class="text-xs text-slate-400">0 misi</span></h3></div>
            <div id="missions-sampingan" aria-label="Daftar misi sampingan" class="mb-3" aria-live="polite"></div>
            <button class="btn-outline w-full" type="button" id="add-sampingan">＋ Tambah Misi Sampingan</button>
          </div>
          <noscript><p class="mt-3 text-red-600">Aktifkan JavaScript untuk menambahkan dan menyunting kartu misi.</p></noscript>
        </section>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-5">
          <p class="max-w-md text-sm text-slate-600">💡 <strong>Draft terlebih dahulu.</strong> Soal tiap gamemode bisa disusun pada tahap editor soal terpisah.</p>
          <button class="btn-main" id="submit-button" type="submit">Simpan Planet & Misi →</button>
        </div>
      </main>

      <aside class="lg:sticky lg:top-6" aria-label="Pratinjau planet">
        <div class="panel overflow-hidden p-4 sm:p-5">
          <div class="mb-4 flex items-center justify-between"><p class="text-sm font-extrabold">Live Preview</p><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">DRAFT</span></div>
          <div class="planet-look" data-theme="<?= h($form['theme_key']) ?>" id="planet-look">
            <div class="planet-preview-bg flex min-h-57 flex-col items-center justify-center p-5 text-center">
              <div class="h-34 w-34" id="planet-icon-preview" aria-hidden="true"><?= $catalog['icons'][array_key_exists((string) $form['icon_key'], $catalog['icons']) ? $form['icon_key'] : 'matematika']['svg'] ?></div>
              <p id="planet-preview-name" class="mt-2 text-lg font-black leading-6">Planet Baru</p>
              <p id="planet-preview-description" class="mt-1 line-clamp-2 text-xs text-slate-600">Deskripsi petualangan...</p>
              <span id="planet-preview-status" class="preview-badge is-unlocked mt-3">🔓 Terbuka</span>
            </div>
          </div>
          <div class="mt-5">
            <div class="flex items-center justify-between"><label class="field-label mb-0" for="student-level">Simulasi Level Siswa</label><span class="rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-black" id="student-level-value">0</span></div>
            <input class="mt-3 w-full" id="student-level" type="range" min="0" max="20" value="0">
            <p class="helper mt-2" id="level-info">Bandingkan level siswa dengan level minimum planet.</p>
          </div>
          <div class="my-5 border-t border-slate-100"></div>
          <h3 class="mb-3 text-sm font-extrabold">Ringkasan Misi</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between rounded-xl bg-indigo-50 px-3 py-3 text-sm"><span class="font-semibold text-indigo-800">⭐ Misi Wajib</span><span class="font-black text-indigo-800" id="summary-wajib">0</span></div>
            <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-3 text-sm"><span class="font-semibold text-emerald-800">✦ Misi Sampingan</span><span class="font-black text-emerald-800" id="summary-sampingan">0</span></div>
            <div class="flex items-center justify-between px-1 text-sm"><span class="text-slate-500">Total Aktivitas</span><span class="font-black" id="summary-total">0</span></div>
          </div>
          <p class="helper mt-5 rounded-xl bg-slate-50 p-3">Pratinjau level ini hanya simulasi untuk tutor. Hak akses siswa wajib diperiksa ulang oleh PHP di server.</p>
        </div>
      </aside>
    </form>
  </div>
  <?php foreach ($catalog['icons'] as $key => $item): ?>
    <template id="icon-<?= h($key) ?>"><?= $item['svg'] ?></template>
  <?php endforeach; ?>
  <script type="application/json" id="mode-catalog"><?= json_encode($catalog['modes'], $encodeOptions | JSON_THROW_ON_ERROR) ?></script>
  <script type="application/json" id="initial-missions"><?= json_encode($oldMissions, $encodeOptions | JSON_THROW_ON_ERROR) ?></script>
  <script src="assets/form.js" defer></script>
</body>
</html>
