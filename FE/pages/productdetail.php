<?php
require_once __DIR__ . '/../../BE/db.php';
$productId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$product = null;
if ($productId && $productId > 0) {
    $stmt = $conn->prepare("SELECT p.id, p.user_id, p.title, p.description, p.image_url, p.price, p.condition_percent,
        p.transaction_type, p.created_at, u.fullname AS seller_name, u.reputation_score,
        c.name AS category_name
        FROM products p JOIN users u ON u.id = p.user_id JOIN categories c ON c.id = p.category_id
        WHERE p.id = ? AND p.status = 'active' AND p.moderation_status = 'approved' LIMIT 1");
    $stmt->bind_param('i', $productId); $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc(); $stmt->close();
}
$typeLabels = ['sell' => 'Chỉ bán', 'barter' => 'Chỉ trao đổi', 'both' => 'Bán hoặc trao đổi'];
?>
<link rel="stylesheet" href="./assets/css/product-detail.css">
<a href="layout.php?page=home" class="detail-back">← Quay lại danh sách</a>
<?php if (!$product): http_response_code(404); ?>
<section class="detail-empty"><h1>Không tìm thấy sản phẩm</h1><p>Sản phẩm không tồn tại hoặc chưa được duyệt.</p><a href="layout.php?page=home">Xem sản phẩm khác</a></section>
<?php else: ?>
<section class="detail-wrap">
  <div class="detail-media"><img src="<?php echo e($product['image_url'] ?: './assets/image/ban-phim-co.png'); ?>" alt="<?php echo e($product['title']); ?>"></div>
  <div class="detail-info"><span class="detail-category"><?php echo e($product['category_name']); ?></span><h1><?php echo e($product['title']); ?></h1><p class="detail-price"><?php echo number_format((float)$product['price'], 0, ',', '.'); ?> đ</p>
    <dl class="detail-meta"><div><dt>Độ mới</dt><dd><?php echo (int)$product['condition_percent']; ?>%</dd></div><div><dt>Hình thức</dt><dd><?php echo e($typeLabels[$product['transaction_type']] ?? $product['transaction_type']); ?></dd></div><div><dt>Người bán</dt><dd><?php echo e($product['seller_name']); ?> · <?php echo (int)$product['reputation_score']; ?> điểm uy tín</dd></div><div><dt>Ngày đăng</dt><dd><?php echo e(date('d/m/Y', strtotime($product['created_at']))); ?></dd></div></dl>
    <div class="detail-actions">
      <?php if ((int)($product['user_id']) === (int)($_SESSION['user_id'] ?? 0)): ?><a href="layout.php?page=create-product&amp;id=<?php echo (int)$product['id']; ?>" class="detail-btn">Sửa tin của bạn</a>
      <?php else: ?>
        <?php if (in_array($product['transaction_type'], ['sell','both'], true)): ?>
          <?php if (isset($_SESSION['user_id'])): ?><form method="post" action="layout.php?page=cart"><input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>"><input type="hidden" name="cart_action" value="add"><input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>"><button type="submit" class="detail-btn">🛒 Thêm vào giỏ</button></form>
          <?php else: ?><a href="layout.php?page=login" class="detail-btn">Đăng nhập để mua</a><?php endif; ?>
        <?php endif; ?>
        <?php if (in_array($product['transaction_type'], ['barter','both'], true)): ?><a href="layout.php?page=barter" class="detail-btn detail-outline">Đề nghị trao đổi</a><?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
<section class="detail-description"><h2>Thông tin sản phẩm</h2><p><?php echo nl2br(e($product['description'])); ?></p></section>
<?php endif; ?>

<link rel="stylesheet" href="./assets/css/bao.css">
<?php if ($product): ?>
<section class="bao-panel" style="margin-top:18px" id="bao-suggestions">
  <h2>Gợi ý thông minh (demo)</h2>
  <p class="bao-muted">Gợi ý theo giá và danh mục sản phẩm; chưa dùng mô hình AI.</p>
  <button type="button" class="bao-btn" id="bao-suggest-btn" data-id="<?=(int)$product['id']?>">Xem gợi ý giá &amp; trao đổi</button>
  <div id="bao-suggest-results" class="bao-muted" aria-live="polite"></div>
</section>
<script>
(function(){
  const btn=document.getElementById('bao-suggest-btn');
  if(!btn) return;
  btn.addEventListener('click',async()=>{
    const box=document.getElementById('bao-suggest-results');
    box.textContent='Đang tải gợi ý...';
    try {
      const id=encodeURIComponent(btn.dataset.id);
      const [a,b]=await Promise.all([
        fetch('../BE/api/price-suggest.php?product_id='+id).then(r=>r.json()),
        fetch('../BE/api/barter-matches.php?product_id='+id).then(r=>r.json())
      ]);
      box.replaceChildren();
      const p=document.createElement('p');
      p.textContent=a.suggested_price===null||a.suggested_price===undefined
        ? 'Chưa đủ dữ liệu để gợi ý giá.'
        : 'Giá tham khảo: '+new Intl.NumberFormat('vi-VN').format(a.suggested_price)+' đ ('+a.sample_size+' mẫu)';
      box.appendChild(p);
      const title=document.createElement('p');title.textContent='Gợi ý trao đổi:';box.appendChild(title);
      const list=document.createElement('ul');
      for(const item of (b.matches||[])) {
        const li=document.createElement('li');
        li.textContent=item.title+' — '+new Intl.NumberFormat('vi-VN').format(item.price)+' đ (điểm phù hợp '+item.match_score+')';
        list.appendChild(li);
      }
      if(!list.children.length){const p=document.createElement('p');p.textContent='Chưa có sản phẩm trao đổi phù hợp.';box.appendChild(p);}
      else box.appendChild(list);
    } catch(e){box.textContent='Không thể tải gợi ý. Hãy kiểm tra API và kết nối database.';}
  });
})();
</script>
<?php endif; ?>
