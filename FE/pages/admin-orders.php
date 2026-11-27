<?php $flash=bao_pull_flash(); ?>
<link rel="stylesheet" href="./assets/css/bao.css"><section class="bao-page"><div class="bao-page-heading"><div><p class="bao-eyebrow">REUSE / ADMIN</p><h1>Quản lý đơn hàng</h1><p>Xử lý trạng thái giao hàng và thanh toán giả lập COD.</p></div><a class="bao-link" href="layout.php?page=admin-products">← Quản lý sản phẩm</a></div>
<?php if($flash):?><p class="bao-alert"><?=e($flash['message'])?></p><?php endif;?><?php if($adminOrderError):?><p class="bao-alert bao-error"><?=e($adminOrderError)?></p><?php endif;?>
<div class="bao-panel"><div class="bao-table-wrap"><table class="bao-table"><thead><tr><th>Đơn</th><th>Người mua</th><th>Ngày</th><th>Tổng tiền</th><th>Giao hàng</th><th>Thanh toán</th><th>Quản lý</th></tr></thead><tbody>
<?php foreach($adminOrders as $order): ?><tr><td>#<?=(int)$order['id']?></td><td><?=e($order['buyer'])?></td><td><?=e($order['created_at'])?></td><td><?=bao_money((float)$order['total_amount'])?></td><td><?=e($order['order_status'])?></td><td><?=e($order['payment_status'])?></td><td>
<?php $next=['pending'=>['shipping'=>'Đang giao','cancelled'=>'Hủy'],'shipping'=>['completed'=>'Hoàn tất','cancelled'=>'Hủy']]; ?>
<?php foreach(($next[$order['order_status']]??[]) as $status=>$label): ?>
<?php if ($status==='cancelled' && $order['payment_status']==='paid') continue; ?>
<form class="bao-inline" method="post" action="layout.php?page=admin-orders"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="order_id" value="<?=(int)$order['id']?>"><input type="hidden" name="admin_action" value="status"><input type="hidden" name="order_status" value="<?=e($status)?>"><button type="submit"><?=e($label)?></button></form>
<?php endforeach; ?>
<?php if($order['payment_status']==='pending' && $order['order_status']!=='cancelled'): ?><form class="bao-inline" method="post" action="layout.php?page=admin-orders"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="order_id" value="<?=(int)$order['id']?>"><input type="hidden" name="admin_action" value="payment"><button type="submit">COD đã thu (demo)</button></form><?php endif;?>
</td></tr><?php endforeach; ?></tbody></table></div>
<?php if(!$adminOrders): ?><p>Chưa có đơn hàng.</p><?php endif;?></div></section>
