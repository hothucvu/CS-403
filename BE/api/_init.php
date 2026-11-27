<?php
// REST API chỉ đọc, trả JSON đúng mã UTF-8.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/../db.php';
function api_respond(array $payload, int $status=200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function api_int(mixed $raw, int $default, int $min, int $max): int {
    $i = filter_var($raw, FILTER_VALIDATE_INT);
    return $i === false || $i < $min ? $default : min($i, $max);
}
function api_cache(string $key, int $ttl, callable $build): array {
    // Cache demo (không chứa thông tin người dùng), TTL ngắn để hạn chế dữ liệu cũ.
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cs403_bao_' . hash('sha256', $key) . '.json';
    if (is_file($path) && filemtime($path) + $ttl > time()) {
        $decoded = json_decode((string) @file_get_contents($path), true);
        if (is_array($decoded)) return [$decoded, true];
    }
    $data = $build();
    @file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return [$data, false];
}
