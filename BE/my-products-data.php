<?php
// Quản lý tin đăng của tài khoản. Tệp này được nạp trước header từ FE/layout.php.
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: layout.php?page=login');
    exit;
}

$myUserId = (int) $_SESSION['user_id'];
$myProductsError = '';
$myProductsNotice = (string) ($_SESSION['my_products_notice'] ?? '');
unset($_SESSION['my_products_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expectedToken = (string) ($_SESSION['csrf_token'] ?? '');
    $providedToken = (string) ($_POST['csrf_token'] ?? '');
    $id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
    $action = (string) ($_POST['listing_action'] ?? '');

    if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        $myProductsError = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
    } elseif (!$id || $id <= 0 || !in_array($action, ['hide', 'activate', 'delete'], true)) {
        $myProductsError = 'Yêu cầu không hợp lệ.';
    } elseif ($action === 'delete') {
        // Chỉ xóa sản phẩm thuộc tài khoản, chưa bán/đổi và không có lịch sử giao dịch.
        // Dùng transaction + FOR UPDATE để tránh tác vụ đặt hàng/trao đổi xen ngang.
        $transactionStarted = false;
        try {
            $conn->begin_transaction();
            $transactionStarted = true;

            $stmt = $conn->prepare(
                'SELECT status FROM products WHERE id = ? AND user_id = ? FOR UPDATE'
            );
            $stmt->bind_param('ii', $id, $myUserId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$product) {
                $myProductsError = 'Không tìm thấy tin đăng thuộc tài khoản của bạn.';
            } elseif (!in_array($product['status'], ['active', 'hidden'], true)) {
                $myProductsError = 'Không thể xóa sản phẩm đã bán hoặc đã trao đổi.';
            } else {
                // KHÔNG xóa dữ liệu lịch sử: order_items và mọi yêu cầu Barter (kể cả đã từ chối).
                $stmt = $conn->prepare('SELECT id FROM order_items WHERE product_id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $hasOrder = (bool) $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $stmt = $conn->prepare(
                    'SELECT id FROM barter_requests WHERE offered_product_id = ? OR requested_product_id = ? LIMIT 1'
                );
                $stmt->bind_param('ii', $id, $id);
                $stmt->execute();
                $hasBarter = (bool) $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($hasOrder || $hasBarter) {
                    $myProductsError = 'Sản phẩm đã liên quan đến đơn hàng hoặc yêu cầu trao đổi. Hãy dùng Ẩn tin để giữ lịch sử giao dịch.';
                } else {
                    // Giỏ hàng chỉ là dữ liệu tạm: xóa sản phẩm sẽ tự gỡ nó khỏi các giỏ hàng.
                    $stmt = $conn->prepare('DELETE FROM carts WHERE product_id = ?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare(
                        "DELETE FROM products WHERE id = ? AND user_id = ? AND status IN ('active', 'hidden')"
                    );
                    $stmt->bind_param('ii', $id, $myUserId);
                    $stmt->execute();
                    $deleted = $stmt->affected_rows === 1;
                    $stmt->close();

                    if (!$deleted) {
                        throw new RuntimeException('Product deletion affected no rows.');
                    }
                }
            }

            if ($myProductsError === '') {
                $conn->commit();
                $transactionStarted = false;
                $_SESSION['my_products_notice'] = 'Đã xóa tin đăng thành công.';
                header('Location: layout.php?page=my-products');
                exit;
            }
            $conn->rollback();
            $transactionStarted = false;
        } catch (Throwable $exception) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            error_log('Delete listing: ' . $exception->getMessage());
            $myProductsError = 'Không thể xóa tin đăng. Vui lòng thử lại hoặc sử dụng Ẩn tin.';
        }
    } else {
        // Ẩn / hiện chỉ áp dụng với trạng thái active/hidden. Không hồi sinh sản phẩm đã bán.
        $newStatus = $action === 'hide' ? 'hidden' : 'active';
        $oldStatus = $action === 'hide' ? 'active' : 'hidden';
        try {
            $stmt = $conn->prepare(
                'UPDATE products SET status = ? WHERE id = ? AND user_id = ? AND status = ?'
            );
            $stmt->bind_param('siis', $newStatus, $id, $myUserId, $oldStatus);
            $stmt->execute();
            $changed = $stmt->affected_rows;
            $stmt->close();
            $_SESSION['my_products_notice'] = $changed === 1
                ? 'Đã cập nhật trạng thái tin đăng.'
                : 'Không thể thay đổi tin đăng này.';
            header('Location: layout.php?page=my-products');
            exit;
        } catch (mysqli_sql_exception $exception) {
            error_log('My listings: ' . $exception->getMessage());
            $myProductsError = 'Không thể cập nhật tin đăng.';
        }
    }
}

$stmt = $conn->prepare(
    "SELECT p.id, p.title, p.price, p.image_url, p.transaction_type,
               p.moderation_status, p.status, p.created_at,
               (EXISTS(SELECT 1 FROM order_items oi WHERE oi.product_id = p.id)
                OR EXISTS(SELECT 1 FROM barter_requests br
                          WHERE br.offered_product_id = p.id OR br.requested_product_id = p.id)) AS has_history
        FROM products p WHERE p.user_id = ? ORDER BY p.id DESC"
);
$stmt->bind_param('i', $myUserId);
$stmt->execute();
$myListings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
