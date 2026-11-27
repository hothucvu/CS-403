<?php
require_once __DIR__ . '/db.php';

$searchKeyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$productList = [];
$statusMessage = '';
$isError = false;

if (!isset($conn) || $conn->connect_error) {
    $isError = true;
    $statusMessage = 'Không thể kết nối cơ sở dữ liệu.';
} elseif ($searchKeyword !== '') {
    $likeKeyword = '%' . $searchKeyword . '%';
    $sql = "SELECT id, title, price, description, image_url
            FROM products
            WHERE title LIKE ? OR description LIKE ?
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $likeKeyword, $likeKeyword);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $productList[] = $row;
    }
    $stmt->close();

    if (empty($productList)) {
        $statusMessage = 'Không tìm thấy sản phẩm phù hợp với "' . $searchKeyword . '".';
    }
} else {
    $sql = "SELECT id, title, price, description, image_url
            FROM products
            ORDER BY id DESC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $productList[] = $row;
        }
        if (empty($productList)) {
            $statusMessage = 'Chưa có sản phẩm nào.';
        }
    } else {
        $isError = true;
        $statusMessage = 'Lỗi truy vấn dữ liệu.';
    }
}