<?php
/** Saldo & inventaris siswa berdasarkan MySQL Tahap 5; tidak mempercayai localStorage/session lama. */
defined('BRIGHT_SPACE') or exit('Akses tidak diizinkan.');
require_once __DIR__.'/student_progress.php';
function bs_shop_student_id(): int {
    $u=bs_verified_student();
    if(!$u) throw new DomainException('Silakan login sebagai siswa terlebih dahulu.');
    return (int)$u['id'];
}
function bs_shop_catalog(): array {
    global $items;
    if (!isset($items)) require_once __DIR__.'/planets.php';
    return array_column($items ?? [],null,'id');
}
function bs_shop_csrf(): string {
    if(empty($_SESSION['bs_shop_csrf']))$_SESSION['bs_shop_csrf']=bin2hex(random_bytes(32));
    return $_SESSION['bs_shop_csrf'];
}
function bs_shop_valid_csrf($input): bool { return is_string($input) && hash_equals(bs_shop_csrf(),$input); }
function bs_shop_owned(int $studentId): array {
    $stmt=mission_db()->prepare('SELECT item_id,is_equipped,purchased_at FROM bs_student_items WHERE student_id=? ORDER BY purchased_at,item_id');
    $stmt->execute([$studentId]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function bs_shop_state(int $studentId): array {
    $catalog=bs_shop_catalog();$owned=[];$topi=null;$kacamata=null;
    foreach(bs_shop_owned($studentId) as $row){
        $item=$catalog[$row['item_id']]??null;
        if(!$item)continue;
        $owned[]=array_merge($item,['purchased_at'=>$row['purchased_at']]);
        if((int)$row['is_equipped']===1){
            if(($item['slot']??null)==='topi')$topi=$item['id'];
            if(($item['slot']??null)==='kacamata')$kacamata=$item['id'];
        }
    }
    $stmt=mission_db()->prepare('SELECT star_coins,total_xp,level FROM bs_students WHERE id=? AND is_active=1');
    $stmt->execute([$studentId]);$s=$stmt->fetch(PDO::FETCH_ASSOC);
    if(!$s)throw new RuntimeException('Akun siswa tidak ditemukan.');
    return ['coins'=>(int)$s['star_coins'],'collection'=>$owned,'topi'=>$topi,'kacamata'=>$kacamata];
}
function bs_shop_buy(int $studentId,string $itemId): int {
    $catalog=bs_shop_catalog();$item=$catalog[$itemId]??null;
    if(!$item)throw new DomainException('Item tidak tersedia.');
    $price=(int)$item['harga'];if($price<0)throw new RuntimeException('Harga item tidak valid.');
    $pdo=mission_db();$pdo->beginTransaction();
    try {
        $stmt=$pdo->prepare('SELECT star_coins FROM bs_students WHERE id=? AND is_active=1 FOR UPDATE');
        $stmt->execute([$studentId]);$balance=$stmt->fetchColumn();
        if($balance===false)throw new DomainException('Akun siswa tidak aktif.');
        $stmt=$pdo->prepare('SELECT 1 FROM bs_student_items WHERE student_id=? AND item_id=?');
        $stmt->execute([$studentId,$itemId]);
        if($stmt->fetchColumn())throw new DomainException('Item sudah dimiliki.');
        if((int)$balance<$price)throw new DomainException('Koin tidak cukup untuk membeli item ini.');
        $stmt=$pdo->prepare('INSERT INTO bs_student_items (student_id,item_id,is_equipped) VALUES (?,?,0)');
        $stmt->execute([$studentId,$itemId]);
        $stmt=$pdo->prepare('UPDATE bs_students SET star_coins=star_coins-? WHERE id=? AND star_coins>=?');
        $stmt->execute([$price,$studentId,$price]);
        if($stmt->rowCount()!==1)throw new RuntimeException('Saldo berubah, pembelian dibatalkan.');
        $pdo->commit();return (int)$balance-$price;
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function bs_shop_set_equipped(int $studentId,string $itemId,bool $equip): void {
    $catalog=bs_shop_catalog();$item=$catalog[$itemId]??null;$slot=$item['slot']??null;
    if(!in_array($slot,['topi','kacamata'],true))throw new DomainException('Item tidak dapat dipakai.');
    $pdo=mission_db();$pdo->beginTransaction();
    try {
        $stmt=$pdo->prepare('SELECT id FROM bs_students WHERE id=? AND is_active=1 FOR UPDATE');
        $stmt->execute([$studentId]);if(!$stmt->fetchColumn())throw new DomainException('Akun tidak aktif.');
        $stmt=$pdo->prepare('SELECT item_id FROM bs_student_items WHERE student_id=? AND item_id=? FOR UPDATE');
        $stmt->execute([$studentId,$itemId]);if(!$stmt->fetchColumn())throw new DomainException('Item belum dimiliki.');
        if($equip){
            foreach($catalog as $other){
                if(($other['slot']??null)===$slot && $other['id']!==$itemId){
                    $stmt=$pdo->prepare('UPDATE bs_student_items SET is_equipped=0 WHERE student_id=? AND item_id=?');
                    $stmt->execute([$studentId,$other['id']]);
                }
            }
        }
        $stmt=$pdo->prepare('UPDATE bs_student_items SET is_equipped=? WHERE student_id=? AND item_id=?');
        $stmt->execute([$equip?1:0,$studentId,$itemId]);
        $pdo->commit();
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
