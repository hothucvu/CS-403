<?php
require_once __DIR__ . '/_init.php';
try {
    $started = microtime(true);
    $page = api_int($_GET['page'] ?? 1, 1, 1, 100000);
    $perPage = api_int($_GET['per_page'] ?? 12, 12, 1, 50);
    $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
    $categoryId = api_int($_GET['category_id'] ?? 0, 0, 0, 100000000);
    $type = (string) ($_GET['type'] ?? '');
    if (!in_array($type, ['', 'sell', 'barter', 'both'], true)) api_respond(['error' => 'Loại giao dịch không hợp lệ'], 400);
    $minPrice = is_numeric($_GET['min_price'] ?? null) ? max(0, (float) $_GET['min_price']) : 0;
    $maxPrice = is_numeric($_GET['max_price'] ?? null) ? max(0, (float) $_GET['max_price']) : 100000000000;
    if ($maxPrice < $minPrice) api_respond(['error' => 'Khoảng giá không hợp lệ'], 400);
    $sort = (string) ($_GET['sort'] ?? 'newest');
    $orders = ['newest' => 'p.id DESC', 'price_asc' => 'p.price ASC, p.id DESC', 'price_desc' => 'p.price DESC, p.id DESC'];
    if (!isset($orders[$sort])) api_respond(['error' => 'Kiểu sắp xếp không hợp lệ'], 400);

    $cacheKey = http_build_query(compact('page','perPage','search','categoryId','type','minPrice','maxPrice','sort'));
    [$data, $hit] = api_cache('products:' . $cacheKey, 20, static function () use ($conn,$page,$perPage,$search,$categoryId,$type,$minPrice,$maxPrice,$orders,$sort) {
        $clauses = ["p.status = 'active'", "p.moderation_status = 'approved'", 'p.price BETWEEN ? AND ?'];
        $types = 'dd'; $params = [$minPrice, $maxPrice];
        if ($search !== '') { $clauses[] = '(p.title LIKE ? OR p.description LIKE ?)'; $types .= 'ss'; $params[] = '%' . $search . '%'; $params[] = '%' . $search . '%'; }
        if ($categoryId) { $clauses[] = 'p.category_id = ?'; $types .= 'i'; $params[] = $categoryId; }
        if ($type !== '') { $clauses[] = "p.transaction_type IN (?, 'both')"; $types .= 's'; $params[] = $type; }
        $where = implode(' AND ', $clauses);
        $count = $conn->prepare("SELECT COUNT(*) FROM products p WHERE $where");
        $count->bind_param($types, ...$params);$count->execute();$total = (int)$count->get_result()->fetch_column();$count->close();
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.id, p.title, p.description, p.image_url, p.price, p.condition_percent,
                       p.transaction_type, p.created_at, c.name AS category, u.fullname AS seller
                FROM products p JOIN categories c ON c.id = p.category_id JOIN users u ON u.id = p.user_id
                WHERE $where ORDER BY {$orders[$sort]} LIMIT ? OFFSET ?";
        $stmt=$conn->prepare($sql);$listTypes = $types . 'ii';$listParams = [...$params, $perPage, $offset];
        $stmt->bind_param($listTypes, ...$listParams);$stmt->execute();$items=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
        return ['data'=>$items,'pagination'=>['page'=>$page,'per_page'=>$perPage,'total'=>$total,'pages'=>(int)ceil($total/$perPage)]];
    });
    $data['meta']=['cache_hit'=>$hit,'latency_ms'=>round((microtime(true)-$started)*1000,2)];
    api_respond($data);
} catch (Throwable $e) {
    error_log('API products: ' . $e->getMessage());
    api_respond(['error'=>'Không thể tải danh sách sản phẩm'],500);
}
