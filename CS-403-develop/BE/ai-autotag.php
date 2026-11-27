<?php
// BE/ai-autotag.php  -  Nhóm 2
// Cầu nối PHP -> Flask AI. Frontend gửi ảnh (POST, field "image") vào file này,
// file này lấy danh mục từ DB, gọi Flask /api/auto-tag rồi trả JSON về cho Frontend.

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

const AI_SERVICE_URL = 'http://127.0.0.1:5000/api/auto-tag';
const AI_MAX_BYTES   = 5 * 1024 * 1024;

function jsonOut(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['status' => 'error', 'error' => 'Chỉ chấp nhận POST'], 405);
}

if (!isset($_SESSION['user_id'])) {
    jsonOut(['status' => 'error', 'error' => 'Bạn cần đăng nhập.'], 401);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonOut(['status' => 'error', 'error' => 'Chưa chọn ảnh hoặc upload lỗi'], 400);
}

$file = $_FILES['image'];
if ($file['size'] > AI_MAX_BYTES) {
    jsonOut(['status' => 'error', 'error' => 'Ảnh vượt quá 5MB'], 413);
}

// Kiểm tra MIME thật của file, không tin phần mở rộng
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    jsonOut(['status' => 'error', 'error' => 'Chỉ hỗ trợ ảnh JPG, PNG, WEBP'], 415);
}

// Lấy danh mục từ DB để AI chọn đúng category_id
$categories = [];
$res = $conn->query('SELECT id, name FROM categories ORDER BY id');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $categories[] = ['id' => (int)$row['id'], 'name' => $row['name']];
    }
}

$ch = curl_init(AI_SERVICE_URL);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 40,
    CURLOPT_POSTFIELDS     => [
        'image'      => new CURLFile($file['tmp_name'], $mime, $file['name']),
        'categories' => json_encode($categories, JSON_UNESCAPED_UNICODE),
    ],
]);
$body     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($body === false) {
    jsonOut(['status' => 'error', 'error' => 'Không kết nối được AI service (đã chạy python app.py chưa?): ' . $curlErr], 503);
}

$data = json_decode($body, true);
if (!is_array($data)) {
    jsonOut(['status' => 'error', 'error' => 'AI service trả về dữ liệu không hợp lệ'], 502);
}

jsonOut($data, $httpCode ?: 200);
