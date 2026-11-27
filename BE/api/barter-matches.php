<?php
// Gợi ý trao đổi theo giá và danh mục. Không tự động đặt yêu cầu Barter.
require_once __DIR__ . '/_init.php';
$id=api_int($_GET['product_id'] ?? 0,0,0,2147483647);
if (!$id) api_respond(['error'=>'Thiếu mã sản phẩm'],400);
try {
    $started=microtime(true);
    [$data,$hit]=api_cache('match:' . $id,30,static function() use($conn,$id) {
      $s=$conn->prepare("SELECT id,user_id,category_id,price,condition_percent FROM products WHERE id=? AND status='active' AND moderation_status='approved' AND transaction_type IN ('barter','both') LIMIT 1");
      $s->bind_param('i',$id);$s->execute();$own=$s->get_result()->fetch_assoc();$s->close();
      if (!$own) return ['error'=>'Sản phẩm không thể trao đổi'];
      $s=$conn->prepare("SELECT p.id,p.title,p.price,p.condition_percent,p.image_url,c.name AS category,
         LEAST(100,ROUND(100-LEAST(90,ABS(p.price-?)/GREATEST(1,?)*70) + IF(p.category_id=?,10,0),1)) AS match_score
         FROM products p JOIN categories c ON c.id=p.category_id
         WHERE p.user_id<>? AND p.status='active' AND p.moderation_status='approved'
         AND p.transaction_type IN ('barter','both')
         ORDER BY match_score DESC, p.id DESC LIMIT 10");
      $price=(float)$own['price'];$cat=(int)$own['category_id'];$owner=(int)$own['user_id'];
      $s->bind_param('ddii',$price,$price,$cat,$owner);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
      return ['product_id'=>$id,'matches'=>$rows,'method'=>'Điểm phù hợp tham khảo dựa trên chênh lệch giá và danh mục; chưa tích hợp AI'];
    });
    $data['meta']=['cache_hit'=>$hit,'latency_ms'=>round((microtime(true)-$started)*1000,2)];api_respond($data,isset($data['error'])?404:200);
} catch(Throwable $e){error_log($e->getMessage());api_respond(['error'=>'Có lỗi hệ thống'],500);}
