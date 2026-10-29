<?php
/** Publikasi Tahap 6B: CSRF, ownership, transaksi, snapshot. */
require __DIR__.'/_auth.php';
require_once __DIR__.'/../config/publication_repository.php';
$tutor=tutor_require_teacher();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('POST diperlukan.');}
if(!tutor_valid_csrf($_POST['csrf_token']??null)){http_response_code(403);exit('Token tidak valid.');}
$id=is_string($_POST['planet_id']??null)?trim($_POST['planet_id']):'';
if(strlen($id)>64||!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$id)){http_response_code(400);exit('ID planet tidak valid.');}
$action=(string)($_POST['action']??'');
try{
    $result=bs_pub_apply($id,$tutor,$action);
    $_SESSION['bs_publication_flash']=['ok'=>true,'message'=>$result['message']];
}catch(DomainException $e){$_SESSION['bs_publication_flash']=['ok'=>false,'message'=>$e->getMessage()];}
catch(Throwable $e){error_log('Publikasi 6B: '.$e->getMessage());$_SESSION['bs_publication_flash']=['ok'=>false,'message'=>'Publikasi gagal. Periksa MySQL/log Laragon.'];}
header('Location: mission-list.php?planet='.rawurlencode($id),true,303);exit;
