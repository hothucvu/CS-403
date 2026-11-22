
<?php
// BE/logout-data.php
// Xử lý đăng xuất người dùng

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Xóa dữ liệu trong Session
    $_SESSION = [];

    // Xóa cookie Session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    // Hủy Session
    session_destroy();

    $statusMessage = 'Đăng xuất thành công!';
} else {
    http_response_code(405);
    $statusMessage = 'Phương thức không hợp lệ.';
}
?>
