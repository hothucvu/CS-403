<?php $flash=bao_pull_flash(); ?>
<link rel="stylesheet" href="./assets/css/bao.css"><section class="bao-page"><div class="bao-page-heading"><div><p class="bao-eyebrow">REUSE / ADMIN</p><h1>Quản lý sản phẩm</h1><p>Thêm, sửa, duyệt và ẩn sản phẩm của hệ thống.</p></div><a class="bao-link" href="layout.php?page=admin-orders">Quản lý đơn hàng →</a></div>
<?php if($flash): ?><p class="bao-alert"><?=e($flash['message'])?></p><?php endif; ?><?php if($adminProductError): ?><p class="bao-alert bao-error"><?=e($adminProductError)?></p><?php endif; ?>
<div class="bao-panel"><h2><?=$adminEditing?'Sửa sản phẩm #'.(int)$adminEditing['id']:'Thêm sản phẩm mới'?></h2>
<form action="layout.php?page=admin-products" method="post" class="bao-form bao-form-grid">
<input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="admin_action" value="save"><input type="hidden" name="product_id" value="<?=(int)($adminEditing['id']??0)?>">
<label>Tên sản phẩm<input name="title" maxlength="200" required value="<?=e($adminEditing['title']??'')?>"></label>
<label>Danh mục<select name="category_id" required><option value="">-- Chọn --</option><?php foreach($adminCategories as $cat): ?><option value="<?=(int)$cat['id']?>" <?=($adminEditing['category_id']??0)==$cat['id']?'selected':''?>><?=e($cat['name'])?></option><?php endforeach; ?></select></label>
<label>Giá (VND)<input name="price" type="number" min="0" max="1000000000" step="1000" required value="<?=e($adminEditing['price']??'')?>"></label>
<label>Độ mới (%)<input name="condition_percent" type="number" min="1" max="100" required value="<?=e($adminEditing['condition_percent']??'90')?>"></label>
<label>Hình thức<select name="transaction_type"><option value="sell" <?=($adminEditing['transaction_type']??'')==='sell'?'selected':''?>>Bán</option><option value="barter" <?=($adminEditing['transaction_type']??'')==='barter'?'selected':''?>>Trao đổi</option><option value="both" <?=($adminEditing['transaction_type']??'')==='both'?'selected':''?>>Cả hai</option></select></label>
<label>Kiểm duyệt<select name="moderation_status"><option value="pending" <?=($adminEditing['moderation_status']??'')==='pending'?'selected':''?>>Chờ duyệt</option><option value="approved" <?=($adminEditing['moderation_status']??'')==='approved'?'selected':''?>>Được duyệt</option><option value="rejected" <?=($adminEditing['moderation_status']??'')==='rejected'?'selected':''?>>Từ chối</option></select></label>
<label>Hiển thị<select name="status"><option value="active" <?=($adminEditing['status']??'active')==='active'?'selected':''?>>Hiển thị</option><option value="hidden" <?=($adminEditing['status']??'')==='hidden'?'selected':''?>>Ẩn</option></select></label>
<label>Đường dẫn ảnh nội bộ<input name="image_url" maxlength="255" required placeholder="./assets/image/ban-phim-co.png" value="<?=e($adminEditing['image_url']??'./assets/image/ban-phim-co.png')?>"></label>
<label class="bao-field-full">Mô tả<textarea name="description" maxlength="10000" rows="3" required><?=e($adminEditing['description']??'')?></textarea></label>
<div class="bao-field-full"><button class="bao-btn" type="submit"><?=$adminEditing?'Lưu thay đổi':'Tạo sản phẩm'?></button> <?php if($adminEditing): ?><a class="bao-link" href="layout.php?page=admin-products">Hủy sửa</a><?php endif; ?></div>
</form></div>
<div class="bao-panel"><h2>Danh sách sản phẩm</h2><div class="bao-table-wrap"><table class="bao-table"><thead><tr><th>ID</th><th>Sản phẩm</th><th>Chủ sở hữu</th><th>Giá</th><th>Duyệt</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach($adminProducts as $p): ?><tr><td>#<?=(int)$p['id']?></td><td><?=e($p['title'])?></td><td><?=e($p['seller'])?></td><td><?=bao_money((float)$p['price'])?></td><td><?php
  $moderationLabels=['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'];
  echo e($moderationLabels[$p['moderation_status']] ?? $p['moderation_status']);
?></td><td><?=e($p['status'])?></td><td class="bao-actions">
<?php if ($p['moderation_status'] === 'pending' && $p['status'] === 'active'): ?>
<form class="bao-inline" method="post" action="layout.php?page=admin-products">
  <input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
  <input type="hidden" name="admin_action" value="moderate">
  <input type="hidden" name="product_id" value="<?=(int)$p['id']?>">
  <input type="hidden" name="decision" value="approved">
  <button type="submit" class="bao-approve-button" onclick="return confirm('Duyệt sản phẩm này để hiển thị trên trang chủ?')">Duyệt</button>
</form>
<form class="bao-inline" method="post" action="layout.php?page=admin-products">
  <input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
  <input type="hidden" name="admin_action" value="moderate">
  <input type="hidden" name="product_id" value="<?=(int)$p['id']?>">
  <input type="hidden" name="decision" value="rejected">
  <button type="submit" class="bao-reject-button" onclick="return confirm('Từ chối tin đăng này?')">Từ chối</button>
</form>
<?php endif; ?>
<a href="layout.php?page=admin-products&amp;edit=<?=(int)$p['id']?>">Sửa</a><?php if($p['status']==='active'): ?><form class="bao-inline" method="post" action="layout.php?page=admin-products"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="admin_action" value="hide"><input type="hidden" name="product_id" value="<?=(int)$p['id']?>"><button type="submit" onclick="return confirm('Ẩn sản phẩm này?')">Ẩn</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div></section>
