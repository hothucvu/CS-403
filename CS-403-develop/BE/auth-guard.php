
<?php
// BE/auth-guard.php
// Kiểm tra đăng nhập và phân quyền

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra người dùng đã đăng nhập
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        exit('Bạn cần đăng nhập.');
    }
}

// Kiểm tra quyền Admin
function requireAdmin(): void {
    requireLogin();

    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Bạn không có quyền truy cập.');
    }
}
?>
