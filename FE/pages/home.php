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
                value="<?php echo htmlspecialchars((string)($searchKeyword ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Tìm sản phẩm..."
                class="search-input"
            >
            <button type="submit" class="btn-search">Tìm</button>
            <button type="button" id="resetSearchBtn" class="btn-reset">Tất cả</button>
        </form>
    </div>

    <?php if (!empty($statusMessage)): ?>
        <p class="status-message <?php echo !empty($isError) ? 'status-error' : ''; ?>">
            <?php echo htmlspecialchars((string)$statusMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($productList)): ?>
        <div class="product-grid">
            <?php foreach ($productList as $product): ?>
                <?php
                $productId = (int)($product['id'] ?? 0);
                $rawDetailUrl = function_exists('page_url')
                    ? page_url('product-detail', ['id' => $productId])
                    : 'layout.php?page=product-detail&id=' . $productId;
                $detailUrl = htmlspecialchars((string)$rawDetailUrl, ENT_QUOTES, 'UTF-8');

                $rawImageUrl = trim((string)($product['image_url'] ?? ''));
                if ($rawImageUrl === '') {
                    $imageSrc = './assets/image/image.png';
                } elseif (
                    str_starts_with($rawImageUrl, './') ||
                    str_starts_with($rawImageUrl, 'http://') ||
                    str_starts_with($rawImageUrl, 'https://')
                ) {
                    $imageSrc = $rawImageUrl;
                } else {
                    $imageSrc = './assets/image/' . ltrim($rawImageUrl, '/');
                }
                ?>
                <article class="product-card">
                    <a href="<?php echo $detailUrl; ?>">
                        <img
                            src="<?php echo htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars((string)($product['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            class="product-image"
                        >
                    </a>
                    <div class="product-body">
                        <h2 class="product-title">
                            <a href="<?php echo $detailUrl; ?>">
                                <?php echo htmlspecialchars((string)($product['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h2>
                        <p class="product-price"><?php echo number_format((float)($product['price'] ?? 0), 0, ',', '.'); ?> đ</p>
                        <p class="product-desc"><?php echo htmlspecialchars((string)($product['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script src="./assets/js/home.js"></script>