<?php
// BE/db.php - File cấu hình kết nối CSDL chung cho Backend
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'smart_trade'; // Tên database theo file csdl.sql của nhóm

$conn = new mysqli($host, $username, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $conn->connect_error);
}
// Thiết lập font tiếng Việt
$conn->set_charset("utf8");
?>