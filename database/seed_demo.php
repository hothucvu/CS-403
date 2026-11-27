<?php
// CLI-only: php database/seed_demo.php ; KHÔNG tự tạo tài khoản/mật khẩu/token.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Chỉ chạy từ CLI.'); }
require __DIR__ . '/../BE/db.php';
$cats=[['Điện tử','dien-tu'],['Thời trang','thoi-trang'],['Sách','sach'],['Đồ gia dụng','do-gia-dung'],['Thể thao','the-thao']];
foreach($cats as [$name,$slug]) {
    $s=$conn->prepare('SELECT id FROM categories WHERE slug=? LIMIT 1');$s->bind_param('s',$slug);$s->execute();$exists=$s->get_result()->fetch_assoc();$s->close();
    if(!$exists) {$s=$conn->prepare('INSERT INTO categories(name,slug,description) VALUES(?,?,?)');$d='Danh mục demo ReUse';$s->bind_param('sss',$name,$slug,$d);$s->execute();$s->close();}
}
$users=$conn->query('SELECT id FROM users ORDER BY id LIMIT 2')->fetch_all(MYSQLI_ASSOC);
if(count($users)<2) {echo "Đã thêm danh mục. Đăng ký ít nhất 2 tài khoản trước khi thêm sản phẩm trao đổi demo.\n";exit(0);}
$cat=(int)$conn->query("SELECT id FROM categories WHERE slug='dien-tu' LIMIT 1")->fetch_column();
$demo=[[ (int)$users[0]['id'],'Máy ảnh cũ demo','Máy ảnh dùng tốt, có thể trao đổi.','./assets/image/ban-phim-co.png',450000,85],[(int)$users[1]['id'],'Tai nghe cũ demo','Tai nghe đã qua sử dụng, có thể trao đổi.','./assets/image/tai-nghe-bluetooth.png',200000,80]];
foreach($demo as [$uid,$title,$desc,$img,$price,$condition]) {
    $s=$conn->prepare('SELECT id FROM products WHERE user_id=? AND title=? LIMIT 1');$s->bind_param('is',$uid,$title);$s->execute();$exists=$s->get_result()->fetch_assoc();$s->close();
    if(!$exists){$s=$conn->prepare("INSERT INTO products(user_id,category_id,title,description,image_url,price,condition_percent,transaction_type,moderation_status,status) VALUES(?,?,?,?,?,?,?,'both','approved','active')");$s->bind_param('iisssdi',$uid,$cat,$title,$desc,$img,$price,$condition);$s->execute();$s->close();}
}
echo "Đã thêm dữ liệu demo an toàn (không tạo user hoặc token).\n";
