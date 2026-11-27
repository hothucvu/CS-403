<?php
require_once __DIR__ . '/_init.php';
$id=api_int($_GET['id'] ?? 0,0,0,2147483647);
if (!$id) api_respond(['error'=>'Thiếu mã sản phẩm'],400);
try {
    $stmt=$conn->prepare("SELECT p.id,p.title,p.description,p.image_url,p.price,p.condition_percent,
       p.transaction_type,p.created_at,c.id AS category_id,c.name AS category,u.id AS seller_id,u.fullname AS seller
       FROM products p JOIN categories c ON c.id=p.category_id JOIN users u ON u.id=p.user_id
       WHERE p.id=? AND p.status='active' AND p.moderation_status='approved' LIMIT 1");
    $stmt->bind_param('i',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
    if (!$row) api_respond(['error'=>'Không tìm thấy sản phẩm'],404);
    api_respond(['data'=>$row]);
} catch (Throwable $e) {error_log($e->getMessage());api_respond(['error'=>'Có lỗi hệ thống'],500);}
