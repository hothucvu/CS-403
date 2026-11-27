<?php
// Quản lý hồ sơ người dùng ReUse. Chạy trước FE/header.php để hỗ trợ redirect.
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['user_id'])) { header('Location: layout.php?page=login'); exit; }

$profileUserId = (int) $_SESSION['user_id'];
$profileError = '';
$profileNotice = (string) ($_SESSION['profile_notice'] ?? '');
unset($_SESSION['profile_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        $profileError = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
    } else {
        $action = (string) ($_POST['profile_action'] ?? '');
        try {
            if ($action === 'update_info') {
                $fullname = trim((string) ($_POST['fullname'] ?? ''));
                $phone = trim((string) ($_POST['phone'] ?? ''));
                $address = trim((string) ($_POST['address'] ?? ''));
                if ($fullname === '' || mb_strlen($fullname) > 100) {
                    $profileError = 'Họ tên không được để trống hoặc vượt quá 100 ký tự.';
                } elseif (strlen($phone) > 20 || ($phone !== '' && !preg_match('/^[0-9+().\s-]{8,20}$/', $phone))) {
                    $profileError = 'Số điện thoại không hợp lệ.';
                } elseif (mb_strlen($address) > 500) {
                    $profileError = 'Địa chỉ tối đa 500 ký tự.';
                } else {
                    $stmt = $conn->prepare('UPDATE users SET fullname = ?, phone = ?, address = ? WHERE id = ?');
                    $stmt->bind_param('sssi', $fullname, $phone, $address, $profileUserId);
                    $stmt->execute();
                    $stmt->close();
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['profile_notice'] = 'Đã cập nhật thông tin cá nhân.';
                    header('Location: layout.php?page=profile'); exit;
                }
            } elseif ($action === 'change_password') {
                $old = (string) ($_POST['current_password'] ?? '');
                $new = (string) ($_POST['new_password'] ?? '');
                $confirm = (string) ($_POST['confirm_password'] ?? '');
                if (strlen($new) < 8 || strlen($new) > 72) {
                    $profileError = 'Mật khẩu mới phải dài từ 8 đến 72 ký tự.';
                } elseif ($new !== $confirm) {
                    $profileError = 'Xác nhận mật khẩu mới không khớp.';
                } else {
                    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
                    $stmt->bind_param('i', $profileUserId);
                    $stmt->execute();
                    $record = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if (!$record || !password_verify($old, $record['password'])) {
                        $profileError = 'Mật khẩu hiện tại không đúng.';
                    } else {
                        $hash = password_hash($new, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                        $stmt->bind_param('si', $hash, $profileUserId);
                        $stmt->execute(); $stmt->close();
                        $_SESSION['profile_notice'] = 'Đổi mật khẩu thành công.';
                        header('Location: layout.php?page=profile'); exit;
                    }
                }
            } else {
                $profileError = 'Thao tác không được hỗ trợ.';
            }
        } catch (mysqli_sql_exception $exception) {
            error_log('Profile error: ' . $exception->getMessage());
            $profileError = 'Không thể lưu dữ liệu. Vui lòng thử lại.';
        }
    }
}

$stmt = $conn->prepare('SELECT id, fullname, email, phone, address, role, reputation_score, barter_wallet, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $profileUserId);
$stmt->execute();
$profileUser = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$profileUser) { http_response_code(404); $profileError = 'Tài khoản không tồn tại.'; }
