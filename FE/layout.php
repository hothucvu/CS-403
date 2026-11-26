
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/helpers.php';

// Lấy trang người dùng yêu cầu
$requestedPage = isset($_GET['page'])
    ? strtolower(trim($_GET['page']))
    : 'home';

$requestedPage = preg_replace('/[^a-z0-9-]/', '', $requestedPage);

// Khai báo các trang
$routes = [
    'home' => [
        'file' => 'pages/home.php',
        'access' => 'public'
    ],

    'product-detail' => [
        'file' => 'pages/productdetail.php',
        'access' => 'public'
    ],

    'create-product' => [
        'file' => 'pages/create-product.php',
        'access' => 'user'
    ],

    'login' => [
        'file' => 'pages/login.php',
        'access' => 'public'
    ],

    'register' => [
        'file' => 'pages/register.php',
        'access' => 'public'
    ],

    'cart' => [
        'file' => 'pages/cart.php',
        'access' => 'user'
    ],

    'barter' => [
        'file' => 'pages/barter.php',
        'access' => 'user'
    ]
];

// Kiểm tra đăng nhập
$isLoggedIn = isset($_SESSION['user_id']);

$userRole = $isLoggedIn
    ? strtolower((string) ($_SESSION['role'] ?? 'user'))
    : 'guest';

// Trang mặc định nếu không tìm thấy
$pageToLoad = 'pages/not-found.php';

// Kiểm tra route và quyền truy cập
if (isset($routes[$requestedPage])) {

    $requiredAccess = $routes[$requestedPage]['access'];

    $canAccess =
        $requiredAccess === 'public' ||
        (
            $requiredAccess === 'user' &&
            $isLoggedIn &&
            in_array($userRole, ['user', 'admin'], true)
        );

    if (!$canAccess) {
        header('Location: layout.php?page=login');
        exit;
    }

    $file = $routes[$requestedPage]['file'];

    if (is_file(__DIR__ . '/' . $file)) {
        $pageToLoad = $file;
    }
}

// Xử lý giỏ hàng trước khi xuất HTML
if ($pageToLoad === 'pages/cart.php') {
    require_once __DIR__ . '/../BE/cart-data.php';
}

// Xử lý Barter trước khi xuất HTML
if ($pageToLoad === 'pages/barter.php') {
    require_once __DIR__ . '/../BE/barter-data.php';
}

// Hiển thị giao diện
include __DIR__ . '/header.php';
include __DIR__ . '/' . $pageToLoad;
include __DIR__ . '/footer.php';
