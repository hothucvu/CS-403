<?php
// Phần xử lý giỏ hàng. Được nạp bởi FE/pages/cart.php.
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
$cartItems = [];
$cartTotal = 0.0;
$cartError = '';
$cartNotice = (string) ($_SESSION['cart_notice'] ?? '');
unset($_SESSION['cart_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providedToken = (string) ($_POST['csrf_token'] ?? '');
    $action = (string) ($_POST['cart_action'] ?? '');
    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);

    if (!hash_equals($_SESSION['csrf_token'], $providedToken)) {
        http_response_code(403);
        $cartError = 'Phiên thao tác không hợp lệ. Hãy tải lại trang.';
    } elseif (!$productId || $productId <= 0) {
        $cartError = 'Sản phẩm không hợp lệ.';
    } elseif ($action === 'add') {
        // Chỉ cho mua sản phẩm đang bán, được duyệt và không thuộc chính mình.
        $stmt = $conn->prepare("SELECT id FROM products
                                WHERE id = ? AND user_id <> ?
                                  AND status = 'active'
                                  AND moderation_status = 'approved'
                                  AND transaction_type IN ('sell', 'both') LIMIT 1");
        $stmt->bind_param('ii', $productId, $userId);
        $stmt->execute();
        $isEligible = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$isEligible) {
            $cartError = 'Sản phẩm này không thể thêm vào giỏ hàng.';
        } else {
            $stmt = $conn->prepare('SELECT id FROM carts WHERE user_id = ? AND product_id = ? LIMIT 1');
            $stmt->bind_param('ii', $userId, $productId);
            $stmt->execute();
            $alreadyAdded = (bool) $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$alreadyAdded) {
                $stmt = $conn->prepare('INSERT INTO carts (user_id, product_id) VALUES (?, ?)');
                $stmt->bind_param('ii', $userId, $productId);
                $stmt->execute();
                $stmt->close();
            }
            $_SESSION['cart_notice'] = $alreadyAdded
                ? 'Sản phẩm đã có trong giỏ hàng.'
                : 'Đã thêm sản phẩm vào giỏ hàng.';
            header('Location: layout.php?page=cart');
            exit;
        }
    } elseif ($action === 'remove') {
        $stmt = $conn->prepare('DELETE FROM carts WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $userId, $productId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['cart_notice'] = 'Đã xóa sản phẩm khỏi giỏ hàng.';
        header('Location: layout.php?page=cart');
        exit;
    } else {
        $cartError = 'Thao tác không hợp lệ.';
    }
}

$stmt = $conn->prepare("SELECT c.product_id, p.title, p.price, p.image_url
                        FROM carts c
                        JOIN products p ON p.id = c.product_id
                        WHERE c.user_id = ? AND p.status = 'active'
                          AND p.moderation_status = 'approved'
                          AND p.transaction_type IN ('sell', 'both')
                        ORDER BY c.created_at DESC, c.id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($item = $result->fetch_assoc()) {
    $cartItems[] = $item;
    $cartTotal += (float) $item['price'];
}
$stmt->close();
