<?php
// $cartItems, $cartTotal, $cartNotice and $cartError are provided by
// BE/cart-data.php, loaded before the header in FE/layout.php.
?>
<link rel="stylesheet" href="./assets/css/cart.css">
<section class="cart-page">
    <div class="cart-top">
        <div>
            <p class="cart-kicker">MUA SẮM THÔNG MINH</p>
            <h1>Giỏ hàng của bạn</h1>
            <p class="cart-muted">Quản lý những món đồ muốn mua tại ReUse.</p>
        </div>
        <a class="cart-back" href="layout.php?page=home">← Tiếp tục mua sắm</a>
    </div>

    <?php if ($cartNotice !== ''): ?>
        <p class="cart-alert cart-success" role="status"><?php echo e($cartNotice); ?></p>
    <?php endif; ?>
    <?php if ($cartError !== ''): ?>
        <p class="cart-alert cart-error" role="alert"><?php echo e($cartError); ?></p>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="cart-empty">
            <div class="cart-empty-icon" aria-hidden="true">🛒</div>
            <h2>Giỏ hàng đang trống</h2>
            <p>Hãy chọn sản phẩm ở trang chủ để thêm vào giỏ hàng.</p>
            <a class="cart-primary" href="layout.php?page=home">Xem sản phẩm</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-list">
                <?php foreach ($cartItems as $item): ?>
                    <article class="cart-item">
                        <div class="cart-item-media">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo e($item['image_url']); ?>" alt="<?php echo e($item['title']); ?>">
                            <?php else: ?>
                                <div class="cart-image-fallback" aria-hidden="true">🛍️</div>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-details">
                            <h2><?php echo e($item['title']); ?></h2>
                            <p class="cart-price"><?php echo number_format((float) $item['price'], 0, ',', '.'); ?> đ</p>
                            <p class="cart-muted">Số lượng: 1</p>
                        </div>
                        <form method="post" action="layout.php?page=cart" class="cart-remove-form">
                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="cart_action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                            <button type="submit" class="cart-remove">Xóa</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
            <aside class="cart-summary">
                <h2>Tổng kết giỏ hàng</h2>
                <div class="cart-summary-line"><span>Số sản phẩm</span><strong><?php echo count($cartItems); ?></strong></div>
                <div class="cart-summary-line cart-summary-total"><span>Tổng tiền</span><strong><?php echo number_format($cartTotal, 0, ',', '.'); ?> đ</strong></div>
                <p class="cart-muted">Chức năng thanh toán sẽ tích hợp với phần Checkout của nhóm.</p>
                <a class="cart-back" href="layout.php?page=home">Tiếp tục mua sắm</a>
            </aside>
        </div>
    <?php endif; ?>
</section>
