<?php
require_once __DIR__ . '/bao-helpers.php';
$adminId=bao_require_admin($conn);
$adminProductError='';$adminProducts=[];$adminCategories=[];$adminEditing=null;
try {
  if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!bao_csrf_ok()) throw new RuntimeException('Phiên làm việc đã hết hạn.');
    $action=(string)($_POST['admin_action'] ?? '');
    $id=bao_int($_POST['product_id'] ?? null);
    if ($action==='save') {
      $title=trim((string)($_POST['title'] ?? ''));
      $description=trim((string)($_POST['description'] ?? ''));
      $image=trim((string)($_POST['image_url'] ?? ''));
      $categoryId=bao_int($_POST['category_id'] ?? null);
      $price=(float)($_POST['price'] ?? -1);
      $condition=bao_int($_POST['condition_percent'] ?? null);
      $type=(string)($_POST['transaction_type'] ?? 'sell');
      $moderation=(string)($_POST['moderation_status'] ?? 'pending');
      $status=(string)($_POST['status'] ?? 'active');
      if ($title==='' || mb_strlen($title)>200 || $description==='' || mb_strlen($description)>10000) throw new RuntimeException('Tên hoặc mô tả sản phẩm chưa hợp lệ.');
      if ($image==='' || strlen($image)>255 || !in_array($image[0],['.','/'],true) || str_contains($image,'..')) throw new RuntimeException('Đường dẫn ảnh phải là đường dẫn nội bộ hợp lệ.');
      if ($categoryId===0 || !is_numeric($_POST['price'] ?? null) || $price<0 || $price>1000000000 || $condition<1 || $condition>100) throw new RuntimeException('Danh mục, giá hoặc độ mới không hợp lệ.');
      if (!in_array($type,['sell','barter','both'],true) || !in_array($moderation,['pending','approved','rejected'],true) || !in_array($status,['active','hidden'],true)) throw new RuntimeException('Trạng thái sản phẩm không hợp lệ.');
      $s=$conn->prepare('SELECT id FROM categories WHERE id=?');$s->bind_param('i',$categoryId);$s->execute();$ok=(bool)$s->get_result()->fetch_assoc();$s->close();
      if (!$ok) throw new RuntimeException('Danh mục không tồn tại.');
      if ($id) {
        $statusCheck=$conn->prepare('SELECT status FROM products WHERE id=? LIMIT 1');
        $statusCheck->bind_param('i',$id);$statusCheck->execute();$oldStatus=$statusCheck->get_result()->fetch_column();$statusCheck->close();
        if (!in_array($oldStatus,['active','hidden'],true)) throw new RuntimeException('Sản phẩm đã bán hoặc trao đổi không thể sửa.');
        $s=$conn->prepare("UPDATE products SET title=?,description=?,image_url=?,category_id=?,price=?,condition_percent=?,transaction_type=?,moderation_status=?,status=? WHERE id=? AND status IN ('active','hidden')");
        $s->bind_param('sssidisssi',$title,$description,$image,$categoryId,$price,$condition,$type,$moderation,$status,$id);
      } else {
        $s=$conn->prepare("INSERT INTO products(user_id,category_id,title,description,image_url,price,condition_percent,transaction_type,moderation_status,status)
         VALUES(?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param('iisssdisss',$adminId,$categoryId,$title,$description,$image,$price,$condition,$type,$moderation,$status);
      }
      $s->execute();$s->close();
      bao_flash('success',$id?'Đã cập nhật sản phẩm.':'Đã tạo sản phẩm.');bao_redirect('admin-products');
    }
    // Duyet / tu choi tin dang tu nguoi dung ngay tai danh sach Admin.
    // Chi xu ly tin dang dang cho duyet va con dang hoat dong.
    if ($action === 'moderate') {
      $decision = (string)($_POST['decision'] ?? '');
      if ($id < 1 || !in_array($decision, ['approved', 'rejected'], true)) {
        throw new RuntimeException('Yêu cầu duyệt sản phẩm không hợp lệ.');
      }
      $stmt = $conn->prepare(
        "UPDATE products SET moderation_status = ?
         WHERE id = ? AND moderation_status = 'pending' AND status = 'active'"
      );
      $stmt->bind_param('si', $decision, $id);
      $stmt->execute();
      $updated = $stmt->affected_rows;
      $stmt->close();
      if ($updated !== 1) {
        throw new RuntimeException('Sản phẩm không còn ở trạng thái chờ duyệt hoặc đã bị ẩn.');
      }
      bao_flash('success', $decision === 'approved'
        ? 'Đã duyệt sản phẩm #'.$id.'. Tin đăng có thể xuất hiện trên trang chủ.'
        : 'Đã từ chối sản phẩm #'.$id.'.');
      bao_redirect('admin-products');
    }
    if ($action==='hide' && $id) {
      // Xóa mềm để bảo toàn lịch sử giỏ hàng / trao đổi / đơn hàng.
      $s=$conn->prepare("UPDATE products SET status='hidden' WHERE id=? AND status='active'");
      $s->bind_param('i',$id);$s->execute();$updated=$s->affected_rows;$s->close();
      if (!$updated) throw new RuntimeException('Không thể ẩn sản phẩm này.');
      bao_flash('success','Đã ẩn sản phẩm.');bao_redirect('admin-products');
    }
    throw new RuntimeException('Thao tác không hợp lệ.');
  }
  $editId=bao_int($_GET['edit'] ?? null);
  if ($editId) {
    $s=$conn->prepare('SELECT * FROM products WHERE id=? LIMIT 1');$s->bind_param('i',$editId);$s->execute();$adminEditing=$s->get_result()->fetch_assoc();$s->close();
    if (!$adminEditing) $adminProductError='Không tìm thấy sản phẩm cần sửa.';
  }
} catch(Throwable $e){error_log('Admin products: '.$e->getMessage());$adminProductError=$e instanceof RuntimeException?$e->getMessage():'Không thể cập nhật sản phẩm.';}
try {
  $adminCategories=$conn->query('SELECT id,name FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
  $adminProducts=$conn->query('SELECT p.id,p.title,p.price,p.status,p.moderation_status,p.transaction_type,u.fullname AS seller,c.name AS category
    FROM products p JOIN users u ON u.id=p.user_id JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC LIMIT 150')->fetch_all(MYSQLI_ASSOC);
} catch(Throwable $e){error_log($e->getMessage());$adminProductError='Không tải được danh sách sản phẩm.';}
