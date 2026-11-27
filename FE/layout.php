<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
require_once __DIR__ . '/includes/helpers.php';

$requestedPage = preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string)($_GET['page'] ?? 'home'))));
$routes = [
    'home' => ['file'=>'pages/home.php','access'=>'public'],
    'product-detail' => ['file'=>'pages/productdetail.php','access'=>'public'],
    'create-product' => ['file'=>'pages/create-product.php','access'=>'user'],
    'my-products' => ['file'=>'pages/my-products.php','access'=>'user'],
    'profile' => ['file'=>'pages/profile.php','access'=>'user'],
    'login' => ['file'=>'pages/login.php','access'=>'public'],
    'register' => ['file'=>'pages/register.php','access'=>'public'],
    'cart' => ['file'=>'pages/cart.php','access'=>'user'],
    'barter' => ['file'=>'pages/barter.php','access'=>'user'],
    'checkout' => ['file'=>'pages/checkout.php','access'=>'user'],
    'my-orders' => ['file'=>'pages/my-orders.php','access'=>'user'],
    'order-detail' => ['file'=>'pages/order-detail.php','access'=>'user'],
    'wallet' => ['file'=>'pages/wallet.php','access'=>'user'],
    'admin-products' => ['file'=>'pages/admin-products.php','access'=>'admin'],
    'admin-orders' => ['file'=>'pages/admin-orders.php','access'=>'admin'],
    'admin-wallet' => ['file'=>'pages/admin-wallet.php','access'=>'admin'],
];
$isLoggedIn = (int)($_SESSION['user_id'] ?? 0) > 0;
$userRole = $isLoggedIn ? strtolower((string)($_SESSION['role'] ?? 'user')) : 'guest';
$pageToLoad = 'pages/not-found.php';
if (isset($routes[$requestedPage])) {
    $access = $routes[$requestedPage]['access'];
    if ($access === 'user' && !$isLoggedIn) { header('Location: layout.php?page=login');exit; }
    if ($access === 'admin' && !$isLoggedIn) { header('Location: layout.php?page=login');exit; }
    if ($access === 'admin' && $userRole !== 'admin') { http_response_code(403);exit('Không có quyền quản trị.'); }
    $file = $routes[$requestedPage]['file'];
    if (is_file(__DIR__ . '/' . $file)) $pageToLoad = $file;
}
// Controller chạy TRƯỚC khi xuất HTML để session, cookie và chuyển hướng hoạt động.
$controllers = [
    'pages/login.php' => '../BE/login-data.php',
    'pages/register.php' => '../BE/register-data.php',
    'pages/cart.php' => '../BE/cart-data.php',
    'pages/barter.php' => '../BE/barter-data.php',
    'pages/create-product.php' => '../BE/create-product-data.php',
    'pages/my-products.php' => '../BE/my-products-data.php',
    'pages/profile.php' => '../BE/profile-data.php',
    'pages/checkout.php' => '../BE/checkout-data.php',
    'pages/my-orders.php' => '../BE/orders-data.php',
    'pages/order-detail.php' => '../BE/orders-data.php',
    'pages/wallet.php' => '../BE/wallet-data.php',
    'pages/admin-products.php' => '../BE/admin-products-data.php',
    'pages/admin-orders.php' => '../BE/admin-orders-data.php',
    'pages/admin-wallet.php' => '../BE/wallet-data.php',
];
if (isset($controllers[$pageToLoad])) require_once __DIR__ . '/' . $controllers[$pageToLoad];
include __DIR__ . '/header.php';
include __DIR__ . '/' . $pageToLoad;
include __DIR__ . '/footer.php';
