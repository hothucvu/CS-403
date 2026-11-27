<link rel="stylesheet" href="./assets/css/marketplace.css">
<section class="mp-page">
  <div class="mp-heading"><div><p class="mp-eyebrow">ĐỒ CŨ CÓ GIÁ TRỊ MỚI</p><h1><?php echo $editingId > 0 ? 'Chỉnh sửa tin đăng' : 'Đăng bán sản phẩm'; ?></h1><p>Đăng món đồ đã qua sử dụng, chọn bán hoặc trao đổi với cộng đồng ReUse.</p></div><a class="mp-link" href="layout.php?page=my-products">Tin của tôi →</a></div>
  <?php if ($listingError !== ''): ?><p class="mp-alert mp-error" role="alert"><?php echo e($listingError); ?></p><?php endif; ?>
  <?php if (!$listingCategories): ?><div class="mp-panel">Chưa có danh mục trong bảng <code>categories</code>. Hãy thêm danh mục trong MySQL trước khi đăng bán.</div>
  <?php elseif ($editingId === 0 || ($editProduct && in_array($editProduct['status'], ['active', 'hidden'], true))): ?>
  <div class="mp-panel mp-form-panel">
    <form method="post" action="layout.php?page=create-product<?php echo $editingId > 0 ? '&amp;id=' . $editingId : ''; ?>" enctype="multipart/form-data" class="mp-form">
      <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
      <label for="listing_title">Tên sản phẩm *</label><input id="listing_title" name="title" maxlength="200" minlength="5" required placeholder="Ví dụ: Tai nghe Bluetooth còn mới" value="<?php echo e($listingForm['title']); ?>">
      <label for="listing_category">Danh mục *</label><select id="listing_category" name="category_id" required><option value="">-- Chọn danh mục --</option><?php foreach ($listingCategories as $cat): ?><option value="<?php echo (int)$cat['id']; ?>" <?php echo (int)$listingForm['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option><?php endforeach; ?></select>
      <label for="listing_description">Mô tả tình trạng sản phẩm *</label><textarea id="listing_description" name="description" rows="5" maxlength="5000" minlength="10" required placeholder="Mô tả chức năng, tình trạng, phụ kiện đi kèm..."><?php echo e($listingForm['description']); ?></textarea>
      <div class="mp-two-fields"><div><label for="listing_price">Giá bán / giá trị tham khảo (VNĐ) *</label><input id="listing_price" name="price" type="number" step="1000" min="0" max="999999999" value="<?php echo e($listingForm['price']); ?>" required></div><div><label for="listing_condition">Độ mới (%) *</label><input id="listing_condition" name="condition_percent" type="number" min="1" max="100" value="<?php echo e($listingForm['condition_percent']); ?>" required></div></div>
      <label for="listing_type">Hình thức giao dịch *</label><select id="listing_type" name="transaction_type" required><option value="sell" <?php echo $listingForm['transaction_type'] === 'sell' ? 'selected' : ''; ?>>Chỉ bán</option><option value="barter" <?php echo $listingForm['transaction_type'] === 'barter' ? 'selected' : ''; ?>>Chỉ trao đổi</option><option value="both" <?php echo $listingForm['transaction_type'] === 'both' ? 'selected' : ''; ?>>Bán và trao đổi</option></select>
      <label for="product_image">Ảnh sản phẩm <?php echo $editingId > 0 ? '(chọn nếu muốn thay)' : '*'; ?></label><input id="product_image" name="product_image" type="file" accept="image/jpeg,image/png,image/webp" <?php echo $editingId === 0 ? 'required' : ''; ?>><p class="mp-hint">Chỉ nhận JPG, PNG, WEBP · tối đa 5 MB. Không đăng ảnh chứa thông tin riêng tư.</p>
      <?php if (!empty($editProduct['image_url'])): ?><p class="mp-hint">Ảnh hiện tại:</p><img class="mp-preview" src="<?php echo e($editProduct['image_url']); ?>" alt="Ảnh sản phẩm hiện tại"><?php endif; ?>
      <button type="submit" class="mp-primary"><?php echo $editingId > 0 ? 'Lưu chỉnh sửa' : 'Đăng sản phẩm'; ?></button>
    </form>
  </div>
  <?php endif; ?>
</section>
