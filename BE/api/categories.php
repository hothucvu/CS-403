<?php
require_once __DIR__ . '/_init.php';
try {
    $r=$conn->query('SELECT id,name,slug,description FROM categories ORDER BY name,id');
    api_respond(['data'=>$r->fetch_all(MYSQLI_ASSOC)]);
} catch (Throwable $e) { error_log($e->getMessage());api_respond(['error'=>'Có lỗi hệ thống'],500); }
