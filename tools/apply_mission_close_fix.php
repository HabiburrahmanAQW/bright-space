<?php
/**
 * BRIGHT SPACE - Hotfix Tahap 4: Konfirmasi tutup dan hapus sesi misi MySQL.
 * Kode aplikasi tidak diubah saat --check. Backup otomatis dibuat saat apply.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Jalankan dari terminal Laragon.');
}
$root = dirname(__DIR__);
$check = in_array('--check', $argv, true);

function replace_once(string $content, string $old, string $new, string $file): string
{
    $count = substr_count($content, $old);
    if ($count !== 1) {
        throw new RuntimeException("{$file}: penanda tidak cocok ({$count}x). Tidak ada file yang diubah.");
    }
    return str_replace($old, $new, $content);
}

function patch_player(string $source): string
{
    if (str_contains($source, 'name="bs-mission-csrf"') && str_contains($source, "\$action==='abandon'")) {
        return $source;
    }
    $source = replace_once(
        $source,
        '<title><?= play_e($mission[\'title\']) ?> — BRIGHT SPACE</title>',
        '<title><?= play_e($mission[\'title\']) ?> — BRIGHT SPACE</title>' . "\n" . '<meta name="bs-mission-csrf" content="<?= play_e($csrf) ?>">',
        'mainkan-misi.php'
    );
    $old = "    if(\$action==='restart'){";
    $new = <<<'PHP_ACTION'
    // Tombol X / backdrop / Escape: hapus sesi di server, bukan hanya menutup iframe.
    if($action==='abandon'){
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if(empty($_SESSION['bs_mission_run']) || (int)($_SESSION['bs_mission_run']['mission_id']??0)!==$missionId){
            http_response_code(409);
            echo json_encode(['ok'=>false,'error'=>'Sesi misi aktif sudah berubah.']);
            exit;
        }
        unset($_SESSION['bs_mission_run']);
        echo json_encode(['ok'=>true]);
        exit;
    }
    if($action==='restart'){
PHP_ACTION;
    return replace_once($source, $old, $new, 'mainkan-misi.php');
}

function patch_modal(string $source): string
{
    if (str_contains($source, 'async function confirmEndMission()')) {
        return $source;
    }
    $source = replace_once(
        $source,
        "  let oldOverflow = '';",
        "  let oldOverflow = '';\n  let endingMission = false;",
        'assets/js/student-mission-modal.js'
    );
    $insert = <<<'JS'
  async function confirmEndMission() {
    if (!dialog || endingMission) return;
    const confirmed = window.confirm(
      'Yakin ingin mengakhiri misi?\n\nSemua progres soal saat ini akan hangus, dan kamu harus mulai lagi dari soal pertama.'
    );
    if (!confirmed) return; // Jika Batal, sesi dan timer tetap berjalan.

    const currentFrame = frame;
    let csrf = '';
    try {
      // Iframe satu origin; token didapat dari halaman permainan saat ini.
      csrf = currentFrame?.contentDocument?.querySelector('meta[name="bs-mission-csrf"]')?.content || '';
    } catch (_) {
      csrf = '';
    }
    if (!csrf) {
      window.alert('Permainan belum selesai dimuat. Coba tekan X lagi sebentar lagi.');
      return;
    }

    endingMission = true;
    const closeButton = dialog.querySelector('[data-bs-mission-close]');
    if (closeButton) closeButton.disabled = true;
    currentFrame.style.pointerEvents = 'none';

    try {
      const response = await fetch(currentFrame.src, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'Accept': 'application/json'
        },
        body: new URLSearchParams({ csrf_token: csrf, action: 'abandon' })
      });
      if (!response.ok) throw new Error('Server gagal mengakhiri misi (HTTP ' + response.status + ').');
      const result = await response.json();
      if (result?.ok !== true) throw new Error('Server tidak mengonfirmasi pengakhiran sesi.');
      closeMission(); // Tutup modal hanya SETELAH sesi PHP berhasil dihapus.
    } catch (error) {
      window.alert('Sesi belum berhasil diakhiri. Modal tetap terbuka agar progres tidak hilang tanpa konfirmasi.\n\n' + error.message);
    } finally {
      endingMission = false;
      if (closeButton && dialog) closeButton.disabled = false;
      if (dialog && frame === currentFrame) currentFrame.style.pointerEvents = '';
    }
  }

JS;
    $source = replace_once(
        $source,
        '  function openMission(url, button) {',
        $insert . '  function openMission(url, button) {',
        'assets/js/student-mission-modal.js'
    );
    $source = replace_once(
        $source,
        '    if (dialog) closeMission();',
        '    if (dialog) return; // Jangan menutup sesi lain secara diam-diam.',
        'assets/js/student-mission-modal.js'
    );
    $source = replace_once(
        $source,
        "    close.title = 'Tutup permainan';",
        "    close.title = 'Akhiri permainan';\n    close.setAttribute('data-bs-mission-close', '1');",
        'assets/js/student-mission-modal.js'
    );
    $source = replace_once(
        $source,
        "    close.addEventListener('click', closeMission);",
        "    close.addEventListener('click', confirmEndMission);",
        'assets/js/student-mission-modal.js'
    );
    $source = replace_once(
        $source,
        '      if (event.target === dialog) closeMission();',
        '      if (event.target === dialog) confirmEndMission();',
        'assets/js/student-mission-modal.js'
    );
    $source = replace_once(
        $source,
        "    if (event.data && event.data.type === 'bs-mission-close') closeMission();",
        "    if (event.data && event.data.type === 'bs-mission-close') confirmEndMission();",
        'assets/js/student-mission-modal.js'
    );
    $source = replace_once(
        $source,
        "      closeMission();\n    }\n  });\n})();",
        "      confirmEndMission();\n    }\n  });\n})();",
        'assets/js/student-mission-modal.js'
    );
    return $source;
}

try {
    $files = [
        'mainkan-misi.php' => 'patch_player',
        'assets/js/student-mission-modal.js' => 'patch_modal',
    ];
    $old = [];
    $new = [];
    foreach ($files as $path => $fn) {
        $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (!is_file($full)) throw new RuntimeException("File {$path} tidak ditemukan. Pastikan Penyempurnaan Tahap 4 sudah terpasang.");
        $raw = file_get_contents($full);
        if ($raw === false) throw new RuntimeException("Gagal membaca {$path}.");
        $normalized = str_replace("\r\n", "\n", $raw);
        $patched = $fn($normalized);
        $old[$path] = $raw;
        $new[$path] = str_contains($raw, "\r\n") ? str_replace("\n", "\r\n", $patched) : $patched;
    }
    $changes = array_values(array_filter(array_keys($files), static fn($p) => $old[$p] !== $new[$p]));
    if (!$changes) {
        echo "Hotfix sudah terpasang. Tidak ada yang perlu diubah.\n";
        exit(0);
    }
    echo "File yang akan diubah:\n - " . implode("\n - ", $changes) . "\n";
    if ($check) {
        echo "--check BERHASIL. Belum ada file yang diubah.\n";
        exit(0);
    }

    $base = getenv('USERPROFILE') ?: (getenv('HOME') ?: sys_get_temp_dir());
    $backup = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'BRIGHT_SPACE_mission_close_backups' . DIRECTORY_SEPARATOR . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
    if (!mkdir($backup, 0700, true) && !is_dir($backup)) throw new RuntimeException('Gagal membuat folder backup.');
    foreach ($changes as $path) {
        $target = $backup . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0700, true)) throw new RuntimeException("Gagal membuat folder backup untuk {$path}.");
        if (file_put_contents($target, $old[$path], LOCK_EX) === false) throw new RuntimeException("Gagal backup {$path}.");
    }
    $written = [];
    try {
        foreach ($changes as $path) {
            $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (file_get_contents($full) !== $old[$path]) throw new RuntimeException("{$path} berubah selama proses; dibatalkan.");
            if (file_put_contents($full, $new[$path], LOCK_EX) !== strlen($new[$path])) throw new RuntimeException("Gagal menulis {$path}.");
            $written[] = $path;
        }
    } catch (Throwable $e) {
        foreach (array_reverse($written) as $path) {
            @file_put_contents($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path), $old[$path], LOCK_EX);
        }
        throw $e;
    }
    echo "Hotfix BERHASIL diterapkan.\nBackup: {$backup}\n";
    echo "Selanjutnya refresh planet-detail.php (Ctrl + F5) lalu uji tombol X.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "GAGAL: " . $e->getMessage() . "\n");
    exit(1);
}
