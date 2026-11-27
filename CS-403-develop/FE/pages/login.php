<?php
require_once __DIR__ . '/../../BE/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . page_url('home'));
    exit();
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } else {
        $stmt = $conn->prepare('SELECT id, fullname, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            header('Location: ' . page_url('home'));
            exit();
        }
        $error = 'Email hoặc mật khẩu không đúng.';
    }
}

$registered = isset($_GET['registered']);
?>

<link rel="stylesheet" href="./assets/css/auth.css">

<section class="auth-card">
    <h1>Đăng nhập</h1>

    <?php if ($registered && $error === ''): ?>
        <p class="auth-alert auth-success">Đăng ký thành công. Hãy đăng nhập.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="auth-alert auth-error"><?php echo e($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(page_url('login')); ?>" class="auth-form">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo e($email); ?>" required>

        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="auth-submit">Đăng nhập</button>
    </form>

    <p class="auth-note">Chưa có tài khoản? <a href="<?php echo e(page_url('register')); ?>">Đăng ký</a></p>
</section>