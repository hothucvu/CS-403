
<?php
require_once __DIR__ . '/../../BE/home-data.php';
?>

<link rel="stylesheet" href="./assets/css/home.css">

<section class="home-section">
    <div class="home-header">
        <h1>Danh sách sản phẩm</h1>

        <form id="homeSearchForm" method="GET" action="layout.php" class="search-form">
            <input type="hidden" name="page" value="home">
            <input
                type="text"
                id="searchInput"
                name="q"
                value="<?php echo htmlspecialchars($searchKeyword); ?>"
                placeholder="Tìm sản phẩm..."
                class="search-input"
            >
            <button type="submit" class="btn-search">Tìm</button>
            <button type="button" id="resetSearchBtn" class="btn-reset">Tất cả</button>
        </form>
    </div>

    <?php if ($statusMessage !== ''): ?>
        <p class="status-message <?php echo $isError ? 'status-error' : ''; ?>">
            <?php echo htmlspecialchars($statusMessage); ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($productList)): ?>
        <div class="product-grid">
            <?php foreach ($productList as $product): ?>
                <?php
                $imgSrc = !empty($product['image_url'])
                    ? htmlspecialchars($product['image_url'])
                    : './assets/image/image.png';
                ?>

                <article class="product-card">
                    <img
                        src="<?php echo $imgSrc; ?>"
                        alt="<?php echo htmlspecialchars($product['title']); ?>"
                        class="product-image"
                    >

                    <div class="product-body">
                        <h2 class="product-title">
                            <?php echo htmlspecialchars($product['title']); ?>
                        </h2>

                        <p class="product-price">
                            <?php echo number_format((float)$product['price'], 0, ',', '.'); ?> đ
                        </p>

                        <p class="product-desc">
                            <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                        </p>

                        <!-- THÊM SẢN PHẨM VÀO GIỎ HÀNG -->
                        <?php if (isset($_SESSION['user_id'])): ?>

                            <form method="POST" action="layout.php?page=cart">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                >

                                <input
                                    type="hidden"
                                    name="cart_action"
                                    value="add"
                                >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?php echo (int)$product['id']; ?>"
                                >

                                <button type="submit" class="btn-add-cart">
                                    🛒 Thêm vào giỏ hàng
                                </button>
                            </form>

                        <?php else: ?>

                            <a href="layout.php?page=login" class="btn-add-cart">
                                Đăng nhập để mua
                            </a>

                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script src="./assets/js/home.js"></script>
