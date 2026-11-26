<?php
// BE/barter-data.php: xu ly yeu cau trao doi, nap truoc FE/header.php.
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: layout.php?page=login');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int) $_SESSION['user_id'];
$barterError = '';
$barterNotice = (string) ($_SESSION['barter_notice'] ?? '');
unset($_SESSION['barter_notice']);
$myBarterProducts = [];
$otherBarterProducts = [];
$incomingRequests = [];
$outgoingRequests = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $action = (string) ($_POST['barter_action'] ?? '');

        if (!hash_equals((string) $_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            $barterError = 'Phiên thao tác không hợp lệ. Hãy tải lại trang.';
        } elseif ($action === 'create') {
            $offeredId = filter_var($_POST['offered_product_id'] ?? null, FILTER_VALIDATE_INT);
            $requestedId = filter_var($_POST['requested_product_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$offeredId || !$requestedId || $offeredId === $requestedId) {
                $barterError = 'Bạn cần chọn hai sản phẩm khác nhau.';
            } else {
                // Khong tin vao product_id hoac receiver_id gui tu client.
                $sql = "SELECT id, user_id FROM products
                        WHERE id IN (?, ?)
                          AND status = 'active'
                          AND moderation_status = 'approved'
                          AND transaction_type IN ('barter', 'both')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $offeredId, $requestedId);
                $stmt->execute();
                $result = $stmt->get_result();
                $products = [];
                while ($row = $result->fetch_assoc()) {
                    $products[(int) $row['id']] = (int) $row['user_id'];
                }
                $stmt->close();

                if (count($products) !== 2 ||
                    ($products[$offeredId] ?? 0) !== $userId ||
                    ($products[$requestedId] ?? 0) === $userId) {
                    $barterError = 'Sản phẩm không hợp lệ hoặc bạn không có quyền trao đổi.';
                } else {
                    $receiverId = $products[$requestedId];
                    $stmt = $conn->prepare(
                        "SELECT id FROM barter_requests
                         WHERE sender_id = ? AND offered_product_id = ? AND requested_product_id = ?
                           AND status IN ('pending', 'accepted') LIMIT 1"
                    );
                    $stmt->bind_param('iii', $userId, $offeredId, $requestedId);
                    $stmt->execute();
                    $exists = (bool) $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ($exists) {
                        $barterError = 'Bạn đã gửi yêu cầu trao đổi này rồi.';
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO barter_requests
                               (sender_id, receiver_id, offered_product_id, requested_product_id, compensation_amount, status)
                             VALUES (?, ?, ?, ?, 0, 'pending')"
                        );
                        $stmt->bind_param('iiii', $userId, $receiverId, $offeredId, $requestedId);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION['barter_notice'] = 'Đã gửi yêu cầu trao đổi. Đang chờ phản hồi.';
                        header('Location: layout.php?page=barter');
                        exit;
                    }
                }
            }
        } elseif ($action === 'accept' || $action === 'reject') {
            $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$requestId) {
                $barterError = 'Yêu cầu không hợp lệ.';
            } else {
                $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
                // Chi chu san pham duoc phan hoi yeu cau dang cho.
                // Truoc khi chap nhan, san pham phai van hop le va dung chu so huu.
                if ($action === 'accept') {
                    $sql = "UPDATE barter_requests b
                            JOIN products offered ON offered.id = b.offered_product_id
                            JOIN products requested ON requested.id = b.requested_product_id
                            SET b.status = ?
                            WHERE b.id = ? AND b.receiver_id = ? AND b.status = 'pending'
                              AND offered.user_id = b.sender_id
                              AND requested.user_id = b.receiver_id
                              AND offered.status = 'active' AND requested.status = 'active'
                              AND offered.moderation_status = 'approved'
                              AND requested.moderation_status = 'approved'
                              AND offered.transaction_type IN ('barter', 'both')
                              AND requested.transaction_type IN ('barter', 'both')";
                } else {
                    $sql = "UPDATE barter_requests SET status = ?
                            WHERE id = ? AND receiver_id = ? AND status = 'pending'";
                }
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sii', $newStatus, $requestId, $userId);
                $stmt->execute();
                $updated = $stmt->affected_rows;
                $stmt->close();
                if ($updated === 1) {
                    $_SESSION['barter_notice'] = $action === 'accept'
                        ? 'Bạn đã đồng ý yêu cầu trao đổi.' : 'Bạn đã từ chối yêu cầu trao đổi.';
                    header('Location: layout.php?page=barter');
                    exit;
                }
                $barterError = 'Không thể cập nhật yêu cầu. Yêu cầu có thể đã được xử lý hoặc sản phẩm không còn hợp lệ.';
            }
        } else {
            $barterError = 'Thao tác không được hỗ trợ.';
        }
    }

    $stmt = $conn->prepare(
        "SELECT id, title, price FROM products
         WHERE user_id = ? AND status = 'active' AND moderation_status = 'approved'
           AND transaction_type IN ('barter', 'both') ORDER BY id DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $myBarterProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT p.id, p.title, p.price, u.fullname AS owner_name
         FROM products p JOIN users u ON u.id = p.user_id
         WHERE p.user_id <> ? AND p.status = 'active' AND p.moderation_status = 'approved'
           AND p.transaction_type IN ('barter', 'both') ORDER BY p.id DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $otherBarterProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT b.id, b.status, b.created_at,
                sender.fullname AS sender_name,
                offered.title AS offered_title, requested.title AS requested_title
         FROM barter_requests b
         JOIN users sender ON sender.id = b.sender_id
         JOIN products offered ON offered.id = b.offered_product_id
         JOIN products requested ON requested.id = b.requested_product_id
         WHERE b.receiver_id = ? ORDER BY b.created_at DESC, b.id DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $incomingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT b.id, b.status, b.created_at,
                receiver.fullname AS receiver_name,
                offered.title AS offered_title, requested.title AS requested_title
         FROM barter_requests b
         JOIN users receiver ON receiver.id = b.receiver_id
         JOIN products offered ON offered.id = b.offered_product_id
         JOIN products requested ON requested.id = b.requested_product_id
         WHERE b.sender_id = ? ORDER BY b.created_at DESC, b.id DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $outgoingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Barter SQL error: ' . $exception->getMessage());
    http_response_code(500);
    $barterError = 'Có lỗi khi xử lý dữ liệu trao đổi. Hãy kiểm tra cấu trúc bảng barter_requests trong MySQL.';
}
