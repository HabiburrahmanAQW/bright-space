<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/question_repository.php';
$tutor = tutor_require_teacher();
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit('POST diperlukan'); }
if (!tutor_valid_csrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Token keamanan tidak valid.'); }
$missionId = filter_var($_POST['mission_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
$questionId = filter_var($_POST['question_id'] ?? 0, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
$mission = $missionId ? bmq_owned_mission((int)$missionId, $tutor) : null;
if (!$mission) { http_response_code(404); exit('Misi tidak ditemukan.'); }
$action = (string)($_POST['action'] ?? '');
$back = 'questions.php?mission=' . $missionId;
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 30000) { http_response_code(413); exit('Formulir terlalu besar.'); }
try {
    $pdo=mission_db();
    if ($action==='delete') {
        if (!$questionId || !bmq_find($questionId,$missionId,$mission['mode_type'])) { http_response_code(404); exit('Soal tidak ditemukan.'); }
        $stmt=$pdo->prepare('DELETE FROM bs_mission_questions WHERE id=? AND mission_id=? AND mode_type=?');
        $stmt->execute([$questionId,$missionId,$mission['mode_type']]);
        header('Location: '.$back, true,303);exit;
    }
    if ($action!=='save') { http_response_code(400); exit('Aksi tidak dikenal.'); }
    if ($questionId && !bmq_find($questionId,$missionId,$mission['mode_type'])) { http_response_code(404); exit('Soal tidak ditemukan.'); }
    [$data,$errors]=bmq_validate($mission['mode_type'],$_POST);
    if (!$questionId && bmq_count($missionId,$mission['mode_type'])>=50) $errors[]='Maksimal 50 soal per misi.';
    if ($errors) {
        $_SESSION['bmq_old']=$_POST;
        $_SESSION['bmq_errors']=$errors;
        header('Location: '.$back.($questionId?'&edit='.$questionId:''),true,303);exit;
    }
    $json=json_encode($data['payload'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    if ($questionId) {
        $stmt=$pdo->prepare('UPDATE bs_mission_questions SET prompt=?,payload_json=?,explanation=? WHERE id=? AND mission_id=? AND mode_type=?');
        $stmt->execute([$data['prompt'],$json,$data['explanation'],$questionId,$missionId,$mission['mode_type']]);
    } else {
        $stmt=$pdo->prepare('INSERT INTO bs_mission_questions(mission_id,mode_type,prompt,payload_json,explanation,sort_order) VALUES(?,?,?,?,?,?)');
        $stmt->execute([$missionId,$mission['mode_type'],$data['prompt'],$json,$data['explanation'],bmq_count($missionId,$mission['mode_type'])+1]);
    }
    header('Location: '.$back,true,303);exit;
} catch(Throwable $e) {
    error_log('Tahap 4 question action: '.$e->getMessage());
    http_response_code(500);exit('Penyimpanan gagal. Periksa tabel bs_mission_questions dan log Laragon.');
}
