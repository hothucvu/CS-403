<?php
// BE/db.php - File cấu hình kết nối CSDL chung cho Backend
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cs403'; // Tên database theo fi

$conn = new mysqli($host, $username, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
}
// Thiết lập font tiếng Việt
$conn->set_charset("utf8mb4");
?>