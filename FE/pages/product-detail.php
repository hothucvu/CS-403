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
    if ($stmt) {
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$typeLabels = [
    'sell' => 'Bán',
    'barter' => 'Trao đổi',
    'both' => 'Bán hoặc trao đổi',
];

$homeUrl = function_exists('page_url') ? page_url('home') : 'layout.php?page=home';
$homeUrlEscaped = htmlspecialchars((string)$homeUrl, ENT_QUOTES, 'UTF-8');
?>

<link rel="stylesheet" href="./assets/css/product-detail.css">

<?php if (!$product): ?>
    <?php http_response_code(404); ?>
    <section class="detail-empty">
        <h1>Không tìm thấy sản phẩm</h1>
        <p>Sản phẩm không tồn tại hoặc đã bị ẩn.</p>
        <a href="<?php echo $homeUrlEscaped; ?>">&larr; Về danh sách sản phẩm</a>
    </section>
<?php else: ?>
    <?php
    $rawImageUrl = trim((string)($product['image_url'] ?? ''));
    if ($rawImageUrl === '') {
        $imageSrc = './assets/image/image.png';
    } elseif (str_starts_with($rawImageUrl, './') || str_starts_with($rawImageUrl, 'http://') || str_starts_with($rawImageUrl, 'https://')) {
        $imageSrc = $rawImageUrl;
    } else {
        $imageSrc = './assets/image/' . ltrim($rawImageUrl, '/');
    }

    $transactionLabel = $typeLabels[$product['transaction_type']] ?? (string)$product['transaction_type'];
    $formattedDate = !empty($product['created_at']) ? date('d/m/Y', strtotime((string)$product['created_at'])) : '';
    ?>
    <a class="detail-back" href="<?php echo $homeUrlEscaped; ?>">&larr; Quay lại</a>

    <section class="detail-wrap">
        <div class="detail-image">
            <img
                src="<?php echo htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars((string)$product['title'], ENT_QUOTES, 'UTF-8'); ?>"
            >
        </div>

        <div class="detail-info">
            <p class="detail-category"><?php echo htmlspecialchars((string)$product['category_name'], ENT_QUOTES, 'UTF-8'); ?></p>
            <h1><?php echo htmlspecialchars((string)$product['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="detail-price"><?php echo number_format((float)$product['price'], 0, ',', '.'); ?> đ</p>

            <ul class="detail-meta">
                <li><span>Độ mới</span> <?php echo (int)$product['condition_percent']; ?>%</li>
                <li><span>Hình thức</span> <?php echo htmlspecialchars($transactionLabel, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><span>Người bán</span> <?php echo htmlspecialchars((string)$product['seller_name'], ENT_QUOTES, 'UTF-8'); ?> (uy tín <?php echo (int)$product['reputation_score']; ?>)</li>
                <li><span>Ngày đăng</span> <?php echo htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8'); ?></li>
            </ul>

            <h2>Mô tả</h2>
            <p class="detail-desc"><?php echo nl2br(htmlspecialchars((string)$product['description'], ENT_QUOTES, 'UTF-8')); ?></p>
        </div>
    </section>
<?php endif; ?>