<?php
// Danh sách đơn của người đang đăng nhập (cookie PHPSESSID).
require_once __DIR__ . '/_init.php';
if(session_status()===PHP_SESSION_NONE) session_start();
$userId=(int)($_SESSION['user_id']??0);
if(!$userId) api_respond(['error'=>'Vui lòng đăng nhập'],401);
try {
  $s=$conn->prepare('SELECT id,total_amount,payment_method,payment_status,order_status,created_at FROM orders WHERE buyer_id=? ORDER BY id DESC LIMIT 100');
  $s->bind_param('i',$userId);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
  api_respond(['data'=>$rows]);
} catch(Throwable $e){error_log($e->getMessage());api_respond(['error'=>'Không tải được đơn hàng'],500);}
