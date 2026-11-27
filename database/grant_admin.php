<?php
// CLI local demo: php database/grant_admin.php email@example.com
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$email = $argv[1] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { fwrite(STDERR,"Cách dùng: php database/grant_admin.php email-cua-ban\n");exit(1); }
require __DIR__ . '/../BE/db.php';
$s=$conn->prepare("UPDATE users SET role='admin' WHERE email=?");
$s->bind_param('s',$email);$s->execute();$updated=$s->affected_rows;$s->close();
if(!$updated){echo "Không tìm thấy tài khoản, hoặc tài khoản đã là admin.\n";}else{echo "Đã đặt quyền admin cho tài khoản được chỉ định.\n";}
