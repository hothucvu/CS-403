<?php $flash=bao_pull_flash(); ?>
<link rel="stylesheet" href="./assets/css/bao.css">
<section class="bao-page">
  <div class="bao-page-heading"><div><p class="bao-eyebrow">REUSE / THANH TOÁN</p><h1>Đặt hàng</h1><p>Kiểm tra giỏ hàng và địa chỉ nhận hàng trước khi xác nhận.</p></div><a class="bao-link" href="layout.php?page=cart">← Quay lại giỏ hàng</a></div>
  <?php if($checkoutError): ?><p class="bao-alert bao-error"><?=e($checkoutError)?></p><?php endif; ?>
  <?php if(!$checkoutItems): ?>
    <div class="bao-panel"><p>Giỏ hàng không có sản phẩm đang bán hợp lệ.</p><a class="bao-btn" href="layout.php?page=home">Xem sản phẩm</a></div>
  <?php else: ?>
  <div class="bao-two-col"><div class="bao-panel"><h2>Thông tin giao hàng</h2>
    <form method="post" action="layout.php?page=checkout" class="bao-form">
      <input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
      <label for="shipping_address">Địa chỉ nhận hàng *</label>
      <textarea name="shipping_address" id="shipping_address" minlength="10" maxlength="1000" rows="4" required><?=e($checkoutAddress)?></textarea>
      <label for="payment_method">Phương thức thanh toán</label>
      <select name="payment_method" id="payment_method"><option value="COD">COD – Thanh toán khi nhận hàng (mô phỏng)</option></select>
      <p class="bao-muted">Chưa kết nối cổng thanh toán và vận chuyển thật.</p>
      <button type="submit" class="bao-btn">Xác nhận đặt hàng</button>
    </form></div>
    <aside class="bao-panel"><h2>Đơn hàng của bạn</h2>
      <?php foreach($checkoutItems as $item): ?><div class="bao-order-line"><span><?=e($item['title'])?></span><strong><?=bao_money((float)$item['price'])?></strong></div><?php endforeach; ?>
      <div class="bao-total"><span>Tổng cộng</span><strong><?=bao_money($checkoutTotal)?></strong></div>
    </aside></div><?php endif; ?>
</section>
