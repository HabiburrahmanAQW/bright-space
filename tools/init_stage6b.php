<?php
/** Bootstrap sekali agar soal published lama tetap dapat dimainkan. Idempotent. */
if(PHP_SAPI!=='cli')exit('Jalankan melalui Laragon Terminal.');
define('BRIGHT_SPACE',true);
require __DIR__.'/../config/database.php';
require __DIR__.'/../config/publication_repository.php';
$pdo=mission_db();
try{
    $pdo->beginTransaction();
    $query="SELECT m.id,m.mode_type,m.question_target FROM bs_missions m JOIN bs_planets p ON p.id=m.planet_id WHERE m.publication_status='published' AND p.publication_status='published' ORDER BY m.id FOR UPDATE";
    $missions=$pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    $ins=$pdo->prepare('INSERT IGNORE INTO bs_published_questions (mission_id,source_question_id,mode_type,prompt,payload_json,explanation,sort_order) VALUES (?,?,?,?,?,?,?)');
    $seeded=0;
    foreach($missions as $m){
        $id=(int)$m['id'];
        $exists=$pdo->prepare('SELECT COUNT(*) FROM bs_published_questions WHERE mission_id=?');
        $exists->execute([$id]);if((int)$exists->fetchColumn()>0)continue;
        // Sebelum fitur Update Publikasi, pemain hanya mengambil N soal pertama.
        // Karena itu bootstrap snapshot hanya N soal pertama, sisanya dinilai belum dipublish.
        $qs=bs_pub_live($id,(string)$m['mode_type']);
        foreach(array_slice($qs,0,max(0,(int)$m['question_target'])) as $q){
            $ins->execute([$id,$q['source_question_id'],$q['mode_type'],$q['prompt'],$q['payload_json'],$q['explanation'],$q['sort_order']]);$seeded++;
        }
    }
    $pdo->commit();echo "Bootstrap selesai: {$seeded} soal lama masuk snapshot. Aman dijalankan ulang.\n";
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,'GAGAL: '.$e->getMessage()."\n");exit(1);}
