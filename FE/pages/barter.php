<?php
// BE/barter-data.php da duoc nap truoc header trong FE/layout.php
$statusLabels = [
    'pending' => 'Chờ phản hồi',
    'accepted' => 'Đã đồng ý',
    'rejected' => 'Đã từ chối',
    'completed' => 'Đã hoàn tất'
];
?>
<link rel="stylesheet" href="./assets/css/barter.css">
<section class="barter-page">
    <div class="barter-head">
        <div>
            <p class="barter-eyebrow">REUSE · SMART TRADE</p>
            <h1>Trao đổi sản phẩm</h1>
            <p>Tạo đề xuất trao đổi và theo dõi phản hồi từ chủ sản phẩm.</p>
        </div>
        <a href="layout.php?page=home">← Về trang chủ</a>
    </div>

    <?php if ($barterNotice !== ''): ?>
        <p role="status" class="barter-message barter-ok"><?php echo e($barterNotice); ?></p>
    <?php endif; ?>
    <?php if ($barterError !== ''): ?>
        <p role="alert" class="barter-message barter-err"><?php echo e($barterError); ?></p>
    <?php endif; ?>

    <div class="barter-box">
        <h2>Gửi yêu cầu trao đổi</h2>
        <?php if (empty($myBarterProducts)): ?>
            <p>Bạn chưa có sản phẩm được phép trao đổi. Hãy đăng một sản phẩm có hình thức <strong>Trao đổi</strong> hoặc <strong>Cả hai</strong>.</p>
        <?php elseif (empty($otherBarterProducts)): ?>
            <p>Chưa có sản phẩm của người khác sẵn sàng trao đổi.</p>
        <?php else: ?>
            <form method="post" action="layout.php?page=barter" class="barter-form">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="barter_action" value="create">
                <label for="offered_product_id">Sản phẩm của bạn mang đi đổi</label>
                <select name="offered_product_id" id="offered_product_id" required>
                    <option value="">-- Chọn sản phẩm của bạn --</option>
                    <?php foreach ($myBarterProducts as $product): ?>
                        <option value="<?php echo (int) $product['id']; ?>"><?php echo e($product['title']); ?> · <?php echo number_format((float) $product['price'], 0, ',', '.'); ?> đ</option>
                    <?php endforeach; ?>
                </select>
                <label for="requested_product_id">Sản phẩm bạn muốn nhận</label>
                <select name="requested_product_id" id="requested_product_id" required>
                    <option value="">-- Chọn sản phẩm của người khác --</option>
                    <?php foreach ($otherBarterProducts as $product): ?>
                        <option value="<?php echo (int) $product['id']; ?>"><?php echo e($product['title']); ?> · <?php echo e($product['owner_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="barter-btn">Gửi đề xuất trao đổi</button>
            </form>
        <?php endif; ?>
        <p class="barter-hint">Chức năng này chỉ tạo yêu cầu. Đồng ý chưa đồng nghĩa đã chuyển quyền sở hữu hoặc hoàn tất thanh toán.</p>
    </div>

    <div class="barter-cols">
        <div class="barter-box">
            <h2>Yêu cầu gửi đến bạn (<?php echo count($incomingRequests); ?>)</h2>
            <?php if (!$incomingRequests): ?>
                <p>Chưa có yêu cầu nào.</p>
            <?php else: ?>
                <?php foreach ($incomingRequests as $request): ?>
                    <article class="barter-request">
                        <p><strong><?php echo e($request['sender_name']); ?></strong> đề xuất đổi <strong><?php echo e($request['offered_title']); ?></strong> lấy <strong><?php echo e($request['requested_title']); ?></strong>.</p>
                        <span class="barter-status"><?php echo e($statusLabels[$request['status']] ?? $request['status']); ?></span>
                        <?php if ($request['status'] === 'pending'): ?>
                            <div class="barter-actions">
                                <form method="post" action="layout.php?page=barter">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                    <button name="barter_action" value="accept" class="barter-btn" type="submit">Đồng ý</button>
                                </form>
                                <form method="post" action="layout.php?page=barter">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                    <button name="barter_action" value="reject" class="barter-btn barter-btn-light" type="submit">Từ chối</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="barter-box">
            <h2>Yêu cầu bạn đã gửi (<?php echo count($outgoingRequests); ?>)</h2>
            <?php if (!$outgoingRequests): ?>
                <p>Chưa gửi yêu cầu nào.</p>
            <?php else: ?>
                <?php foreach ($outgoingRequests as $request): ?>
                    <article class="barter-request">
                        <p>Bạn đề xuất đổi <strong><?php echo e($request['offered_title']); ?></strong> lấy <strong><?php echo e($request['requested_title']); ?></strong> của <?php echo e($request['receiver_name']); ?>.</p>
                        <span class="barter-status"><?php echo e($statusLabels[$request['status']] ?? $request['status']); ?></span>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
