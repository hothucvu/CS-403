<?php $flash=bao_pull_flash(); ?>
<link rel="stylesheet" href="./assets/css/bao.css"><section class="bao-page"><div class="bao-page-heading"><div><p class="bao-eyebrow">REUSE / ĐƠN HÀNG</p><h1>Chi tiết đơn hàng</h1></div><a class="bao-link" href="layout.php?page=my-orders">← Danh sách đơn</a></div>
<?php if($flash): ?><p class="bao-alert"><?=e($flash['message'])?></p><?php endif; ?>
<?php if($orderError): ?><p class="bao-alert bao-error"><?=e($orderError)?></p><?php endif; ?>
<?php if($orderDetail): ?><div class="bao-two-col"><div class="bao-panel"><h2>Đơn #<?=(int)$orderDetail['id']?></h2>
<p><strong>Trạng thái giao hàng:</strong> <?=e($orderDetail['order_status'])?></p><p><strong>Thanh toán:</strong> <?=e($orderDetail['payment_status'])?> (<?=e($orderDetail['payment_method'])?>)</p>
<p><strong>Ngày đặt:</strong> <?=e($orderDetail['created_at'])?></p>
<p><strong>Địa chỉ:</strong> <?=nl2br(e($orderDetail['shipping_address']))?></p></div><div class="bao-panel"><h2>Sản phẩm</h2>
<?php foreach($orderItems as $item): ?><div class="bao-order-line"><span><?=e($item['title'])?></span><strong><?=bao_money((float)$item['price'])?></strong></div><?php endforeach; ?>
<div class="bao-total"><span>Tổng cộng</span><strong><?=bao_money((float)$orderDetail['total_amount'])?></strong></div></div></div><?php endif; ?></section>
