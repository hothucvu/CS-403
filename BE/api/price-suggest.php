<?php
// Gợi ý giá theo trung vị sản phẩm cùng danh mục, KHÔNG phải model AI.
require_once __DIR__ . '/_init.php';
$id=api_int($_GET['product_id'] ?? 0,0,0,2147483647);
if (!$id) api_respond(['error'=>'Thiếu mã sản phẩm'],400);
try {
    $started=microtime(true);
    [$data,$hit]=api_cache('price:' . $id,60,static function() use($conn,$id) {
       $s=$conn->prepare("SELECT id,title,category_id,price,condition_percent FROM products WHERE id=? AND status='active' AND moderation_status='approved' LIMIT 1");
       $s->bind_param('i',$id);$s->execute();$product=$s->get_result()->fetch_assoc();$s->close();
       if (!$product) return ['error'=>'Không tìm thấy sản phẩm'];
       $s=$conn->prepare("SELECT price,condition_percent FROM products
          WHERE category_id=? AND id<>? AND status='active' AND moderation_status='approved'
          ORDER BY price ASC LIMIT 100");
       $cat=(int)$product['category_id'];$s->bind_param('ii',$cat,$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
       if (!$rows) return ['product_id'=>$id,'suggested_price'=>null,'sample_size'=>0,'method'=>'Chưa có dữ liệu sản phẩm cùng danh mục'];
       $values=array_map(static fn($r) => (float)$r['price'] / max(1, (int)$r['condition_percent']) * (int)$product['condition_percent'], $rows);
       sort($values,SORT_NUMERIC);$n=count($values);
       $median=$n%2?$values[intdiv($n,2)]:($values[intdiv($n,2)-1]+$values[intdiv($n,2)])/2;
       return ['product_id'=>$id,'suggested_price'=>round($median/1000)*1000,'sample_size'=>$n,'method'=>'Trung vị giá đã điều chỉnh theo độ mới; chỉ để tham khảo, không phải mô hình AI'];
    });
    $data['meta']=['cache_hit'=>$hit,'latency_ms'=>round((microtime(true)-$started)*1000,2)];
    api_respond($data,isset($data['error'])?404:200);
} catch(Throwable $e){error_log($e->getMessage());api_respond(['error'=>'Có lỗi hệ thống'],500);}
