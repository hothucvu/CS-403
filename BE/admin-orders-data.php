<?php
require_once __DIR__ . '/bao-helpers.php';
bao_require_admin($conn);
$adminOrders=[];$adminOrderError='';
try {
 if ($_SERVER['REQUEST_METHOD']==='POST') {
   if(!bao_csrf_ok()) throw new RuntimeException('Phiên làm việc không hợp lệ.');
   $id=bao_int($_POST['order_id']??null);$action=(string)($_POST['admin_action']??'');
   if (!$id || !in_array($action,['status','payment'],true)) throw new RuntimeException('Thao tác không hợp lệ.');
   $conn->begin_transaction();
   try {
     $s=$conn->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();$order=$s->get_result()->fetch_assoc();$s->close();
     if (!$order) throw new RuntimeException('Không tìm thấy đơn.');
     if ($action==='status') {
        $to=(string)($_POST['order_status']??'');$from=$order['order_status'];
        $allowed=['pending'=>['shipping','cancelled'],'shipping'=>['completed','cancelled'],'completed'=>[],'cancelled'=>[]];
        if (!in_array($to,$allowed[$from]??[],true)) throw new RuntimeException('Không thể chuyển trạng thái đơn hàng như yêu cầu.');
        if ($to==='cancelled') {
          if ($order['payment_status']==='paid') throw new RuntimeException('Không thể hủy đơn đã thanh toán (chưa có xử lý hoàn tiền).');
          $s=$conn->prepare('SELECT product_id FROM order_items WHERE order_id=?');$s->bind_param('i',$id);$s->execute();$productIds=array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'product_id');$s->close();
          sort($productIds,SORT_NUMERIC);
          foreach($productIds as $pid) {
             $pid=(int)$pid;$x=$conn->prepare("UPDATE products SET status='active' WHERE id=? AND status='sold'");$x->bind_param('i',$pid);$x->execute();$x->close();
          }
        }
        $s=$conn->prepare('UPDATE orders SET order_status=? WHERE id=?');$s->bind_param('si',$to,$id);$s->execute();$s->close();
     } else {
        if ($order['order_status']==='cancelled') throw new RuntimeException('Đơn đã hủy không thể ghi nhận thanh toán.');
        if ($order['payment_status']==='paid') throw new RuntimeException('Đơn đã được ghi nhận thanh toán.');
        $s=$conn->prepare("UPDATE orders SET payment_status='paid' WHERE id=? AND payment_status='pending'");$s->bind_param('i',$id);$s->execute();$s->close();
     }
     $conn->commit();bao_flash('success','Đã cập nhật đơn #'.$id.'.');bao_redirect('admin-orders');
   } catch(Throwable $e){$conn->rollback();throw $e;}
 }
 $adminOrders=$conn->query("SELECT o.id,o.buyer_id,u.fullname AS buyer,o.total_amount,o.order_status,o.payment_status,o.payment_method,o.created_at
  FROM orders o JOIN users u ON u.id=o.buyer_id ORDER BY o.id DESC LIMIT 150")->fetch_all(MYSQLI_ASSOC);
} catch(Throwable $e){error_log('Admin orders: '.$e->getMessage());$adminOrderError=$e instanceof RuntimeException?$e->getMessage():'Không thể cập nhật đơn hàng.';}
