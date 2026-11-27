
<?php
// BE/login-data.php
// Xử lý đăng nhập tài khoản người dùng

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

$statusMessage = '';
$isError = false;
$loginSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $isError = true;
        $statusMessage = 'Vui lòng nhập đầy đủ thông tin.';
    } else {
        try {
            // Tìm người dùng theo email
            $sql = "SELECT id, fullname, email, password, role
                    FROM users
                    WHERE email = ?
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            // Kiểm tra mật khẩu
            if ($user && password_verify(
                $password,
                $user['password']
            )) {

                // Tạo lại session ID sau khi đăng nhập
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];

                $loginSuccess = true;
                $statusMessage = 'Đăng nhập thành công!';

            } else {
                $isError = true;
                $statusMessage = 'Email hoặc mật khẩu không đúng.';
            }

        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());

            $isError = true;
            $statusMessage = 'Lỗi hệ thống. Vui lòng thử lại.';
        }
    }
}
?>
