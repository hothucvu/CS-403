<?php
require_once __DIR__ . '/../../BE/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . page_url('home'));
    exit();
}

$old = ['fullname' => '', 'email' => '', 'phone' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['fullname'] = trim($_POST['fullname'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($old['fullname'] === '' || mb_strlen($old['fullname']) > 100) {
        $errors[] = 'Họ tên không được để trống và tối đa 100 ký tự.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 100) {
        $errors[] = 'Email không hợp lệ.';
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\s]{8,20}$/', $old['phone'])) {
        $errors[] = 'Số điện thoại không hợp lệ.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Mật khẩu nhập lại không khớp.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $old['email']);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $errors[] = 'Email này đã được đăng ký.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $phone = $old['phone'] !== '' ? $old['phone'] : null;
            $stmt = $conn->prepare('INSERT INTO users (fullname, email, password, phone) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $old['fullname'], $old['email'], $hash, $phone);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: ' . page_url('login', ['registered' => 1]));
                exit();
            }
            $stmt->close();
            $errors[] = 'Không thể tạo tài khoản, vui lòng thử lại.';
        }
    }
}
?>

<link rel="stylesheet" href="./assets/css/auth.css">

<section class="auth-card">
    <h1>Đăng ký</h1>

    <?php foreach ($errors as $msg): ?>
        <p class="auth-alert auth-error"><?php echo e($msg); ?></p>
    <?php endforeach; ?>

    <form method="POST" action="<?php echo e(page_url('register')); ?>" class="auth-form">
        <label for="fullname">Họ và tên</label>
        <input type="text" id="fullname" name="fullname" value="<?php echo e($old['fullname']); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo e($old['email']); ?>" required>

        <label for="phone">Số điện thoại (không bắt buộc)</label>
        <input type="text" id="phone" name="phone" value="<?php echo e($old['phone']); ?>">

        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" minlength="6" required>

        <label for="confirm_password">Nhập lại mật khẩu</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>

        <button type="submit" class="auth-submit">Tạo tài khoản</button>
    </form>

    <p class="auth-note">Đã có tài khoản? <a href="<?php echo e(page_url('login')); ?>">Đăng nhập</a></p>
</section>