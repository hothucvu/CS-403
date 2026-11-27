<link rel="stylesheet" href="./assets/css/marketplace.css">
<section class="mp-page">
  <div class="mp-heading"><div><p class="mp-eyebrow">GIAN HÀNG CỦA BẠN</p><h1>Quản lý tin đăng</h1><p>Xem, sửa, ẩn, hiện hoặc xóa sản phẩm bạn sở hữu.</p></div><a class="mp-primary mp-button-link" href="layout.php?page=create-product">+ Đăng sản phẩm</a></div>
  <?php if ($myProductsNotice !== ''): ?><p class="mp-alert mp-success" role="status"><?php echo e($myProductsNotice); ?></p><?php endif; ?>
  <?php if ($myProductsError !== ''): ?><p class="mp-alert mp-error" role="alert"><?php echo e($myProductsError); ?></p><?php endif; ?>
  <?php if (empty($myListings)): ?>
    <div class="mp-panel mp-empty"><h2>Bạn chưa có tin đăng</h2><p>Hãy đăng món đồ không còn sử dụng để bắt đầu.</p><a class="mp-primary mp-button-link" href="layout.php?page=create-product">Đăng món đồ đầu tiên</a></div>
  <?php else: ?>
    <div class="mp-listings">
    <?php foreach ($myListings as $item): ?>
      <article class="mp-listing">
        <img src="<?php echo e($item['image_url'] ?: './assets/image/ban-phim-co.png'); ?>" alt="<?php echo e($item['title']); ?>">
        <div class="mp-listing-info"><h2><?php echo e($item['title']); ?></h2><strong><?php echo number_format((float)$item['price'], 0, ',', '.'); ?> đ</strong><p class="mp-hint">Trạng thái: <?php echo e(['active'=>'Đang hiển thị','hidden'=>'Đã ẩn','sold'=>'Đã bán','bartered'=>'Đã trao đổi'][$item['status']] ?? $item['status']); ?> · Duyệt: <?php echo e($item['moderation_status']); ?></p></div>
        <div class="mp-actions"><a class="mp-secondary" href="layout.php?page=create-product&amp;id=<?php echo (int)$item['id']; ?>">Sửa tin</a>
          <?php if (in_array($item['status'], ['active', 'hidden'], true)): ?>
          <form method="post" action="layout.php?page=my-products"><input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>"><input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>"><input type="hidden" name="listing_action" value="<?php echo $item['status'] === 'active' ? 'hide' : 'activate'; ?>"><button type="submit" class="mp-secondary"><?php echo $item['status'] === 'active' ? 'Ẩn tin' : 'Hiện tin'; ?></button></form>
          <?php endif; ?>
          <?php if (in_array($item['status'], ['active', 'hidden'], true)): ?>
          <?php if ((int) $item['has_history']): ?>
            <button type="button" class="mp-danger" disabled title="Sản phẩm có đơn hàng hoặc lịch sử trao đổi. Hãy dùng Ẩn tin.">Không thể xóa</button>
          <?php else: ?>
          <form method="post" action="layout.php?page=my-products" onsubmit="return confirm('Bạn có chắc muốn XÓA VĨNH VIỄN tin đăng này?\nNếu đang có trong giỏ hàng, sản phẩm cũng sẽ được gỡ khỏi giỏ.\nKhông thể khôi phục sau khi xóa.');">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
            <input type="hidden" name="listing_action" value="delete">
            <button type="submit" class="mp-danger">Xóa</button>
          </form>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
