<?php
// Tiện ích chung cho phần hệ thống sản phẩm, đơn hàng và quản trị.
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function bao_user_id(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}
function bao_require_login(): int {
    $userId = bao_user_id();
    if ($userId < 1) {
        header('Location: layout.php?page=login');
        exit;
    }
    return $userId;
}
function bao_require_admin(mysqli $conn): int {
    $id = bao_require_login();
    // Không chỉ tin vào role phía client/session.
    $stmt = $conn->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $role = $stmt->get_result()->fetch_column();
    $stmt->close();
    if ($role !== 'admin') {
        http_response_code(403);
        exit('Bạn không có quyền truy cập khu vực quản trị.');
    }
    return $id;
}
function bao_csrf_ok(): bool {
    return hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''));
}
function bao_flash(string $type, string $message): void {
    $_SESSION['bao_flash'] = ['type' => $type, 'message' => $message];
}
function bao_pull_flash(): ?array {
    $message = $_SESSION['bao_flash'] ?? null;
    unset($_SESSION['bao_flash']);
    return is_array($message) ? $message : null;
}
function bao_redirect(string $page, array $params = []): never {
    header('Location: layout.php?' . http_build_query(['page' => $page] + $params));
    exit;
}
function bao_int(mixed $v, int $min=1): int {
    $i = filter_var($v, FILTER_VALIDATE_INT);
    return $i !== false && $i >= $min ? $i : 0;
}
function bao_money(float $v): string {
    return number_format($v, 0, ',', '.') . ' đ';
}
function bao_image_src(string $value): string {
    // Sản phẩm cũ lưu ./assets/image/...; file upload từ module khác cũng cùng dạng.
    $value = trim($value);
    if ($value === '' || str_contains($value, '..') || preg_match('/[\x00-\x1F]/', $value)) {
        return './assets/image/tai-nghe-bluetooth.png';
    }
    if (str_starts_with($value, './assets/image/') || str_starts_with($value, './assets/uploads/')) {
        return $value;
    }
    return './assets/image/tai-nghe-bluetooth.png';
}
