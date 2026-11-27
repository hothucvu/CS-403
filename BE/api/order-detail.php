<?php
// Chỉ chủ đơn mới xem được chi tiết, tránh đọc đơn người khác.
require_once __DIR__ . '/_init.php';
if(session_status()===PHP_SESSION_NONE) session_start();
$userId=(int)($_SESSION['user_id']??0);
if(!$userId) api_respond(['error'=>'Vui lòng đăng nhập'],401);
$id=api_int($_GET['id']??0,0,0,2147483647);
if(!$id) api_respond(['error'=>'Mã đơn không hợp lệ'],400);
try {
  $s=$conn->prepare('SELECT id,total_amount,shipping_address,payment_method,payment_status,order_status,created_at FROM orders WHERE id=? AND buyer_id=? LIMIT 1');
  $s->bind_param('ii',$id,$userId);$s->execute();$order=$s->get_result()->fetch_assoc();$s->close();
  if(!$order) api_respond(['error'=>'Không tìm thấy đơn hàng'],404);
  $s=$conn->prepare('SELECT p.id AS product_id,p.title,i.price FROM order_items i JOIN products p ON p.id=i.product_id WHERE i.order_id=? ORDER BY i.id');
  $s->bind_param('i',$id);$s->execute();$items=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
  api_respond(['order'=>$order,'items'=>$items]);
} catch(Throwable $e){error_log($e->getMessage());api_respond(['error'=>'Không tải được đơn hàng'],500);}
