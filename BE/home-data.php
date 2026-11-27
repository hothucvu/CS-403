<?php
// Danh sách đồ cũ có tìm kiếm, lọc và phân trang. Không hiện sản phẩm đã ẩn/bán.
require_once __DIR__ . '/db.php';
$searchKeyword = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 120);
$selectedCategory = filter_var($_GET['category'] ?? 0, FILTER_VALIDATE_INT);
$selectedCategory = $selectedCategory && $selectedCategory > 0 ? (int) $selectedCategory : 0;
$selectedType = (string) ($_GET['type'] ?? 'all');
if (!in_array($selectedType, ['all', 'sell', 'barter'], true)) { $selectedType = 'all'; }

$minPriceRaw = trim((string) ($_GET['min_price'] ?? ''));
$maxPriceRaw = trim((string) ($_GET['max_price'] ?? ''));
$minPrice = ($minPriceRaw !== '' && ctype_digit($minPriceRaw) && strlen($minPriceRaw) <= 10)
    ? min((float)$minPriceRaw, 999999999) : null;
$maxPrice = ($maxPriceRaw !== '' && ctype_digit($maxPriceRaw) && strlen($maxPriceRaw) <= 10)
    ? min((float)$maxPriceRaw, 999999999) : null;
$selectedSort = (string) ($_GET['sort'] ?? 'newest');
if (!in_array($selectedSort, ['newest', 'price_asc', 'price_desc'], true)) { $selectedSort = 'newest'; }
$searchPage = filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT);
$searchPage = max(1, min((int) ($searchPage ?: 1), 100000));
$pageSize = 12;
$productList = [];
$productCategories = [];
$statusMessage = '';
$isError = false;
$totalProducts = 0;
$totalPages = 1;

$categoryResult = $conn->query('SELECT id, name FROM categories ORDER BY name');
while ($category = $categoryResult->fetch_assoc()) { $productCategories[] = $category; }

$conditions = ["p.status = 'active'", "p.moderation_status = 'approved'"];
$params = [];
$types = '';
if ($searchKeyword !== '') {
    $conditions[] = '(p.title LIKE ? OR p.description LIKE ?)';
    $like = '%' . $searchKeyword . '%';
    array_push($params, $like, $like);
    $types .= 'ss';
}
if ($selectedCategory > 0) {
    $conditions[] = 'p.category_id = ?';
    $params[] = $selectedCategory;
    $types .= 'i';
}
if ($selectedType === 'sell') { $conditions[] = "p.transaction_type IN ('sell', 'both')"; }
elseif ($selectedType === 'barter') { $conditions[] = "p.transaction_type IN ('barter', 'both')"; }

if ($minPrice !== null) { $conditions[] = 'p.price >= ?'; $params[] = $minPrice; $types .= 'd'; }
if ($maxPrice !== null) { $conditions[] = 'p.price <= ?'; $params[] = $maxPrice; $types .= 'd'; }
$where = implode(' AND ', $conditions);

$countSql = "SELECT COUNT(*) AS total FROM products p WHERE $where";
$stmt = $conn->prepare($countSql);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$totalProducts = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$totalPages = max(1, (int) ceil($totalProducts / $pageSize));
$searchPage = min($searchPage, $totalPages);
$offset = ($searchPage - 1) * $pageSize;
$orderBy = match ($selectedSort) {
    'price_asc' => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
    default => 'p.id DESC'
};

$sql = "SELECT p.id, p.user_id, p.title, p.price, p.description, p.image_url,
               p.condition_percent, p.transaction_type, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE $where ORDER BY $orderBy LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$listParams = [...$params, $pageSize, $offset];
$stmt->bind_param($types . 'ii', ...$listParams);
$stmt->execute();
$productList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
if (!$productList) { $statusMessage = 'Không tìm thấy sản phẩm phù hợp. Hãy thử bộ lọc khác.'; }

// URL phân trang chỉ dùng các giá trị đã được kiểm tra bên trên.
$homeFilterParams = ['q' => $searchKeyword, 'category' => $selectedCategory, 'type' => $selectedType, 'sort' => $selectedSort, 'min_price' => $minPriceRaw, 'max_price' => $maxPriceRaw];
