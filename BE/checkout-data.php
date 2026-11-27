<?php
require_once __DIR__ . '/bao-helpers.php';
$userId=bao_require_login();
$checkoutError='';$checkoutItems=[];$checkoutTotal=0.0;$checkoutAddress='';
try {
    $s=$conn->prepare('SELECT address FROM users WHERE id=? LIMIT 1');
    $s->bind_param('i',$userId);$s->execute();$checkoutAddress=(string)($s->get_result()->fetch_column() ?? '');$s->close();
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        $checkoutAddress=trim((string)($_POST['shipping_address'] ?? ''));
        $method=(string)($_POST['payment_method'] ?? 'COD');
        if (!bao_csrf_ok()) $checkoutError='Phiên thao tác không hợp lệ.';
        elseif (mb_strlen($checkoutAddress)<10 || mb_strlen($checkoutAddress)>1000) $checkoutError='Vui lòng nhập địa chỉ nhận hàng từ 10 đến 1000 ký tự.';
        elseif ($method !== 'COD') $checkoutError='Bản demo hiện chỉ hỗ trợ thanh toán khi nhận hàng (COD).';
        else {
            $conn->begin_transaction();
            try {
                // Lock các mặt hàng khi checkout: 1 món đồ cũ chỉ bán một lần.
                $s=$conn->prepare("SELECT DISTINCT p.id,p.title,p.price,p.user_id,p.status,p.moderation_status,p.transaction_type
                      FROM carts c JOIN products p ON p.id=c.product_id
                      WHERE c.user_id=? ORDER BY p.id FOR UPDATE");
                $s->bind_param('i',$userId);$s->execute();$items=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
                if (!$items) throw new RuntimeException('Giỏ hàng đang trống.');
                $sum=0.0;
                foreach($items as $row) {
                    if ((int)$row['user_id']===$userId || $row['status']!=='active' ||
                        $row['moderation_status']!=='approved' ||
                        !in_array($row['transaction_type'],['sell','both'],true)) {
                        throw new RuntimeException('Một sản phẩm không còn khả dụng. Hãy kiểm tra lại giỏ hàng.');
                    }
                    $sum+=(float)$row['price'];
                }
                $s=$conn->prepare("INSERT INTO orders(buyer_id,total_amount,shipping_address,payment_method,payment_status,order_status)
                  VALUES(?,?,?,'COD','pending','pending')");
                $s->bind_param('ids',$userId,$sum,$checkoutAddress);$s->execute();$orderId=$conn->insert_id;$s->close();
                $detail=$conn->prepare('INSERT INTO order_items(order_id,product_id,price) VALUES(?,?,?)');
                $mark=$conn->prepare("UPDATE products SET status='sold' WHERE id=? AND status='active'");
                foreach($items as $item) {
                    $productId=(int)$item['id'];$price=(float)$item['price'];
                    $detail->bind_param('iid',$orderId,$productId,$price);$detail->execute();
                    $mark->bind_param('i',$productId);$mark->execute();
                    if ($mark->affected_rows !== 1) throw new RuntimeException('Sản phẩm vừa thay đổi. Hãy thử lại.');
                }
                $detail->close();$mark->close();
                $s=$conn->prepare('DELETE FROM carts WHERE user_id=?');$s->bind_param('i',$userId);$s->execute();$s->close();
                $conn->commit();
                bao_flash('success','Đặt hàng thành công. Đơn #'.$orderId.' đang chờ xác nhận COD.');
                bao_redirect('order-detail',['id'=>$orderId]);
            } catch(Throwable $e) {
                $conn->rollback();
                $checkoutError=$e instanceof RuntimeException ? $e->getMessage() : 'Đặt hàng không thành công, vui lòng thử lại.';
                error_log('Checkout: '.$e->getMessage());
            }
        }
    }
    // Preview giỏ hàng, loại bỏ món không được bán hoặc thuộc chính mình.
    $s=$conn->prepare("SELECT DISTINCT p.id,p.title,p.price,p.image_url
       FROM carts c JOIN products p ON p.id=c.product_id
       WHERE c.user_id=? AND p.user_id<>? AND p.status='active'
         AND p.moderation_status='approved' AND p.transaction_type IN ('sell','both')
       ORDER BY p.id DESC");
    $s->bind_param('ii',$userId,$userId);$s->execute();$checkoutItems=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
    foreach ($checkoutItems as $item) $checkoutTotal+=(float)$item['price'];
} catch(Throwable $e){error_log($e->getMessage());$checkoutError='Không tải được dữ liệu giỏ hàng.';}
