<?php
require __DIR__ . '/_planet-editor.php';
$tutor = tutor_require_login();
$editId = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
$isEdit = $editId !== '';

$planet = [
    'id' => '', 'name' => '', 'description' => '', 'required_level' => '0',
    'theme_key' => 'indigo', 'icon_key' => 'umum', 'publication_status' => 'draft',
];
$missions = [];
try {
    if ($isEdit) {
        $found = planet_editor_owned($editId, $tutor);
        if (!$found) {
            http_response_code(404);
            exit('Planet tidak ditemukan atau bukan milik akun tutor ini.');
        }
        $planet = $found;
        $missions = mission_list_for_planet($editId);
    }
} catch (Throwable $e) {
    error_log('Planet editor load error: ' . $e->getMessage());
    http_response_code(503);
    exit('Tidak dapat membuka editor. Periksa koneksi MySQL dan tabel Tahap 1.');
}

$flash = $_SESSION['planet_editor_flash'] ?? null;
unset($_SESSION['planet_editor_flash']);
$success = $_SESSION['planet_saved_message'] ?? '';
unset($_SESSION['planet_saved_message']);
if (is_array($flash) && is_array($flash['old'] ?? null)) {
    $old = $flash['old'];
    foreach (['name', 'description', 'required_level', 'theme_key', 'icon_key', 'slug'] as $key) {
        if (isset($old[$key]) && is_string($old[$key])) {
            $planet[$key === 'slug' ? 'id' : $key] = $old[$key];
        }
    }
    if (isset($old['missions_json']) && is_string($old['missions_json'])) {
        $decoded = json_decode($old['missions_json'], true);
        if (is_array($decoded) && array_is_list($decoded)) $missions = array_slice($decoded, 0, 24);
    }
}

