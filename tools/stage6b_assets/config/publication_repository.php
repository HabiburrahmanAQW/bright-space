<?php
/** Snapshot soal untuk siswa. Tutor tetap mengedit tabel bs_mission_questions. */
defined('BRIGHT_SPACE') or exit('Akses ditolak.');
require_once __DIR__.'/question_repository.php';
function bs_pub_live(int $missionId,string $mode): array {
    $stmt=mission_db()->prepare('SELECT id AS source_question_id,mode_type,prompt,payload_json,explanation,sort_order FROM bs_mission_questions WHERE mission_id=? AND mode_type=? ORDER BY sort_order,id');
    $stmt->execute([$missionId,$mode]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function bs_pub_snapshot(int $missionId,string $mode): array {
    $stmt=mission_db()->prepare('SELECT source_question_id,mode_type,prompt,payload_json,explanation,sort_order FROM bs_published_questions WHERE mission_id=? AND mode_type=? ORDER BY sort_order,source_question_id');
    $stmt->execute([$missionId,$mode]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function bs_pub_signature(array $list): string {
    // Urutan dan isi dibandingkan; timestamp tak relevan.
    return hash('sha256',json_encode(array_map(static fn($q)=>[
        (int)$q['source_question_id'],(string)$q['mode_type'],(string)$q['prompt'],
        json_decode((string)$q['payload_json'],true), (string)$q['explanation'],(int)$q['sort_order']
    ],$list),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
}
function bs_pub_status(string $planetId): array {
    $stmt=mission_db()->prepare('SELECT id,mode_type,question_target,publication_status FROM bs_missions WHERE planet_id=? ORDER BY sort_order,id');
    $stmt->execute([$planetId]);$missions=$stmt->fetchAll(PDO::FETCH_ASSOC);
    $pending=0;$liveTotal=0;$snapTotal=0;
    foreach($missions as $m){
        $id=(int)$m['id'];$mode=(string)$m['mode_type'];
        $live=bs_pub_live($id,$mode);$snap=bs_pub_snapshot($id,$mode);
        $liveTotal+=count($live);$snapTotal+=count($snap);
        if(bs_pub_signature($live)!==bs_pub_signature($snap) || $m['publication_status']!=='published')$pending++;
    }
    return ['pending_missions'=>$pending,'live_questions'=>$liveTotal,'published_questions'=>$snapTotal,'missions'=>count($missions),'has_changes'=>$pending>0];
}
function bs_pub_player_list(int $missionId,string $mode): array {
    $rows=bs_pub_snapshot($missionId,$mode);
    $result=[];
    foreach($rows as $r){$r['id']=(int)$r['source_question_id'];$r['mission_id']=$missionId;$r['payload']=json_decode($r['payload_json'],true)?:[];$result[]=$r;}
    return $result;
}
function bs_pub_player_count(int $missionId,string $mode): int {
    $stmt=mission_db()->prepare('SELECT COUNT(*) FROM bs_published_questions WHERE mission_id=? AND mode_type=?');
    $stmt->execute([$missionId,$mode]);return (int)$stmt->fetchColumn();
}
/** Publish/update atomik; saat bank soal > target, target dinaikkan supaya soal baru dapat dimainkan. */
function bs_pub_apply(string $planetId,array $tutor,string $action): array {
    if(!in_array($action,['publish','update','draft'],true))throw new DomainException('Aksi publikasi tidak dikenal.');
    $pdo=mission_db();$pdo->beginTransaction();
    try{
        $stmt=$pdo->prepare('SELECT id,created_by,publication_status FROM bs_planets WHERE id=? FOR UPDATE');
        $stmt->execute([$planetId]);$planet=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$planet||($tutor['role']!=='admin'&&(int)$planet['created_by']!==(int)$tutor['id']))throw new DomainException('Planet bukan milik tutor ini.');
        if($action==='update' && $planet['publication_status']!=='published')throw new DomainException('Planet belum pernah dipublikasikan.');
        if($action==='draft'){
            $pdo->prepare("UPDATE bs_planets SET publication_status='draft' WHERE id=?")->execute([$planetId]);
            $pdo->prepare("UPDATE bs_missions SET publication_status='draft' WHERE planet_id=?")->execute([$planetId]);
            $pdo->commit();return ['message'=>'Planet dikembalikan menjadi Draft. Snapshot terakhir disimpan untuk publikasi berikutnya.'];
        }
        $stmt=$pdo->prepare('SELECT id,mode_type,question_target FROM bs_missions WHERE planet_id=? ORDER BY id FOR UPDATE');
        $stmt->execute([$planetId]);$missions=$stmt->fetchAll(PDO::FETCH_ASSOC);
        $all=0;$inserts=[];
        foreach($missions as $m){
            $qs=bs_pub_live((int)$m['id'],(string)$m['mode_type']);$all+=count($qs);
            $inserts[(int)$m['id']]=$qs;
        }
        if($all<1)throw new DomainException('Buat minimal satu soal sebelum mempublikasikan planet.');
        // Perubahan snapshot bersifat atomik. Tidak boleh hanya sebagian mission ter-update.
        $del=$pdo->prepare('DELETE FROM bs_published_questions WHERE mission_id=?');
        $ins=$pdo->prepare('INSERT INTO bs_published_questions (mission_id,source_question_id,mode_type,prompt,payload_json,explanation,sort_order) VALUES(?,?,?,?,?,?,?)');
        $targetUpdate=$pdo->prepare('UPDATE bs_missions SET question_target=GREATEST(question_target,?),publication_status=\'published\' WHERE id=?');
        foreach($missions as $m){
            $id=(int)$m['id'];$del->execute([$id]);
            foreach($inserts[$id] as $q){$ins->execute([$id,$q['source_question_id'],$q['mode_type'],$q['prompt'],$q['payload_json'],$q['explanation'],$q['sort_order']]);}
            $targetUpdate->execute([count($inserts[$id]),$id]);
        }
        $pdo->prepare("UPDATE bs_planets SET publication_status='published' WHERE id=?")->execute([$planetId]);
        $pdo->commit();return ['message'=>($action==='update'?'Publikasi diperbarui!':'Planet berhasil dipublikasikan!').' '.$all.' soal telah disinkronkan untuk siswa.'];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
