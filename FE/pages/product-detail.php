<?php
require_once __DIR__ . '/../../BE/db.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($productId > 0) {
    $sql = "SELECT p.id, p.title, p.description, p.image_url, p.price, p.condition_percent,
                   p.transaction_type, p.created_at,
                   u.fullname AS seller_name, u.reputation_score,
                   c.name AS category_name
            FROM products p
            JOIN users u ON u.id = p.user_id
            JOIN categories c ON c.id = p.category_id
            WHERE p.id = ? AND p.status <> 'hidden'
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$typeLabels = ['sell' => 'Bán', 'barter' => 'Trao đổi', 'both' => 'Bán hoặc trao đổi'];
?>

<link rel="stylesheet" href="./assets/css/product-detail.css">

<?php if (!$product): ?>
    <?php http_response_code(404); ?>
    <section class="detail-empty">
        <h1>Không tìm thấy sản phẩm</h1>
        <p>Sản phẩm không tồn tại hoặc đã bị ẩn.</p>
        <a href="<?php echo e(page_url('home')); ?>">&larr; Về danh sách sản phẩm</a>
    </section>
<?php else: ?>
    <a class="detail-back" href="<?php echo e(page_url('home')); ?>">&larr; Quay lại</a>
    
    <section class="detail-wrap">
        <div class="detail-image">
            <img src="<?php echo "./assets/image/" . $product['image_url']; ?>" alt="<?php echo e($product['title']); ?>">
        </div>

        <div class="detail-info">
            <p class="detail-category"><?php echo e($product['category_name']); ?></p>
            <h1><?php echo e($product['title']); ?></h1>
            <p class="detail-price"><?php echo number_format((float)$product['price'], 0, ',', '.'); ?> đ</p>

            <ul class="detail-meta">
                <li><span>Độ mới</span> <?php echo (int)$product['condition_percent']; ?>%</li>
                <li><span>Hình thức</span> <?php echo e($typeLabels[$product['transaction_type']] ?? $product['transaction_type']); ?></li>
                <li><span>Người bán</span> <?php echo e($product['seller_name']); ?> (uy tín <?php echo (int)$product['reputation_score']; ?>)</li>
                <li><span>Ngày đăng</span> <?php echo e(date('d/m/Y', strtotime($product['created_at']))); ?></li>
            </ul>

            <h2>Mô tả</h2>
            <p class="detail-desc"><?php echo nl2br(e($product['description'])); ?></p>
        </div>
    </section>
<?php endif; ?>