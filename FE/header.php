
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>ReUse - Chợ đồ cũ thông minh</title>

    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/layout.css">
</head>
<body>

<header class="reuse-header">
    <div class="header-container">

        <!-- Logo -->
        <a href="layout.php?page=home" class="logo">
            <span class="logo-icon">R</span>
            <span class="logo-text">
                Re<span>Use</span>
            </span>
        </a>

        <!-- Tim kiem -->
        <form action="layout.php"
              method="GET"
              class="header-search">

            <input type="hidden"
                   name="page"
                   value="home">

            <input type="text"
                   name="q"
                   placeholder="Tìm kiếm sản phẩm cũ...">

            <button type="submit">
                Tìm kiếm
            </button>
        </form>

        <!-- Menu -->
        <nav class="header-nav">

            <a href="layout.php?page=home"
               class="nav-link">
                Trang chủ
            </a>

            <a href="layout.php?page=home"
               class="nav-link">
                Sản phẩm
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>

                <a href="layout.php?page=cart"
                   class="nav-link">
                    🛒 Giỏ hàng
                </a>

                <a href="layout.php?page=barter"
                   class="nav-link">
                    Trao đổi
                </a>

                <a href="layout.php?page=create-product"
                   class="nav-link">
                    Đăng bán
                </a>

                <a href="layout.php?page=my-products" class="nav-link">Tin của tôi</a>
                <a href="layout.php?page=my-orders" class="nav-link">Đơn mua</a>
                <a href="layout.php?page=wallet" class="nav-link">Ví điểm</a>
                <a href="layout.php?page=profile" class="nav-link">Tài khoản</a>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="layout.php?page=admin-products" class="nav-link">Admin sản phẩm</a>
                    <a href="layout.php?page=admin-orders" class="nav-link">Admin đơn</a>
                    <a href="layout.php?page=admin-wallet" class="nav-link">Admin điểm</a>
                <?php endif; ?>

                <!-- Dang xuat -->
                <form action="../BE/logout-data.php"
                      method="POST"
                      style="display:inline; margin:0;">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?php echo htmlspecialchars(
                               $_SESSION['csrf_token'] ?? '',
                               ENT_QUOTES,
                               'UTF-8'
                           ); ?>">

                    <button type="submit"
                            class="nav-link"
                            style="background:none;
                                   border:none;
                                   cursor:pointer;
                                   font:inherit;">
                        Đăng xuất
                    </button>
                </form>

            <?php else: ?>

                <a href="layout.php?page=login"
                   class="nav-link">
                    Đăng nhập
                </a>

                <a href="layout.php?page=register"
                   class="nav-button">
                    Đăng ký
                </a>

            <?php endif; ?>

        </nav>
    </div>
</header>

<main class="main-content">
    <div class="content-container">
