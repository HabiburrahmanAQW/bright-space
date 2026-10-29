<?php
/** Shared account service: only include after bs_admin_require_login(). */
require_once __DIR__.'/../config/registration.php';

function bs_admin_account_meta(string $kind): array {
    if($kind==='tutor')return ['table'=>'bs_tutors','filter'=>"role='tutor'",'label'=>'Tutor'];
    if($kind==='student')return ['table'=>'bs_students','filter'=>'1=1','label'=>'Siswa'];
    throw new InvalidArgumentException('Jenis akun tidak valid.');
}
function bs_admin_unique_username(PDO $pdo,string $name,string $kind,int $exclude=0): void {
    foreach(['tutor'=>'bs_tutors','student'=>'bs_students'] as $type=>$table){
        $sql="SELECT id FROM {$table} WHERE username=?";
        $args=[$name];
        if($exclude>0 && $kind===$type){$sql.=' AND id<>?';$args[]=$exclude;}
        $stmt=$pdo->prepare($sql.' LIMIT 1');
        $stmt->execute($args);
        if($stmt->fetchColumn()!==false)throw new DomainException('Username sudah dipakai akun lain.');
    }
}
function bs_admin_account_action(string $kind): array {
    $m=bs_admin_account_meta($kind);
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')return [];
    if(!tutor_valid_csrf($_POST['csrf_token']??null)){
        http_response_code(403);return ['Token keamanan tidak valid.'];
    }
    $action=is_string($_POST['action']??null)?$_POST['action']:'';
    if(!in_array($action,['create','update','disable','enable','reset_password'],true)){
        http_response_code(400);return ['Aksi akun tidak dikenal.'];
    }
    $id=filter_var($_POST['account_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
    if($action!=='create' && !$id)return ['ID akun tidak valid.'];
    $pdo=db();if(!$pdo)return ['Database belum terhubung.'];
    try{
        $secret=null;
        if($action==='create'||$action==='update'){
            [$identity,$errors]=bs_account_validate_identity($_POST);
            if($errors)return $errors;
            $pdo->beginTransaction();
            if($action==='update'){
                $st=$pdo->prepare("SELECT id FROM {$m['table']} WHERE id=? AND {$m['filter']} FOR UPDATE");
                $st->execute([$id]);if(!$st->fetchColumn())throw new DomainException('Akun tidak ditemukan.');
            }
            bs_admin_unique_username($pdo,$identity['username'],$kind,$action==='update'?$id:0);
            if($action==='create'){
                $password=bin2hex(random_bytes(12));
                $sql=$kind==='tutor'
                    ?"INSERT INTO bs_tutors(username,display_name,password_hash,role,is_active) VALUES(?,?,?,'tutor',1)"
                    :"INSERT INTO bs_students(username,display_name,password_hash,is_active) VALUES(?,?,?,1)";
                $st=$pdo->prepare($sql);
                $st->execute([$identity['username'],$identity['display_name'],password_hash($password,PASSWORD_DEFAULT)]);
                $secret=['username'=>$identity['username'],'password'=>$password];
                $message="Akun {$m['label']} berhasil dibuat.";
            }else{
                $st=$pdo->prepare("UPDATE {$m['table']} SET username=?,display_name=? WHERE id=? AND {$m['filter']}");
                $st->execute([$identity['username'],$identity['display_name'],$id]);
                $message='Data akun berhasil diperbarui.';
            }
            $pdo->commit();
        }else{
            $st=$pdo->prepare("SELECT id,username FROM {$m['table']} WHERE id=? AND {$m['filter']} LIMIT 1");
            $st->execute([$id]);$target=$st->fetch(PDO::FETCH_ASSOC);
            if(!$target)throw new DomainException('Akun tidak ditemukan.');
            if($action==='disable'||$action==='enable'){
                $value=$action==='enable'?1:0;
                $st=$pdo->prepare("UPDATE {$m['table']} SET is_active=? WHERE id=? AND {$m['filter']}");
                $st->execute([$value,$id]);
                $message=$value?'Akun diaktifkan.':'Akun dinonaktifkan. Data dan progres tetap tersimpan.';
            }else{
                $password=bin2hex(random_bytes(12));
                $st=$pdo->prepare("UPDATE {$m['table']} SET password_hash=? WHERE id=? AND {$m['filter']}");
                $st->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
                $secret=['username'=>$target['username'],'password'=>$password];
                $message='Password baru dibuat. Password lama tidak berlaku lagi.';
            }
        }
        $_SESSION['bs_admin_account_flash']=['kind'=>$kind,'message'=>$message,'secret'=>$secret];
        header('Location: '.($kind==='tutor'?'tutors.php':'students.php'),true,303);
        exit;
    }catch(DomainException $e){
        if($pdo->inTransaction())$pdo->rollBack();
        return [$e->getMessage()];
    }catch(PDOException $e){
        if($pdo->inTransaction())$pdo->rollBack();
        if((string)$e->getCode()==='23000')return ['Username tersebut sudah digunakan.'];
        error_log('Account admin SQL: '.$e->getMessage());
        return ['Gagal menyimpan akun. Periksa MySQL.'];
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        error_log('Account admin: '.$e->getMessage());
        return ['Operasi akun gagal. Periksa log server.'];
    }
}
function bs_admin_account_list(string $kind): array {
    $m=bs_admin_account_meta($kind);
    $page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:1;
    $page=min($page,10000);
    $search=trim((string)($_GET['q']??''));
    if(strlen($search)>60)$search=substr($search,0,60);
    $filter=$m['filter'];$params=[];
    if($search!==''){$filter.=' AND (username LIKE ? OR display_name LIKE ?)';$params=['%'.$search.'%','%'.$search.'%'];}
    $pdo=db();if(!$pdo)throw new RuntimeException('MySQL belum terhubung.');
    $s=$pdo->prepare("SELECT COUNT(*) FROM {$m['table']} WHERE $filter");
    $s->execute($params);$total=(int)$s->fetchColumn();
    $pages=max(1,(int)ceil($total/50));$page=min($pages,$page);
    $offset=($page-1)*50;
    $fields=$kind==='tutor'?'id,username,display_name,is_active':'id,username,display_name,is_active,level,total_xp';
    $s=$pdo->prepare("SELECT $fields FROM {$m['table']} WHERE $filter ORDER BY id DESC LIMIT 50 OFFSET $offset");
    $s->execute($params);
    return ['rows'=>$s->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'pages'=>$pages,'q'=>$search];
}
function bs_admin_account_flash(string $kind): ?array {
    $flash=$_SESSION['bs_admin_account_flash']??null;
    unset($_SESSION['bs_admin_account_flash']);
    return is_array($flash)&&($flash['kind']??'')===$kind?$flash:null;
}