$icons = mission_editor_icons();
$themes = mission_editor_themes();
$modes = mission_editor_modes();
$bootstrap = [
    'missions' => $missions,
    'modes' => $modes,
    'icons' => $icons,
    'isEdit' => $isEdit,
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Planet — BRIGHT SPACE Tutor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{nunito:['Nunito','ui-rounded','sans-serif']}}}};</script>
    <link rel="stylesheet" href="assets/editor.css">
    <script id="editor-data" type="application/json"><?= json_encode($bootstrap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
    <script src="assets/planet-editor.js" defer></script>
    <link rel="stylesheet" href="assets/tutor-layout.css">
</head>
<body class="bg-sky-50 text-indigo-950 font-nunito min-h-screen antialiased">
<?php require_once __DIR__ . '/_layout.php'; tutor_layout_start($tutor, 'planets', 'Editor Planet'); ?>
    

    <main id="tutor-main" class="max-w-7xl mx-auto px-4 sm:px-6 py-7 pb-20">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="uppercase tracking-widest text-xs text-violet-600 font-black mb-1">🚀 Pusat Kreasi Tutor</p>
                <h1 class="text-3xl sm:text-4xl font-black"><?= $isEdit ? 'Edit Misi Planet' : 'Buat Planet Baru' ?></h1>
                <p class="text-sm sm:text-base font-bold text-slate-500 mt-2">Atur syarat level, kategori misi, dan pilihan gamemode — semuanya dalam satu tempat.</p>
            </div>
            <span class="status-pill" id="status-pill"><?= ($planet['publication_status'] === 'published') ? '● Terpublikasi' : '◌ Draft' ?></span>
        </div>

        <?php if ($success !== ''): ?>
            <div class="notify notify-success" role="status"><?= tutor_e($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash['errors'])): ?>
            <div class="notify notify-error" role="alert">
                <p class="font-black mb-1">Data belum berhasil disimpan:</p>
                <ul class="list-disc pl-6">
                    <?php foreach (array_slice($flash['errors'], 0, 30) as $error): ?>
                    <li><?= tutor_e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="planet-form" action="planet-save.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
            <input type="hidden" name="original_id" value="<?= $isEdit ? tutor_e($editId) : '' ?>">
            <input type="hidden" name="missions_json" id="missions-json" value="[]">

            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_350px] items-start gap-6">
                <div class="space-y-6 min-w-0">
                    <section class="editor-panel" aria-labelledby="identity-title">
                        <div class="panel-heading">
                            <span class="panel-heading-icon" aria-hidden="true">🌎</span>
                            <div><h2 id="identity-title" class="text-xl font-black">Identitas Planet</h2><p class="text-sm text-slate-500 font-bold">Informasi yang akan dilihat siswa.</p></div>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="field-label" for="planet-name">Nama Planet <span class="text-rose-600">*</span></label>
                                <input class="field-input" type="text" id="planet-name" name="name" maxlength="120" placeholder="Contoh: Planet Matematika" required value="<?= tutor_e($planet['name']) ?>">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="field-label" for="planet-slug">ID / Slug Planet <span class="text-rose-600">*</span></label>
                                <div class="flex gap-2 items-stretch">
                                    <input class="field-input min-w-0" type="text" id="planet-slug" name="slug" maxlength="64" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="planet-matematika" required value="<?= tutor_e($planet['id']) ?>" <?= $isEdit ? 'readonly' : '' ?>>
                                    <?php if (!$isEdit): ?><button type="button" id="slug-auto" class="btn-small" title="Buat slug dari nama planet">Otomatis</button><?php endif; ?>
                                </div>
                                <p class="field-help"><?= $isEdit ? 'ID tidak dapat diubah setelah planet dibuat.' : 'Huruf kecil, angka, dan tanda hubung. Dibuat otomatis dari nama, tetapi bisa kamu edit.' ?></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="field-label" for="planet-description">Deskripsi Singkat</label>
                                <textarea class="field-input resize-y" name="description" id="planet-description" rows="3" maxlength="500" placeholder="Ceritakan petualangan seru di planet ini..."><?= tutor_e($planet['description']) ?></textarea>
                            </div>
                            <div>
                                <label class="field-label" for="planet-level">Level Minimum Siswa <span class="text-rose-600">*</span></label>
                                <input class="field-input" type="number" min="0" max="1000" step="1" id="planet-level" name="required_level" required value="<?= tutor_e($planet['required_level']) ?>">
                                <p class="field-help">Level 0 = dapat diakses semua siswa.</p>
                            </div>
                            <div>
                                <label class="field-label" for="planet-icon">Ikon Planet</label>
                                <select class="field-input" id="planet-icon" name="icon_key">
                                <?php foreach (['umum'=>'🌟 Umum','matematika'=>'➕ Matematika','sains'=>'🔬 Sains','bahasa'=>'📖 Bahasa','seni-coding'=>'🎨 Seni & Coding'] as $key => $label): ?>
                                    <option value="<?= tutor_e($key) ?>" <?= $planet['icon_key'] === $key ? 'selected' : '' ?>><?= tutor_e($label) ?></option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <fieldset class="mt-5">
                            <legend class="field-label">Tema Warna</legend>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <?php foreach ($themes as $key => $theme): ?>
                                <label class="color-choice">
                                    <input type="radio" name="theme_key" value="<?= tutor_e($key) ?>" <?= $planet['theme_key'] === $key ? 'checked' : '' ?>>
                                    <span class="color-choice-face"><span class="w-5 h-5 rounded-full border-2 border-white shadow-sm shrink-0" style="background:<?= tutor_e($theme['dot']) ?>"></span> <?= tutor_e($theme['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </section>

                    <section class="editor-panel" aria-labelledby="missions-title">
                        <div class="flex flex-wrap justify-between items-center gap-3 mb-5">
                            <div class="panel-heading mb-0">
                                <span class="panel-heading-icon" aria-hidden="true">🎯</span>
                                <div><h2 id="missions-title" class="text-xl font-black">Daftar Misi</h2><p class="text-sm text-slate-500 font-bold">Satu planet boleh memiliki banyak misi.</p></div>
                            </div>
                            <span id="mission-count" class="bg-amber-100 text-amber-900 px-3 py-1 rounded-full text-xs font-black">0 Misi</span>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-5">
                            <button type="button" id="add-wajib" class="btn-add btn-add-primary">＋ Misi Wajib</button>
                            <button type="button" id="add-side" class="btn-add btn-add-secondary">＋ Misi Sampingan</button>
                        </div>
                        <div id="mission-list" class="space-y-4" aria-live="polite"></div>
                        <div id="mission-empty" class="rounded-2xl border-2 border-dashed border-violet-200 bg-violet-50 p-6 text-center">
                            <span class="text-3xl" aria-hidden="true">🛸</span>
                            <p class="font-black mt-2">Belum ada misi.</p>
                            <p class="text-sm text-slate-500 font-bold mt-1">Tambahkan misi wajib atau sampingan untuk mulai membuat petualangan.</p>
                        </div>
                        <p class="text-xs text-slate-500 font-bold mt-4">Misi yang dihapus dari planet yang sudah disimpan akan dihapus dari database setelah kamu menyimpan perubahan.</p>
                    </section>
                </div>

                <aside class="space-y-5 lg:sticky lg:top-5" aria-label="Pratinjau planet dan penyimpanan">
                    <section class="editor-panel !p-4 sm:!p-5">
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <h2 class="text-lg font-black">Preview Siswa</h2>
                            <span class="text-[11px] font-extrabold text-violet-600">Live Preview</span>
                        </div>
                        <article class="preview-card" id="planet-preview" data-theme="<?= tutor_e($planet['theme_key']) ?>">
                            <span id="preview-level" class="preview-level">🔓 Level 0</span>
                            <div id="preview-icon" class="w-24 h-24 mx-auto mt-5 mb-3" aria-hidden="true"><?= $icons[$planet['icon_key']] ?? $icons['umum'] ?></div>
                            <h3 id="preview-title" class="text-xl font-black text-center break-words"><?= tutor_e($planet['name'] ?: 'Planet Baru') ?></h3>
                            <p id="preview-description" class="mt-2 text-center text-sm font-bold opacity-80 min-h-10 break-words"><?= tutor_e($planet['description'] ?: 'Deskripsi planet akan muncul di sini.') ?></p>
                            <div class="preview-button">🚀 Jelajahi Planet</div>
                        </article>
                        <div class="grid grid-cols-2 gap-2 mt-3 text-center">
                            <div class="rounded-xl bg-amber-50 border border-amber-200 p-3"><span class="block font-black text-lg" id="count-wajib">0</span><span class="text-xs font-bold text-slate-600">Misi Wajib</span></div>
                            <div class="rounded-xl bg-sky-50 border border-sky-200 p-3"><span class="block font-black text-lg" id="count-side">0</span><span class="text-xs font-bold text-slate-600">Misi Sampingan</span></div>
                        </div>
                    </section>
                    <section class="editor-panel !p-4 sm:!p-5 space-y-3">
                        <h2 class="text-lg font-black">Simpan Planet</h2>
                        <p class="text-sm font-bold text-slate-600"><?php if ($isEdit && $planet['publication_status'] === 'published'): ?>Planet tetap Published saat perubahan disimpan. Perubahan soal untuk siswa diterapkan melalui Update Publikasi di daftar misi. Untuk menghapus atau mengganti gamemode misi yang sudah tayang, kembalikan planet ke Draft dahulu.<?php else: ?>Planet baru disimpan sebagai Draft. Tambahkan soal, lalu publikasikan melalui halaman Kelola Soal.<?php endif; ?></p>
                        <button type="submit" name="save_action" value="draft" class="w-full rounded-2xl border-2 border-violet-400 bg-white text-violet-700 hover:bg-violet-50 py-3 font-black shadow-sm">💾 <?= ($isEdit && $planet['publication_status'] === 'published') ? 'Simpan Perubahan' : 'Simpan Draft' ?></button>
                        <button type="submit" name="save_action" value="draft_next" class="w-full rounded-2xl bg-violet-600 hover:bg-violet-700 text-white py-3 font-black shadow-sm">📝 Simpan &amp; Kelola Soal →</button>
                        <p class="text-xs text-slate-500 font-bold">Misi harus tersimpan sebelum menambah soal. Klik Simpan &amp; Kelola Soal untuk lanjut. Publikasikan setelah sedikitnya satu soal dibuat.</p>
                        <p id="form-client-error" role="alert" class="hidden text-sm font-bold text-rose-600"></p>
                    </section>
                </aside>
            </div>
        </form>
    </main>
<?php tutor_layout_end(); ?>
</body>
</html>
