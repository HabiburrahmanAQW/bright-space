<?php
require __DIR__ . '/_auth.php';

try {
    if (tutor_current()) {
        header('Location: index.php');
        exit;
    }
} catch (Throwable $e) {
    // Akan ditampilkan saat POST, supaya GET tetap menampilkan petunjuk koneksi.
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!tutor_valid_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } elseif (strlen($username) > 40 || $username === '' || $password === '') {
        $error = 'Isi username dan password dengan benar.';
    } else {
        try {
            $pdo = db();
            if (!$pdo) {
                throw new RuntimeException('MySQL belum terhubung.');
            }
            $stmt = $pdo->prepare(
                "SELECT id, password_hash FROM bs_tutors
                 WHERE username = ? AND is_active = 1 AND role IN ('tutor', 'admin') LIMIT 1"
            );
            $stmt->execute([$username]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($account && password_verify($password, $account['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['tutor_id'] = (int) $account['id'];
                $_SESSION['tutor_csrf'] = bin2hex(random_bytes(32));
                header('Location: index.php');
                exit;
            }
            $error = 'Username atau password tidak sesuai.';
        } catch (Throwable $e) {
            error_log('Tutor login error: ' . $e->getMessage());
            $error = 'Database tutor belum siap. Periksa koneksi dan impor SQL.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Tutor - BRIGHT SPACE</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{fontFamily:{nunito:['Nunito','sans-serif']}}}};</script>
</head>
<body class="min-h-screen bg-sky-50 text-indigo-950 font-nunito antialiased flex items-center justify-center p-4">
  <main class="w-full max-w-md bg-white rounded-3xl border-4 border-violet-200 shadow-lg p-7 sm:p-9">
    <div class="text-4xl text-center" aria-hidden="true">🪐</div>
    <h1 class="text-center text-2xl font-black mt-3">BRIGHT SPACE</h1>
    <p class="text-center text-sm text-violet-600 font-extrabold mb-7">Portal Tutor</p>
    <?php if ($error !== ''): ?>
      <p role="alert" class="rounded-xl bg-red-50 border border-red-200 p-3 text-sm font-bold text-red-700 mb-4"><?= tutor_e($error) ?></p>
    <?php endif; ?>
    <form method="post" autocomplete="on" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= tutor_e(tutor_csrf_token()) ?>">
      <div><label for="username" class="block mb-1 font-extrabold text-sm">Username tutor</label>
        <input id="username" name="username" autocomplete="username" required maxlength="40" class="w-full rounded-xl border-2 border-violet-200 px-4 py-3 focus:outline-none focus:border-violet-500" value="<?= tutor_e($_POST['username'] ?? '') ?>"></div>
      <div><label for="password" class="block mb-1 font-extrabold text-sm">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required class="w-full rounded-xl border-2 border-violet-200 px-4 py-3 focus:outline-none focus:border-violet-500"></div>
      <button type="submit" class="w-full rounded-full bg-violet-600 hover:bg-violet-700 py-3 font-black text-white">Masuk sebagai Tutor</button>
    </form>
    <p class="mt-6 text-xs text-slate-500 text-center">Khusus tutor terdaftar. Login siswa tetap menggunakan halaman yang sudah ada.</p>
  </main>
</body>
</html>
