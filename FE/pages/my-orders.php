<?php $flash=bao_pull_flash(); ?>
<link rel="stylesheet" href="./assets/css/bao.css"><section class="bao-page"><div class="bao-page-heading"><div><p class="bao-eyebrow">REUSE / ĐƠN HÀNG</p><h1>Đơn hàng của tôi</h1><p>Theo dõi lịch sử mua sắm và trạng thái đơn.</p></div></div>
<?php if($flash): ?><p class="bao-alert"><?=e($flash['message'])?></p><?php endif; ?>
<?php if($orderError): ?><p class="bao-alert bao-error"><?=e($orderError)?></p><?php endif; ?>
<div class="bao-panel">
<?php if(!$orderList): ?><p>Chưa có đơn hàng nào. <a href="layout.php?page=home">Khám phá sản phẩm</a></p>
<?php else: ?><div class="bao-table-wrap"><table class="bao-table"><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Giao hàng</th><th>Thanh toán</th><th></th></tr></thead><tbody>
<?php foreach($orderList as $order): ?><tr><td>#<?= (int)$order['id'] ?></td><td><?=e($order['created_at'])?></td><td><?=bao_money((float)$order['total_amount'])?></td><td><?=e($order['order_status'])?></td><td><?=e($order['payment_status'])?></td><td><a href="layout.php?page=order-detail&amp;id=<?=(int)$order['id']?>">Xem</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div></section>
