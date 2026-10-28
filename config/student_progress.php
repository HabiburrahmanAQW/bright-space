<?php
/** Tahap 5: ekonomi misi MySQL, autentikasi siswa, penilaian dan pemberian reward SERVER-SIDE. */
defined('BRIGHT_SPACE') or exit('Tidak diizinkan.');
require_once __DIR__ . '/mission_repository.php';

function bs_student_by_id(int $id): ?array {
    if ($id < 1 || !($pdo = db())) return null;
    $stmt=$pdo->prepare('SELECT id, username, display_name, star_coins, total_xp, level, is_active FROM bs_students WHERE id=? LIMIT 1');
    $stmt->execute([$id]);return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
function bs_verified_student(): ?array {
    $id=(int)($_SESSION['bs_student_id']??0);
    if ($id < 1 || $id !== (int)($_SESSION['user_id']??0)) return null;
    $s=bs_student_by_id($id);
    return ($s && (int)$s['is_active']===1) ? $s : null;
}
function bs_score_reward(string $mode,int $baseCoins,int $correct,int $total): array {
    if ($total < 1 || $correct < 0 || $correct > $total) throw new InvalidArgumentException('Skor tidak valid.');
    // Latihan: tanpa koin, sesuai katalog mode. Reward sekali per misi.
    $coins=$mode==='latihan' ? 0 : (int)floor(max(0,$baseCoins) * $correct / $total);
    $xp=10 + 5 * $correct;
    return ['coins'=>$coins,'xp'=>$xp];
}
function bs_level_for_xp(int $xp): int { return min(1000,1+intdiv(max(0,$xp),100)); }

/**
 * Commit atomik hasil terverifikasi oleh server (JANGAN terima nilai skor dari client).
 * PRIMARY KEY(student_id,mission_id) mencegah farming dari refresh/tab/perangkat lain.
 * Semua percobaan disimpan; reward koin/XP hanya pada penyelesaian pertama.
 */
function bs_finish_mission(int $studentId,int $missionId,int $correct,int $total,string $reason): array {
    if (!in_array($reason,['completed','timeout'],true) || $studentId<1 || $missionId<1 || $total<1 || $correct<0 || $correct>$total)
        throw new InvalidArgumentException('Data penyelesaian tidak valid.');
    $pdo=mission_db();$pdo->beginTransaction();
    try {
        $stmt=$pdo->prepare('SELECT id,star_coins,total_xp FROM bs_students WHERE id=? AND is_active=1 FOR UPDATE');
        $stmt->execute([$studentId]);$student=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$student)throw new RuntimeException('Akun siswa tidak aktif.');
        $stmt=$pdo->prepare('SELECT mode_type,reward_coins,publication_status,question_target FROM bs_missions WHERE id=? FOR UPDATE');
        $stmt->execute([$missionId]);$mission=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$mission || $mission['publication_status']!=='published')throw new RuntimeException('Misi tidak tersedia lagi.');
        if($total !== (int)$mission['question_target'])throw new RuntimeException('Jumlah soal sudah berubah. Silakan mulai lagi.');
        $reward=bs_score_reward((string)$mission['mode_type'],(int)$mission['reward_coins'],$correct,$total);
        $stmt=$pdo->prepare('INSERT IGNORE INTO bs_student_mission_progress
         (student_id,mission_id,attempts_count,best_correct,best_total,first_reward_coins,first_reward_xp)
         VALUES (?,?,1,?,?,?,?)');
        $stmt->execute([$studentId,$missionId,$correct,$total,$reward['coins'],$reward['xp']]);
        $first=$stmt->rowCount()===1;
        $givenCoins=$first?$reward['coins']:0;
        $givenXp=$first?$reward['xp']:0;
        if(!$first) {
            $stmt=$pdo->prepare('UPDATE bs_student_mission_progress
                SET attempts_count=attempts_count+1, best_correct=GREATEST(best_correct,?),
                    best_total=GREATEST(best_total,?),last_completed_at=NOW()
                WHERE student_id=? AND mission_id=?');
            $stmt->execute([$correct,$total,$studentId,$missionId]);
        }
        $newCoins=(int)$student['star_coins']+$givenCoins;
        $newXp=(int)$student['total_xp']+$givenXp;
        $newLevel=bs_level_for_xp($newXp);
        if($first) {
            $stmt=$pdo->prepare('UPDATE bs_students SET star_coins=?,total_xp=?,level=? WHERE id=?');
            $stmt->execute([$newCoins,$newXp,$newLevel,$studentId]);
        }
        $stmt=$pdo->prepare('INSERT INTO bs_mission_attempts
          (student_id,mission_id,correct_count,question_count,finished_reason,awarded_coins,awarded_xp)
          VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$studentId,$missionId,$correct,$total,$reason,$givenCoins,$givenXp]);
        $pdo->commit();
        return ['first'=>$first,'coins'=>$givenCoins,'xp'=>$givenXp,'balance'=>$newCoins,'total_xp'=>$newXp,'level'=>$newLevel];
    } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack();throw $e; }
}
