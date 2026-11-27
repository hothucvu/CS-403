<?php
// BE/barter-data.php - Xu ly yeu cau trao doi truoc khi render HTML.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $action = (string) ($_POST['barter_action'] ?? '');

    if (!hash_equals((string) $_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        $barterError = 'Phiên thao tác không hợp lệ. Hãy tải lại trang.';
    } elseif ($action === 'create') {
        $offeredId = filter_var($_POST['offered_product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $requestedId = filter_var($_POST['requested_product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (!$offeredId || !$requestedId || $offeredId === $requestedId) {
            $barterError = 'Hãy chọn hai sản phẩm khác nhau.';
        } else {
            // Lay thong tin chu so huu tu CSDL, khong tin receiver_id tu form.
            $stmt = $conn->prepare("SELECT o.user_id AS offered_owner, r.user_id AS requested_owner
                FROM products o JOIN products r ON r.id = ?
                WHERE o.id = ?
                  AND o.status = 'active' AND r.status = 'active'
                  AND o.moderation_status = 'approved' AND r.moderation_status = 'approved'
                  AND o.transaction_type IN ('barter', 'both')
                  AND r.transaction_type IN ('barter', 'both') LIMIT 1");
            $stmt->bind_param('ii', $requestedId, $offeredId);
            $stmt->execute();
            $pair = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$pair || (int) $pair['offered_owner'] !== $userId || (int) $pair['requested_owner'] === $userId) {
                $barterError = 'Yêu cầu không hợp lệ. Bạn chỉ được đề xuất bằng sản phẩm của mình để đổi với người khác.';
            } else {
                $receiverId = (int) $pair['requested_owner'];
                $stmt = $conn->prepare("SELECT id FROM barter_requests
                    WHERE sender_id = ? AND receiver_id = ?
                    AND offered_product_id = ? AND requested_product_id = ?
                    AND status = 'pending' LIMIT 1");
                $stmt->bind_param('iiii', $userId, $receiverId, $offeredId, $requestedId);
                $stmt->execute();
                $duplicate = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($duplicate) {
                    $barterError = 'Bạn đã gửi yêu cầu trao đổi này và đang chờ phản hồi.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO barter_requests
                        (sender_id, receiver_id, offered_product_id, requested_product_id, compensation_amount, status)
                        VALUES (?, ?, ?, ?, 0, 'pending')");
                    $stmt->bind_param('iiii', $userId, $receiverId, $offeredId, $requestedId);
                    $stmt->execute();
                    $stmt->close();
                    $_SESSION['barter_notice'] = 'Đã gửi yêu cầu trao đổi. Hãy chờ chủ sản phẩm phản hồi.';
                    header('Location: layout.php?page=barter');
                    exit;
                }
            }
        }
    } elseif ($action === 'accept' || $action === 'reject') {
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$requestId) {
            $barterError = 'Yêu cầu trao đổi không hợp lệ.';
        } else {
            $canRespond = true;
            if ($action === 'accept') {
                // Chi cho dong y neu hai san pham van hop le va thuoc dung nguoi.
                $stmt = $conn->prepare("SELECT b.id FROM barter_requests b
                    JOIN products o ON o.id = b.offered_product_id
                    JOIN products r ON r.id = b.requested_product_id
                    WHERE b.id = ? AND b.receiver_id = ? AND b.status = 'pending'
                      AND o.user_id = b.sender_id AND r.user_id = b.receiver_id
                      AND o.status = 'active' AND r.status = 'active'
                      AND o.moderation_status = 'approved' AND r.moderation_status = 'approved'
                      AND o.transaction_type IN ('barter', 'both')
                      AND r.transaction_type IN ('barter', 'both') LIMIT 1");
                $stmt->bind_param('ii', $requestId, $userId);
                $stmt->execute();
                $canRespond = (bool) $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            if (!$canRespond) {
                $barterError = 'Sản phẩm đã thay đổi hoặc yêu cầu không còn hợp lệ.';
            } else {
                $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
                $stmt = $conn->prepare("UPDATE barter_requests SET status = ?
                    WHERE id = ? AND receiver_id = ? AND status = 'pending'");
                $stmt->bind_param('sii', $newStatus, $requestId, $userId);
                $stmt->execute();
                $updated = $stmt->affected_rows;
                $stmt->close();

                if ($updated === 1) {
                    $_SESSION['barter_notice'] = $action === 'accept'
                        ? 'Đã đồng ý đề xuất. Hai bên cần xác nhận bàn giao trước khi hoàn tất giao dịch.'
                        : 'Đã từ chối đề xuất trao đổi.';
                    header('Location: layout.php?page=barter');
                    exit;
                }
                $barterError = 'Không thể cập nhật: yêu cầu đã được xử lý hoặc không thuộc về bạn.';
            }
        }
    } else {
        $barterError = 'Thao tác không hợp lệ.';
    }
}

// Danh sach do nguoi dung co the mang di trao doi.
$stmt = $conn->prepare("SELECT id, title, price FROM products
    WHERE user_id = ? AND status = 'active' AND moderation_status = 'approved'
      AND transaction_type IN ('barter', 'both') ORDER BY id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$myBarterProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Danh sach do cua nguoi khac de yeu cau trao doi.
$stmt = $conn->prepare("SELECT p.id, p.title, p.price, u.fullname AS owner_name
    FROM products p JOIN users u ON u.id = p.user_id
    WHERE p.user_id <> ? AND p.status = 'active' AND p.moderation_status = 'approved'
      AND p.transaction_type IN ('barter', 'both') ORDER BY p.id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$otherBarterProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Danh sach de nghi: khong tu do thay doi chu so huu qua du lieu POST.
$sql = "SELECT b.id, b.status, b.created_at, b.compensation_amount,
               o.title AS offered_title, r.title AS requested_title,
               sender.fullname AS sender_name, receiver.fullname AS receiver_name
        FROM barter_requests b
        JOIN products o ON o.id = b.offered_product_id
        JOIN products r ON r.id = b.requested_product_id
        JOIN users sender ON sender.id = b.sender_id
        JOIN users receiver ON receiver.id = b.receiver_id
        WHERE b.receiver_id = ? ORDER BY b.created_at DESC, b.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$incomingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$sql = str_replace('WHERE b.receiver_id = ?', 'WHERE b.sender_id = ?', $sql);
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$outgoingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
