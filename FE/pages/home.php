<?php require_once __DIR__ . '/../../BE/home-data.php'; ?>
<link rel="stylesheet" href="./assets/css/home.css">
<section class="home-section">
  <div class="home-intro"><div><p class="home-eyebrow">REUSE · CHỢ ĐỒ CŨ THÔNG MINH</p><h1>Món đồ cũ, giá trị mới</h1><p>Tìm kiếm, mua bán hoặc trao đổi sản phẩm đã qua sử dụng.</p></div><a href="layout.php?page=create-product" class="home-sell">+ Đăng bán sản phẩm</a></div>
  <div class="home-header"><div><h2>Khám phá sản phẩm</h2><p class="home-muted"><?php echo $totalProducts; ?> món đồ phù hợp</p></div></div>
  <form method="get" action="layout.php" class="home-filters" aria-label="Lọc sản phẩm">
    <input type="hidden" name="page" value="home">
    <div class="home-filter-wide"><label for="filter_q">Tìm kiếm</label><input id="filter_q" type="search" name="q" maxlength="120" value="<?php echo e($searchKeyword); ?>" placeholder="Tên, mô tả sản phẩm..."></div>
    <div><label for="filter_category">Danh mục</label><select id="filter_category" name="category"><option value="0">Tất cả</option><?php foreach ($productCategories as $category): ?><option value="<?php echo (int)$category['id']; ?>" <?php echo $selectedCategory === (int)$category['id'] ? 'selected' : ''; ?>><?php echo e($category['name']); ?></option><?php endforeach; ?></select></div>
    <div><label for="filter_type">Hình thức</label><select id="filter_type" name="type"><option value="all" <?php echo $selectedType === 'all' ? 'selected' : ''; ?>>Tất cả</option><option value="sell" <?php echo $selectedType === 'sell' ? 'selected' : ''; ?>>Có thể mua</option><option value="barter" <?php echo $selectedType === 'barter' ? 'selected' : ''; ?>>Có thể trao đổi</option></select></div>
    <div><label for="filter_min_price">Giá từ (đ)</label><input id="filter_min_price" type="number" name="min_price" min="0" max="999999999" step="1000" value="<?php echo e($minPriceRaw); ?>" placeholder="0"></div>
    <div><label for="filter_max_price">Giá đến (đ)</label><input id="filter_max_price" type="number" name="max_price" min="0" max="999999999" step="1000" value="<?php echo e($maxPriceRaw); ?>" placeholder="Không giới hạn"></div>
    <div><label for="filter_sort">Sắp xếp</label><select id="filter_sort" name="sort"><option value="newest" <?php echo $selectedSort === 'newest' ? 'selected' : ''; ?>>Mới đăng</option><option value="price_asc" <?php echo $selectedSort === 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option><option value="price_desc" <?php echo $selectedSort === 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option></select></div>
    <button type="submit" class="home-filter-submit">Lọc</button><a href="layout.php?page=home" class="home-reset">Xóa lọc</a>
  </form>

  <?php if ($statusMessage !== ''): ?><p class="home-notice <?php echo $isError ? 'home-error' : ''; ?>"><?php echo e($statusMessage); ?></p><?php endif; ?>
  <?php if ($productList): ?>
  <div class="product-grid">
    <?php foreach ($productList as $product): ?>
      <?php $image = $product['image_url'] ?: './assets/image/ban-phim-co.png'; $isOwner = (int)($_SESSION['user_id'] ?? 0) === (int)$product['user_id']; $buyable = in_array($product['transaction_type'], ['sell','both'], true); ?>
      <article class="product-card">
        <a class="product-image-link" href="layout.php?page=product-detail&amp;id=<?php echo (int)$product['id']; ?>"><img src="<?php echo e($image); ?>" alt="<?php echo e($product['title']); ?>" class="product-image" loading="lazy"></a>
        <div class="product-body">
          <div class="product-tags"><span><?php echo e($product['category_name']); ?></span><span><?php echo (int)$product['condition_percent']; ?>% mới</span></div>
          <h3 class="product-title"><a href="layout.php?page=product-detail&amp;id=<?php echo (int)$product['id']; ?>"><?php echo e($product['title']); ?></a></h3>
          <p class="product-price"><?php echo number_format((float)$product['price'], 0, ',', '.'); ?> đ</p>
          <p class="product-desc"><?php echo e(mb_strimwidth($product['description'], 0, 110, '…', 'UTF-8')); ?></p>
          <div class="product-actions"><a href="layout.php?page=product-detail&amp;id=<?php echo (int)$product['id']; ?>" class="product-secondary">Chi tiết</a>
          <?php if ($isOwner): ?><span class="product-owned">Tin của bạn</span>
          <?php elseif (!$buyable): ?><a class="product-primary" href="layout.php?page=barter">Trao đổi</a>
          <?php elseif (isset($_SESSION['user_id'])): ?>
            <form method="post" action="layout.php?page=cart"><input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>"><input type="hidden" name="cart_action" value="add"><input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>"><button class="product-primary" type="submit">Thêm vào giỏ</button></form>
          <?php else: ?><a class="product-primary" href="layout.php?page=login">Đăng nhập để mua</a><?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php if ($totalPages > 1): ?><nav class="home-pagination" aria-label="Chuyển trang">
    <?php if ($searchPage > 1): ?><a href="<?php echo e(page_url('home', $homeFilterParams + ['p' => $searchPage - 1])); ?>">← Trước</a><?php endif; ?>
    <span>Trang <?php echo $searchPage; ?> / <?php echo $totalPages; ?></span>
    <?php if ($searchPage < $totalPages): ?><a href="<?php echo e(page_url('home', $homeFilterParams + ['p' => $searchPage + 1])); ?>">Sau →</a><?php endif; ?>
  </nav><?php endif; ?>
  <?php endif; ?>
</section>
