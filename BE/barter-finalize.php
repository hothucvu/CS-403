<?php
// Được require từ BE/barter-data.php sau khi CSRF hợp lệ.
$requestId=filter_var($_POST['request_id']??null,FILTER_VALIDATE_INT);
if (!$requestId || $requestId<1) {
    $barterError='Mã yêu cầu không hợp lệ.';
} else {
    $conn->begin_transaction();
    try {
      $s=$conn->prepare("SELECT * FROM barter_requests WHERE id=? FOR UPDATE");
      $s->bind_param('i',$requestId);$s->execute();$request=$s->get_result()->fetch_assoc();$s->close();
      if (!$request || $request['status']!=='accepted' ||
          !in_array($userId,[(int)$request['sender_id'],(int)$request['receiver_id']],true)) {
          throw new RuntimeException('Yêu cầu chưa được chấp nhận hoặc bạn không có quyền xác nhận.');
      }
      if ((float)$request['compensation_amount']!==0.0) {
          throw new RuntimeException('Bản demo chỉ hoàn tất trao đổi không bù tiền/điểm.');
      }
      $s=$conn->prepare('INSERT IGNORE INTO barter_confirmations(barter_request_id) VALUES(?)');
      $s->bind_param('i',$requestId);$s->execute();$s->close();
      $field=$userId===(int)$request['sender_id']?'sender_confirmed':'receiver_confirmed';
      $s=$conn->prepare("UPDATE barter_confirmations SET $field=1 WHERE barter_request_id=?");
      $s->bind_param('i',$requestId);$s->execute();$s->close();
      $s=$conn->prepare('SELECT sender_confirmed,receiver_confirmed FROM barter_confirmations WHERE barter_request_id=? FOR UPDATE');
      $s->bind_param('i',$requestId);$s->execute();$conf=$s->get_result()->fetch_assoc();$s->close();
      $done=(bool)$conf['sender_confirmed'] && (bool)$conf['receiver_confirmed'];
      if ($done) {
        $ids=[(int)$request['offered_product_id'],(int)$request['requested_product_id']];sort($ids,SORT_NUMERIC);
        $s=$conn->prepare('SELECT id,user_id,status,moderation_status,transaction_type FROM products WHERE id IN (?,?) ORDER BY id FOR UPDATE');
        $s->bind_param('ii',$ids[0],$ids[1]);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        $products=[];foreach($rows as $r) $products[(int)$r['id']]=$r;
        $offer=$products[(int)$request['offered_product_id']]??null;
        $wanted=$products[(int)$request['requested_product_id']]??null;
        foreach([$offer,$wanted] as $row) {
          if (!$row || $row['status']!=='active' || $row['moderation_status']!=='approved' ||
             !in_array($row['transaction_type'],['barter','both'],true)) {
             throw new RuntimeException('Một sản phẩm đã thay đổi, không thể hoàn tất trao đổi.');
          }
        }
        if ((int)$offer['user_id']!==(int)$request['sender_id'] || (int)$wanted['user_id']!==(int)$request['receiver_id']) {
          throw new RuntimeException('Chủ sở hữu đã thay đổi, không thể hoàn tất.');
        }
        // Đổi chủ và đánh dấu đã trao đổi để không còn được bán/đổi lần nữa.
        $sender=(int)$request['sender_id'];$receiver=(int)$request['receiver_id'];
        $offerId=(int)$request['offered_product_id'];$wantId=(int)$request['requested_product_id'];
        $s=$conn->prepare("UPDATE products SET user_id=?,status='bartered' WHERE id=? AND user_id=? AND status='active'");
        $s->bind_param('iii',$receiver,$offerId,$sender);$s->execute();$a=$s->affected_rows;$s->close();
        $s=$conn->prepare("UPDATE products SET user_id=?,status='bartered' WHERE id=? AND user_id=? AND status='active'");
        $s->bind_param('iii',$sender,$wantId,$receiver);$s->execute();$b=$s->affected_rows;$s->close();
        if ($a!==1 || $b!==1) throw new RuntimeException('Không thể cập nhật quyền sở hữu sản phẩm.');
        $s=$conn->prepare("UPDATE barter_requests SET status='completed' WHERE id=? AND status='accepted'");
        $s->bind_param('i',$requestId);$s->execute();$s->close();
        // Các yêu cầu khác cùng sản phẩm không còn hợp lệ.
        $s=$conn->prepare("UPDATE barter_requests SET status='rejected'
            WHERE id<>? AND status IN ('pending','accepted')
            AND (offered_product_id IN (?,?) OR requested_product_id IN (?,?))");
        $s->bind_param('iiiii',$requestId,$offerId,$wantId,$offerId,$wantId);$s->execute();$s->close();
      }
      $conn->commit();
      $_SESSION['barter_notice']=$done?'Hai bên đã xác nhận. Trao đổi hoàn tất và quyền sở hữu đã cập nhật.':'Đã xác nhận bàn giao. Chờ người còn lại xác nhận.';
      header('Location: layout.php?page=barter');exit;
    } catch(Throwable $e) {
      $conn->rollback();error_log('Barter confirm: '.$e->getMessage());
      $barterError=$e instanceof RuntimeException?$e->getMessage():'Không thể xác nhận giao dịch. Kiểm tra đã chạy migration 001.';
    }
}
