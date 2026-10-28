<?php
/** BRIGHT SPACE Tahap 4 — bank soal milik tiap ID misi. */
defined('BRIGHT_SPACE') or exit('Tidak diizinkan.');
require_once __DIR__ . '/mission_repository.php';

function bmq_get_mission(int $id): ?array
{
    $stmt = mission_db()->prepare('SELECT m.*, p.name AS planet_name, p.required_level, p.publication_status AS planet_status, p.created_by
        FROM bs_missions m JOIN bs_planets p ON p.id = m.planet_id WHERE m.id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
function bmq_owned_mission(int $id, array $tutor): ?array
{
    $row = bmq_get_mission($id);
    if (!$row || ($tutor['role'] !== 'admin' && (int)$row['created_by'] !== (int)$tutor['id'])) return null;
    return $row;
}
function bmq_list(int $missionId, string $mode): array
{
    $stmt = mission_db()->prepare('SELECT id, mission_id, prompt, payload_json, explanation, sort_order FROM bs_mission_questions WHERE mission_id = ? AND mode_type = ? ORDER BY sort_order, id');
    $stmt->execute([$missionId, $mode]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) $r['payload'] = json_decode($r['payload_json'], true) ?: [];
    unset($r);
    return $rows;
}
function bmq_find(int $questionId, int $missionId, string $mode): ?array
{
    $stmt = mission_db()->prepare('SELECT id, mission_id, prompt, payload_json, explanation, sort_order FROM bs_mission_questions WHERE id = ? AND mission_id = ? AND mode_type = ?');
    $stmt->execute([$questionId, $missionId, $mode]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $row['payload'] = json_decode($row['payload_json'], true) ?: [];
    return $row ?: null;
}
function bmq_count(int $missionId, string $mode): int
{
    $stmt = mission_db()->prepare('SELECT COUNT(*) FROM bs_mission_questions WHERE mission_id = ? AND mode_type = ?');
    $stmt->execute([$missionId, $mode]);
    return (int)$stmt->fetchColumn();
}
function bmq_ready(int $missionId, string $mode, int $target): bool
{
    // BS_STAGE6B_PUBLIC_COUNT — siswa hanya melihat soal yang telah dipublikasikan.
    if (function_exists('bs_pub_player_count')) return $target >= 1 && bs_pub_player_count($missionId,$mode) >= $target;
    return $target >= 1 && bmq_count($missionId, $mode) >= $target;
}
function bmq_len(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}
function bmq_norm(string $value): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}
function bmq_same_puzzle_letters(string $answer, string $scramble): bool
{
    $clean = static fn(string $s): string => preg_replace('/\s+/u', '', bmq_norm($s)) ?? '';
    $a = preg_split('//u', $clean($answer), -1, PREG_SPLIT_NO_EMPTY);
    $b = preg_split('//u', $clean($scramble), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($a) || !is_array($b)) return false;
    sort($a); sort($b);
    return $a === $b;
}
/** Validasi berdasar gamemode, bukan berdasarkan jawaban kiriman browser. */
function bmq_validate(string $mode, array $input): array
{
    $errors = [];
    $field = static fn(string $key): string => isset($input[$key]) && is_string($input[$key]) ? trim($input[$key]) : '';
    $prompt = $field('prompt');
    $explain = $field('explanation');
    if ($prompt === '' || bmq_len($prompt) > 1500) $errors[] = 'Pertanyaan/skenario wajib diisi (maksimum 1500 karakter).';
    if (bmq_len($explain) > 1500) $errors[] = 'Penjelasan terlalu panjang (maksimum 1500 karakter).';
    $payload = [];
    if (in_array($mode, ['kuis','story','latihan'], true)) {
        $choices = [];
        foreach (['A','B','C'] as $key) {
            $choice = $field('choice_'.$key);
            if ($choice === '' || bmq_len($choice) > 300) $errors[] = "Pilihan {$key} harus diisi (maksimum 300 karakter).";
            $choices[$key] = $choice;
        }
        if (count(array_unique(array_map('bmq_norm', array_values($choices)))) !== 3) $errors[] = 'Pilihan jawaban harus berbeda.';
        $correct = $field('correct');
        if (!in_array($correct, ['A','B','C'], true)) $errors[] = 'Pilih satu kunci jawaban yang benar.';
        $payload = ['choices'=>$choices, 'correct'=>$correct];
    } elseif ($mode === 'puzzle') {
        $answer = $field('answer');
        $scramble = $field('scramble');
        $hint = $field('hint');
        if ($answer === '' || bmq_len($answer) > 100) $errors[] = 'Kunci kata wajib diisi (maksimum 100 karakter).';
        if ($scramble === '' || bmq_len($scramble) > 100) $errors[] = 'Huruf acak wajib diisi (maksimum 100 karakter).';
        if (bmq_len($hint) > 250) $errors[] = 'Petunjuk maksimal 250 karakter.';
        if ($answer !== '' && $scramble !== '' && !bmq_same_puzzle_letters($answer, $scramble)) {
            $errors[] = 'Huruf acak harus terdiri dari huruf yang sama dengan jawaban (urutan boleh berbeda).';
        }
        $payload = ['answer'=>$answer, 'scramble'=>$scramble, 'hint'=>$hint];
    } elseif ($mode === 'matching') {
        $pairs = [];
        for ($i=0;$i<3;$i++) {
            $left = is_string($input['left'][$i] ?? null) ? trim($input['left'][$i]) : '';
            $right = is_string($input['right'][$i] ?? null) ? trim($input['right'][$i]) : '';
            if ($left === '' || $right === '' || bmq_len($left)>120 || bmq_len($right)>120) $errors[] = 'Setiap pasangan wajib diisi, maksimal 120 karakter.';
            $pairs[] = ['left'=>$left, 'right'=>$right];
        }
        if (count(array_unique(array_map(fn($p)=>bmq_norm($p['left']),$pairs))) !== 3 || count(array_unique(array_map(fn($p)=>bmq_norm($p['right']),$pairs))) !== 3) $errors[] = 'Sisi kiri dan sisi kanan masing-masing harus berbeda.';
        $payload = ['pairs'=>$pairs];
    } else {
        $errors[] = 'Gamemode tidak dikenal.';
    }
    return [ ['prompt'=>$prompt, 'payload'=>$payload, 'explanation'=>$explain], array_values(array_unique($errors)) ];
}
