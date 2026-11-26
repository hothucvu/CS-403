<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/helpers.php';

$requestedPage = isset($_GET['page']) ? strtolower(trim($_GET['page'])) : 'home';
$requestedPage = preg_replace('/[^a-z0-9-]/', '', $requestedPage);

$routes = [
    'home' => ['file' => 'pages/home.php', 'access' => 'public'],
    'product-detail' => ['file' => 'pages/product-detail.php', 'access' => 'public'],
    'create-product' => ['file' => 'pages/create-product.php', 'access' => 'user'],
    'login' => ['file' => 'pages/login.php', 'access' => 'public'],
    'register' => ['file' => 'pages/register.php', 'access' => 'public'],
    'cart' => ['file' => 'pages/cart.php', 'access' => 'user'],
    'barter' => ['file' => 'pages/barter.php', 'access' => 'user'],
];

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? strtolower((string) ($_SESSION['role'] ?? 'user')) : 'guest';
$pageToLoad = 'pages/not-found.php';

if (isset($routes[$requestedPage])) {
    $requiredAccess = $routes[$requestedPage]['access'];
    $canAccess = $requiredAccess === 'public' ||
        ($requiredAccess === 'user' && $isLoggedIn && in_array($userRole, ['user', 'admin'], true));

    if (!$canAccess) {
        header('Location: layout.php?page=login');
        exit;
    }

    $file = $routes[$requestedPage]['file'];
    if (is_file(__DIR__ . '/' . $file)) {
        $pageToLoad = $file;
    }
}

// Cart POST must run before the HTML header so redirects work.
if ($pageToLoad === 'pages/cart.php') {
    require_once __DIR__ . '/../BE/cart-data.php';
}

// Barter POST must also run before rendering HTML.
if ($pageToLoad === 'pages/barter.php') {
    require_once __DIR__ . '/../BE/barter-data.php';
}

include __DIR__ . '/header.php';
include __DIR__ . '/' . $pageToLoad;
include __DIR__ . '/footer.php';
