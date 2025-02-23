<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');

// Database connection using PDO
$host = 'localhost';
$db   = 'PROJECT';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed']));
}

try {
    // Get parameters from request
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 8; // Items per page
    $offset = ($page - 1) * $limit;
    $sort = $_GET['sort'] ?? 'newest';
    
    // Handle empty categories string
    $categoriesParam = $_GET['categories'] ?? '';
    $categories = $categoriesParam !== '' ? explode(',', $categoriesParam) : [];

    // First, get total count for pagination
    $countSql = "SELECT COUNT(DISTINCT p.id) as total FROM products p WHERE p.status = 'approved'";
    $countParams = [];
    
    if (!empty($categories) && $categories[0] !== '') {
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $countSql .= " AND p.category IN ($placeholders)";
        $countParams = $categories;
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalItems = (int)$countStmt->fetch()['total'];
    $totalPages = ceil($totalItems / $limit);

    // Ensure page number is valid
    $page = min(max(1, $page), $totalPages);
    $offset = ($page - 1) * $limit;

    // Main query for products
    $sql = "SELECT 
                p.*, 
                COALESCE(AVG(c.rating), 0) AS average_rating,
                COUNT(DISTINCT c.id) AS rating_count
            FROM products p
            LEFT JOIN comments c ON p.id = c.product_id
            WHERE p.status = 'approved'";

    $params = [];

    if (!empty($categories) && $categories[0] !== '') {
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $sql .= " AND p.category IN ($placeholders)";
        $params = array_merge($params, $categories);
    }

    $sql .= " GROUP BY p.id";

    switch ($sort) {
        case 'top_rated':
            $sql .= " ORDER BY average_rating DESC, p.created_at DESC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY p.created_at DESC";
            break;
    }

    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Format products
    foreach ($products as &$product) {
        $product['average_rating'] = number_format((float)$product['average_rating'], 1);
        $product['rating_count'] = (int)$product['rating_count'];
        $product['rental_price'] = number_format((float)$product['rental_price'], 2);
    }

    echo json_encode([
        'products' => $products,
        'pagination' => [
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_previous' => $page > 1
        ]
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}