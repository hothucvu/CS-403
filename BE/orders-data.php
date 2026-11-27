<?php
require_once __DIR__ . '/bao-helpers.php';
$userId=bao_require_login();
$orderList=[];$orderDetail=null;$orderItems=[];$orderError='';
try {
    if ($pageToLoad==='pages/order-detail.php') {
        $id=bao_int($_GET['id'] ?? null);
        if (!$id) { http_response_code(400); $orderError='Mã đơn hàng không hợp lệ.'; }
        else {
            $stmt=$conn->prepare('SELECT * FROM orders WHERE id=? AND buyer_id=? LIMIT 1');
            $stmt->bind_param('ii',$id,$userId);$stmt->execute();$orderDetail=$stmt->get_result()->fetch_assoc();$stmt->close();
            if (!$orderDetail) { http_response_code(404); $orderError='Đơn hàng không tồn tại hoặc không thuộc về bạn.'; }
            else {
                $stmt=$conn->prepare('SELECT i.price,p.title,p.image_url,p.id AS product_id FROM order_items i JOIN products p ON p.id=i.product_id WHERE i.order_id=? ORDER BY i.id');
                $stmt->bind_param('i',$id);$stmt->execute();$orderItems=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
            }
        }
    } else {
        $stmt=$conn->prepare('SELECT id,total_amount,payment_status,payment_method,order_status,created_at FROM orders WHERE buyer_id=? ORDER BY id DESC LIMIT 100');
        $stmt->bind_param('i',$userId);$stmt->execute();$orderList=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
    }
} catch(Throwable $e){error_log($e->getMessage());$orderError='Không tải được dữ liệu đơn hàng.';}
