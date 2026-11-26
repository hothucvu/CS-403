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
                $detailUrl = e(page_url('product-detail', ['id' => $product['id']]));
                ?>
                <article class="product-card">
                    <a href="<?php echo $detailUrl; ?>">
                        <img
                            src="<?php echo "./assets/images/" . $product['image_url']; ?>"
                            alt="<?php echo htmlspecialchars($product['title']); ?>"
                            class="product-image"
                        >
                    </a>
                    <div class="product-body">
                        <h2 class="product-title">
                            <a href="<?php echo $detailUrl; ?>">
                                <?php echo htmlspecialchars($product['title']); ?>
                            </a>
                        </h2>
                        <p class="product-price"><?php echo number_format((float)$product['price'], 0, ',', '.'); ?> đ</p>
                        <p class="product-desc"><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script src="./assets/js/home.js"></script>