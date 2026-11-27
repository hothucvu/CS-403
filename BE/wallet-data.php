<?php
require_once __DIR__ . '/bao-helpers.php';
$userId=bao_require_login();$walletError='';$walletBalance=0;$walletRows=[];$walletUsers=[];
$isWalletAdmin=$pageToLoad==='pages/admin-wallet.php';
if($isWalletAdmin) bao_require_admin($conn);
try {
  if($isWalletAdmin && $_SERVER['REQUEST_METHOD']==='POST') {
    if(!bao_csrf_ok()) throw new RuntimeException('Phiên thao tác không hợp lệ.');
    $target=bao_int($_POST['user_id']??null);
    $change=filter_var($_POST['change_amount']??null,FILTER_VALIDATE_INT);
    $note=trim((string)($_POST['note']??''));
    if(!$target || $change===false || $change===0 || abs($change)>100000 || mb_strlen($note)<3 || mb_strlen($note)>255) throw new RuntimeException('Thông tin điều chỉnh điểm không hợp lệ.');
    $conn->begin_transaction();
    try {
      $s=$conn->prepare('SELECT barter_wallet FROM users WHERE id=? FOR UPDATE');$s->bind_param('i',$target);$s->execute();$value=$s->get_result()->fetch_column();$s->close();
      if($value===false) throw new RuntimeException('Người dùng không tồn tại.');
      $old=(int)$value;$new=$old+$change;
      if($new<0 || $new>1000000000) throw new RuntimeException('Điểm sau điều chỉnh vượt giới hạn.');
      $s=$conn->prepare('UPDATE users SET barter_wallet=? WHERE id=?');$s->bind_param('ii',$new,$target);$s->execute();$s->close();
      $s=$conn->prepare('INSERT INTO wallet_transactions(user_id,actor_id,change_amount,balance_before,balance_after,note) VALUES(?,?,?,?,?,?)');
      $s->bind_param('iiiiis',$target,$userId,$change,$old,$new,$note);$s->execute();$s->close();
      $conn->commit();bao_flash('success','Đã cập nhật điểm cho tài khoản #'.$target.'.');bao_redirect('admin-wallet');
    } catch(Throwable $e){$conn->rollback();throw $e;}
  }
  $s=$conn->prepare('SELECT barter_wallet FROM users WHERE id=?');$s->bind_param('i',$userId);$s->execute();$walletBalance=(int)$s->get_result()->fetch_column();$s->close();
  $s=$conn->prepare('SELECT change_amount,balance_after,note,created_at FROM wallet_transactions WHERE user_id=? ORDER BY id DESC LIMIT 50');
  $s->bind_param('i',$userId);$s->execute();$walletRows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
  if($isWalletAdmin) $walletUsers=$conn->query('SELECT id,fullname,email,barter_wallet FROM users ORDER BY id DESC LIMIT 150')->fetch_all(MYSQLI_ASSOC);
} catch(Throwable $e){error_log('Wallet: '.$e->getMessage());$walletError=$e instanceof RuntimeException?$e->getMessage():'Không tải được điểm. Đã chạy migration 001 chưa?';}
