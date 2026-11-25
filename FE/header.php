
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReUse - Chợ đồ cũ thông minh</title>

    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/layout.css">
</head>
<body>

<header class="reuse-header">
    <div class="header-container">

        <a href="layout.php?page=home" class="logo">
            <span class="logo-icon">R</span>
            <span class="logo-text">Re<span>Use</span></span>
        </a>

        <form action="layout.php" method="GET" class="header-search">
            <input type="hidden" name="page" value="home">
            <input
                type="text"
                name="q"
                placeholder="Tìm kiếm sản phẩm cũ..."
                aria-label="Tìm kiếm sản phẩm"
            >
            <button type="submit">Tìm kiếm</button>
        </form>

        <nav class="header-nav">
            <a href="layout.php?page=home" class="nav-link">Trang chủ</a>
            <a href="layout.php?page=home" class="nav-link">Sản phẩm</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="layout.php?page=create-product" class="nav-link">Đăng bán</a>
                <a href="#" class="nav-link">Tài khoản</a>
            <?php else: ?>
                <a href="layout.php?page=login" class="nav-link">Đăng nhập</a>
                <a href="layout.php?page=register" class="nav-button">Đăng ký</a>
            <?php endif; ?>
        </nav>

    </div>
</header>

<main class="main-content">
    <div class="content-container">
