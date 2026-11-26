<?php require_once __DIR__ . '/../../BE/register-data.php'; ?>
<link rel="stylesheet" href="./assets/css/auth.css">
<section class="auth-container">
    <h1>Tạo tài khoản ReUse</h1>
    <p class="auth-subtitle">Đăng ký để lưu sản phẩm vào giỏ hàng.</p>
    <?php if ($statusMessage !== ''): ?>
        <p class="auth-message <?php echo $isError ? 'auth-error' : 'auth-success'; ?>"><?php echo e($statusMessage); ?></p>
    <?php endif; ?>
    <?php if ($registrationSuccess): ?>
        <p><a href="layout.php?page=login">Đến trang đăng nhập</a></p>
    <?php else: ?>
        <form method="post" action="layout.php?page=register" class="auth-form">
            <label for="fullname">Họ và tên</label>
            <input id="fullname" name="fullname" required maxlength="100" value="<?php echo e($_POST['fullname'] ?? ''); ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required maxlength="100" value="<?php echo e($_POST['email'] ?? ''); ?>">
            <label for="password">Mật khẩu (ít nhất 8 ký tự)</label>
            <input id="password" name="password" type="password" minlength="8" required>
            <label for="password_confirmation">Xác nhận mật khẩu</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>
            <button type="submit">Đăng ký</button>
        </form>
        <p class="auth-subtitle">Đã có tài khoản? <a href="layout.php?page=login">Đăng nhập</a></p>
    <?php endif; ?>
</section>
