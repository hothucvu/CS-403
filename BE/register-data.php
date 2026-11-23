
<?php
require_once __DIR__ . '/db.php';

$statusMessage = '';
$isError = false;
$registrationSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirmation'] ?? '';

    if ($fullname === '' || $email === '' || $password === '') {
        $isError = true;
        $statusMessage = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $isError = true;
        $statusMessage = 'Email không hợp lệ.';
    } elseif (strlen($password) < 8) {
        $isError = true;
        $statusMessage = 'Mật khẩu phải có ít nhất 8 ký tự.';
    } elseif ($password !== $confirmPassword) {
        $isError = true;
        $statusMessage = 'Mật khẩu xác nhận không khớp.';
    } else {
        try {
            // Kiểm tra email đã tồn tại
            $stmt = $conn->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            $emailExists = $stmt->num_rows > 0;
            $stmt->close();

            if ($emailExists) {
                $isError = true;
                $statusMessage = 'Email đã được sử dụng.';
            } else {
                // Mã hóa mật khẩu trước khi lưu
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conn->prepare(
                    "INSERT INTO users (fullname, email, password)
                     VALUES (?, ?, ?)"
                );
                $stmt->bind_param(
                    "sss",
                    $fullname,
                    $email,
                    $hashedPassword
                );
                $stmt->execute();
                $stmt->close();

                $registrationSuccess = true;
                $statusMessage = 'Đăng ký thành công!';
            }
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $isError = true;
            $statusMessage = 'Không thể đăng ký. Vui lòng thử lại.';
        }
    }
}
?>
