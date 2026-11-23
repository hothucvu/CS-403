
<?php
require_once __DIR__ . '/../../BE/login-data.php';
?>

<link rel="stylesheet" href="./assets/css/auth.css">

<div class="auth-container">
    <h2>Đăng nhập SmartTrade</h2>

    <?php if ($statusMessage !== ''): ?>
        <p class="<?php echo $isError ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <?php if ($loginSuccess): ?>
        <p>Đăng nhập thành công!</p>
        <a href="layout.php?page=home">Về trang chủ</a>
    <?php else: ?>

        <form method="POST" action="layout.php?page=login">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label>Email</label>
            <input type="email"
                   name="email"
                   placeholder="Nhập email"
                   required>

            <label>Mật khẩu</label>
            <input type="password"
                   name="password"
                   placeholder="Nhập mật khẩu"
                   required>

            <button type="submit">Đăng nhập</button>
        </form>

        <p>Chưa có tài khoản?
            <a href="layout.php?page=register">Đăng ký ngay</a>
        </p>

    <?php endif; ?>
</div>
