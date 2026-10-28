<?php
/** Pendaftaran tutor hanya tersedia bagi admin yang sudah login. */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/registration.php';
$tutor = tutor_require_login();
if (($tutor['role'] ?? '') !== 'admin') {
    http_response_code(403); exit('Hanya administrator yang dapat mengelola akun tutor.');
}
$errors = [];
$old = ['display_name' => '', 'username' => ''];
$success = (string)($_SESSION['bs_tutor_created_flash'] ?? '');
unset($_SESSION['bs_tutor_created_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!tutor_valid_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $errors[] = 'Token keamanan tidak valid. Muat ulang halaman.';
    } else {
        [$data, $errors] = bs_registration_validate($_POST);
        $old = ['display_name' => $data['display_name'], 'username' => $data['username']];
        if (!$errors) {
            try {
                $stmt = db()->prepare(
                    "INSERT INTO bs_tutors(username,display_name,password_hash,role,is_active) VALUES(?,?,?,'tutor',1)"
                );
                $stmt->execute([$data['username'], $data['display_name'], password_hash($data['password'], PASSWORD_DEFAULT)]);
                $_SESSION['bs_tutor_created_flash'] = 'Akun tutor berhasil dibuat dan sudah aktif.';
                header('Location: accounts.php', true, 303); exit;
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '23000') {
                    $errors[] = 'Username tutor sudah dipakai.';
                } else {
                    error_log('Admin tambah tutor: '.$e->getMessage());
                    $errors[] = 'Tidak dapat menyimpan akun tutor.';
                }
            } catch (Throwable $e) {
                error_log('Admin tambah tutor: '.$e->getMessage());
                $errors[] = 'Pembuatan akun gagal. Periksa koneksi dan log server.';
            }
        }
    }
}
$rows = [];
try {
    $rows = db()->query('SELECT id,username,display_name,role,is_active FROM bs_tutors ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Daftar tutor: '.$e->getMessage());
    $errors[] = 'Daftar akun belum dapat dimuat.';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola Akun Tutor — BRIGHT SPACE</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/editor.css">
<link rel="stylesheet" href="assets/tutor-layout.css">
</head>
<body class="bg-sky-50 text-indigo-950 min-h-screen antialiased" style="font-family:Nunito,ui-rounded,system-ui,sans-serif">
<?php tutor_layout_start($tutor, 'accounts', 'Kelola Akun Tutor'); ?>
<main id="tutor-main" class="max-w-6xl mx-auto px-5 py-8 pb-20 space-y-6">
    <div>
        <p class="uppercase text-xs font-black tracking-widest text-violet-600">Administrator · Manajemen Akun</p>
        <h1 class="font-black text-3xl mt-1">Tambahkan Tutor Baru 📚</h1>
        <p class="font-bold text-slate-500 mt-2">Akun tutor hanya dibuat oleh administrator, bukan melalui register publik.</p>
    </div>
    <?php if ($success): ?><p class="notify notify-success" role="status"><?= tutor_e($success) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="notify notify-error" role="alert"><?php foreach($errors as $message): ?><p><?= tutor_e($message) ?></p><?php endforeach; ?></div><?php endif; ?>
    <section class="editor-panel max-w-2xl">
        <h2 class="text-xl font-black">Buat Akun Tutor</h2>
        <form method="post" action="accounts.php" class="space-y-4 mt-5" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
            <div><label class="field-label" for="display_name">Nama tampilan</label>
                <input class="field-input" id="display_name" name="display_name" required minlength="2" maxlength="80"
                    value="<?= tutor_e($old['display_name']) ?>" placeholder="Nama pengajar"></div>
            <div><label class="field-label" for="username">Username tutor</label>
                <input class="field-input" id="username" name="username" required minlength="3" maxlength="30"
                    pattern="[a-zA-Z][a-zA-Z0-9_]{2,29}" value="<?= tutor_e($old['username']) ?>" placeholder="Contoh: guru_andi"></div>
            <div><label class="field-label" for="password">Password awal</label>
                <input class="field-input" id="password" name="password" type="password" required minlength="12"
                    autocomplete="new-password" placeholder="Minimal 12 karakter"></div>
            <div><label class="field-label" for="password_confirm">Konfirmasi password</label>
                <input class="field-input" id="password_confirm" name="password_confirm" type="password"
                    required minlength="12" autocomplete="new-password" placeholder="Ulangi password"></div>
            <p class="field-help">Akun baru selalu memiliki role Tutor. Berikan password secara privat kepada pengajar.</p>
            <button type="submit" class="rounded-2xl bg-violet-600 text-white font-black px-6 py-3 hover:bg-violet-700">＋ Buat Akun Tutor</button>
        </form>
    </section>
    <section class="editor-panel">
        <h2 class="text-xl font-black">Daftar Tutor dan Admin</h2>
        <div class="overflow-x-auto mt-4"><table class="min-w-full text-sm text-left">
            <thead class="text-violet-700 border-b-2 border-violet-200"><tr>
                <th class="py-3 px-3">Nama</th><th class="py-3 px-3">Username</th>
                <th class="py-3 px-3">Role</th><th class="py-3 px-3">Status</th>
            </tr></thead><tbody>
            <?php foreach($rows as $row): ?><tr class="border-b border-violet-100">
                <td class="py-3 px-3 font-bold"><?= tutor_e($row['display_name']) ?></td>
                <td class="py-3 px-3"><?= tutor_e($row['username']) ?></td>
                <td class="py-3 px-3"><?= tutor_e($row['role']) ?></td>
                <td class="py-3 px-3"><?= (int)$row['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></td>
            </tr><?php endforeach; ?>
            </tbody></table></div>
    </section>
</main>
<?php tutor_layout_end(); ?>
</body></html>
