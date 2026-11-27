<?php
// Đăng và sửa tin bán/trao đổi. Chạy trước header vì có chuyển hướng PRG.
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['user_id'])) { header('Location: layout.php?page=login'); exit; }

$listingUserId = (int) $_SESSION['user_id'];
$listingError = '';
$editingId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$editingId = $editingId && $editingId > 0 ? (int) $editingId : 0;
$editProduct = null;
$listingCategories = [];

$categoryResult = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');
while ($row = $categoryResult->fetch_assoc()) { $listingCategories[] = $row; }

if ($editingId > 0) {
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $editingId, $listingUserId);
    $stmt->execute(); $editProduct = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$editProduct) { http_response_code(404); $listingError = 'Không tìm thấy tin đăng thuộc tài khoản của bạn.'; }
    elseif (!in_array($editProduct['status'], ['active', 'hidden'], true)) {
        $listingError = 'Sản phẩm đã bán hoặc trao đổi không thể chỉnh sửa.';
    }
}

$listingForm = $editProduct ?: [
    'title' => '', 'description' => '', 'price' => '', 'category_id' => '',
    'condition_percent' => '90', 'transaction_type' => 'sell', 'image_url' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $listingError === '') {
    $listingForm = array_merge($listingForm, [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'price' => trim((string) ($_POST['price'] ?? '')),
        'category_id' => (string) ($_POST['category_id'] ?? ''),
        'condition_percent' => (string) ($_POST['condition_percent'] ?? ''),
        'transaction_type' => (string) ($_POST['transaction_type'] ?? '')
    ]);
    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        $listingError = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
    } elseif (mb_strlen($listingForm['title']) < 5 || mb_strlen($listingForm['title']) > 200) {
        $listingError = 'Tên sản phẩm phải từ 5 đến 200 ký tự.';
    } elseif (mb_strlen($listingForm['description']) < 10 || mb_strlen($listingForm['description']) > 5000) {
        $listingError = 'Mô tả sản phẩm phải từ 10 đến 5000 ký tự.';
    } elseif (!is_numeric($listingForm['price']) || (float)$listingForm['price'] < 0 || (float)$listingForm['price'] > 999999999) {
        $listingError = 'Giá sản phẩm phải từ 0 đến 999.999.999đ.';
    } elseif (filter_var($listingForm['condition_percent'], FILTER_VALIDATE_INT) === false || (int)$listingForm['condition_percent'] < 1 || (int)$listingForm['condition_percent'] > 100) {
        $listingError = 'Độ mới phải từ 1% đến 100%.';
    } elseif (!in_array($listingForm['transaction_type'], ['sell', 'barter', 'both'], true)) {
        $listingError = 'Hình thức giao dịch không hợp lệ.';
    } else {
        $categoryId = filter_var($listingForm['category_id'], FILTER_VALIDATE_INT);
        $validCategoryIds = array_map(static fn($cat) => (int)$cat['id'], $listingCategories);
        if (!$categoryId || !in_array((int)$categoryId, $validCategoryIds, true)) {
            $listingError = 'Vui lòng chọn danh mục hợp lệ.';
        }
    }

    $newFilePath = null;
    $imageUrl = (string) ($editProduct['image_url'] ?? '');
    if ($listingError === '') {
        $uploadError = (int) ($_FILES['product_image']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            if ($editingId === 0) { $listingError = 'Vui lòng chọn một ảnh sản phẩm.'; }
        } elseif ($uploadError !== UPLOAD_ERR_OK) {
            $listingError = 'Không thể tải ảnh lên. Vui lòng chọn ảnh khác.';
        } elseif ((int) ($_FILES['product_image']['size'] ?? 0) > 5 * 1024 * 1024) {
            $listingError = 'Ảnh sản phẩm tối đa 5 MB.';
        } else {
            $temporary = (string) $_FILES['product_image']['tmp_name'];
            if (!is_uploaded_file($temporary)) {
                $listingError = 'Tệp tải lên không hợp lệ.';
            } else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime]) || !@getimagesize($temporary)) {
                    $listingError = 'Ảnh chỉ hỗ trợ JPG, PNG hoặc WEBP.';
                } else {
                    $uploadDirectory = __DIR__ . '/../FE/assets/uploads';
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
                        $listingError = 'Không tạo được thư mục lưu ảnh.';
                    } else {
                        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                        $newFilePath = $uploadDirectory . '/' . $filename;
                        if (!move_uploaded_file($temporary, $newFilePath)) {
                            $listingError = 'Không thể lưu ảnh trên máy chủ.';
                            $newFilePath = null;
                        } else {
                            $imageUrl = './assets/uploads/' . $filename;
                        }
                    }
                }
            }
        }
    }

    if ($listingError === '') {
        try {
            $price = (float) $listingForm['price'];
            $condition = (int) $listingForm['condition_percent'];
            $categoryId = (int) $listingForm['category_id'];
            $type = $listingForm['transaction_type'];
            $title = $listingForm['title'];
            $description = $listingForm['description'];
            if ($editingId > 0) {
                $stmt = $conn->prepare("UPDATE products SET category_id = ?, title = ?, description = ?, image_url = ?, price = ?, condition_percent = ?, transaction_type = ?, moderation_status = 'pending' WHERE id = ? AND user_id = ? AND status IN ('active','hidden')");
                $stmt->bind_param('isssdisii', $categoryId, $title, $description, $imageUrl, $price, $condition, $type, $editingId, $listingUserId);
            } else {
                $stmt = $conn->prepare("INSERT INTO products (user_id, category_id, title, description, image_url, price, condition_percent, transaction_type, moderation_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'active')");
                $stmt->bind_param('iisssdis', $listingUserId, $categoryId, $title, $description, $imageUrl, $price, $condition, $type);
            }
            $stmt->execute(); $stmt->close();
            $_SESSION['my_products_notice'] = $editingId > 0 ? 'Đã cập nhật tin đăng.' : 'Đăng sản phẩm thành công!';
            header('Location: layout.php?page=my-products'); exit;
        } catch (mysqli_sql_exception $exception) {
            error_log('Create/edit listing: ' . $exception->getMessage());
            if ($newFilePath !== null && is_file($newFilePath)) { unlink($newFilePath); }
            $listingError = 'Không thể lưu sản phẩm. Vui lòng kiểm tra database.';
        }
    }
}
