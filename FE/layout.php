<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$requestedPage = isset($_GET['page']) ? strtolower(trim($_GET['page'])) : 'home';
$requestedPage = preg_replace('/[^a-z0-9-]/', '', $requestedPage);

$routes = [
    'home'           => ['file' => 'pages/home.php',           'access' => 'public'],
    'product-detail' => ['file' => 'pages/product-detail.php', 'access' => 'public'],
    'create-product' => ['file' => 'pages/create-product.php', 'access' => 'user'],
    'login'          => ['file' => 'pages/login.php',          'access' => 'public'],
    'register' => ['file' => 'pages/register.php', 'access' => 'public'],
];

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn && isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'guest';
$pageToLoad = 'pages/not-found.php';

if (isset($routes[$requestedPage])) {
    $requiredAccess = $routes[$requestedPage]['access'];
    $canAccess =
        $requiredAccess === 'public' ||
        ($requiredAccess === 'user' && in_array($userRole, ['user', 'admin'], true));

    if (!$canAccess) {
        header('Location: layout.php?page=login');
        exit();
    }

    if (file_exists(__DIR__ . '/' . $routes[$requestedPage]['file'])) {
        $pageToLoad = $routes[$requestedPage]['file'];
    }
}

include __DIR__ . '/header.php';
include __DIR__ . '/' . $pageToLoad;
include __DIR__ . '/footer.php';