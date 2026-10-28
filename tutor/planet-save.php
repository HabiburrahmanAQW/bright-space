<?php
/** Simpan planet + banyak misi secara atomik. */
require __DIR__ . '/_planet-editor.php';
$tutor = tutor_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak didukung.');
}
if (!tutor_valid_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Sesi kedaluwarsa. Kembali ke form dan muat ulang.');
}
$oldId = planet_editor_string($_POST, 'original_id');
$isEdit = $oldId !== '';
if ($isEdit && (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $oldId) || strlen($oldId) > 64)) {
    http_response_code(400);
    exit('ID planet tidak valid.');
}
$target = 'planet-form.php' . ($isEdit ? '?id=' . rawurlencode($oldId) : '');
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 100000) {
    planet_editor_flash($target, ['Data formulir terlalu besar.'], []);
}

$requestedAction = planet_editor_string($_POST, 'save_action');
[$input, $errors] = planet_editor_validate($_POST, $isEdit);
if ($isEdit && $oldId !== $input['id']) {
    $errors[] = 'Slug planet tidak dapat diubah setelah dibuat.';
}
if ($errors) {
    planet_editor_flash($target, $errors, $_POST);
}

$pdo = null;
try {
    $pdo = mission_db();
    $pdo->beginTransaction();
    if ($isEdit) {
        $existing = planet_editor_owned($oldId, $tutor, true);
        if (!$existing) {
            $pdo->rollBack();
            http_response_code(404);
            exit('Planet tidak ditemukan atau tidak diizinkan untuk diedit.');
        }
        // Editing content must not silently unpublish an already published planet.
        // Publication changes are controlled by tutor/planet-publication.php.
        $wasPublished = $existing['publication_status'] === 'published';
        $stmt = $pdo->prepare(
            'UPDATE bs_planets SET name = ?, description = ?, required_level = ?, theme_key = ?, icon_key = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $input['name'], $input['description'], $input['required_level'],
            $input['theme_key'], $input['icon_key'], $oldId,
        ]);
        $statement = $pdo->prepare(
            'SELECT id, mode_type, publication_status FROM bs_missions WHERE planet_id = ? FOR UPDATE'
        );
        $statement->execute([$oldId]);
        $existingMissions = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existingMissions[(int) $row['id']] = $row;
        }
        $existingMissionIds = array_fill_keys(array_keys($existingMissions), true);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO bs_planets (id, name, description, required_level, theme_key, icon_key, publication_status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $input['id'], $input['name'], $input['description'], $input['required_level'],
            $input['theme_key'], $input['icon_key'], $input['publication_status'], (int) $tutor['id'],
        ]);
        $existingMissionIds = [];
        $existingMissions = [];
        $wasPublished = false;
    }

    $planetId = $input['id'];
    $retained = [];
    $update = $pdo->prepare(
        'UPDATE bs_missions SET category = ?, mode_type = ?, title = ?, description = ?, reward_coins = ?,
         timer_seconds = ?, question_target = ?, sort_order = ?, publication_status = ? WHERE id = ? AND planet_id = ?'
    );
    $insert = $pdo->prepare(
        'INSERT INTO bs_missions (planet_id, category, mode_type, title, description, reward_coins,
         timer_seconds, question_target, sort_order, publication_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($input['missions'] as $index => $m) {
        $order = $index + 1;
        if ($m['id'] !== null) {
            if (!isset($existingMissionIds[$m['id']])) {
                throw new DomainException('Ada ID misi yang tidak milik planet ini. Muat ulang formulir.');
            }
            $previousMission = $existingMissions[$m['id']];
            // A mode change would instantly invalidate the student's published question snapshot.
            if ($wasPublished && $previousMission['publication_status'] === 'published'
                && $previousMission['mode_type'] !== $m['mode_type']) {
                throw new DomainException(
                    'Jenis permainan pada misi terpublikasi tidak boleh diubah langsung. '
                    . 'Kembalikan planet ke Draft terlebih dahulu.'
                );
            }
            $update->execute([
                $m['category'], $m['mode_type'], $m['title'], $m['description'],
                $m['reward_coins'], $m['timer_seconds'], $m['question_target'],
                $order, $previousMission['publication_status'], $m['id'], $planetId,
            ]);
            $retained[$m['id']] = true;
        } else {
            $insert->execute([
                $planetId, $m['category'], $m['mode_type'], $m['title'], $m['description'],
                $m['reward_coins'], $m['timer_seconds'], $m['question_target'],
                $order, $input['publication_status'],
            ]);
        }
    }
    if ($isEdit) {
        $remove = $pdo->prepare('DELETE FROM bs_missions WHERE id = ? AND planet_id = ?');
        foreach ($existingMissionIds as $id => $_) {
            if (!isset($retained[$id])) {
                // Deleting a published mission would also cascade-delete its live snapshot and progress.
                if ($wasPublished && $existingMissions[$id]['publication_status'] === 'published') {
                    throw new DomainException(
                        'Misi terpublikasi belum dapat dihapus langsung. '
                        . 'Kembalikan planet ke Draft sebelum menghapus misi.'
                    );
                }
                $remove->execute([(int) $id, $planetId]);
            }
        }
    }
    $pdo->commit();
    $_SESSION['planet_saved_message'] = $wasPublished
        ? 'Perubahan tersimpan. Planet tetap Published. Jika ada perubahan soal, buka Kelola Soal Misi lalu pilih Update Publikasi.'
        : 'Planet dan misinya disimpan sebagai Draft. Kelola soal lalu publikasikan dari daftar misi.';
    unset($_SESSION['planet_editor_flash']);
    header('Location: ' . ($requestedAction === 'draft_next' ? 'mission-list.php?planet=' : 'planet-form.php?id=') . rawurlencode($planetId), true, 303);
    exit;
} catch (DomainException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    planet_editor_flash($target, [$e->getMessage()], $_POST);
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('BRIGHT SPACE planet save SQL error: ' . $e->getMessage());
    $message = $e->getCode() === '23000'
        ? 'Slug sudah digunakan atau data bertabrakan dengan relasi database.'
        : 'Gagal menyimpan ke MySQL. Periksa koneksi dan tabel Tahap 1.';
    planet_editor_flash($target, [$message], $_POST);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log('BRIGHT SPACE planet save error: ' . $e->getMessage());
    planet_editor_flash($target, ['Terjadi kesalahan saat menyimpan. Periksa log PHP.'], $_POST);
}
