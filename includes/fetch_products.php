<?php
//fetch_pr
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    die(json_encode(['error' => 'Connection failed: ' . $e->getMessage()]));
}

try {
    // Get parameters from request
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 8;
    $offset = ($page - 1) * $limit;
    $sort = $_GET['sort'] ?? 'newest';
    $categories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];

    // Validate categories
    $validCategories = [
        'Mobile Phones', 'Laptops', 'Tablets', 'Cameras',
        'Accessories', 'Gaming Consoles', 'Audio Devices', 'Drones'
    ];
    foreach ($categories as $category) {
        if (!in_array($category, $validCategories)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid category: ' . $category]);
            exit;
        }
    }

    // Validate sort parameter
    $validSorts = ['newest', 'top_rated'];
    if (!in_array($sort, $validSorts)) {
        throw new Exception('Invalid sort parameter');
    }

    // Build main query
    $sql = "SELECT 
                p.*, 
                COALESCE(AVG(c.rating), 0) AS average_rating,
                COUNT(c.id) AS rating_count
            FROM products p
            LEFT JOIN comments c ON p.id = c.product_id
            WHERE p.status = 'approved'";

    // Add category filter
    $categoryParams = [];
    if (!empty($categories)) {
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $sql .= " AND p.category IN ($placeholders)";
        $categoryParams = $categories;
    }

    // Always group by product ID
    $sql .= " GROUP BY p.id";

    // Add rating filter for top rated
    if ($sort === 'top_rated') {
        $sql .= " HAVING average_rating > 0";
    }

    // Add sorting
    switch ($sort) {
        case 'newest':
            $sql .= " ORDER BY p.created_at DESC";
            break;
        case 'top_rated':
            $sql .= " ORDER BY average_rating DESC";
            break;
    }

    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";

    // Prepare and execute main query
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $paramIndex = 1;
    foreach ($categoryParams as $category) {
        $stmt->bindValue($paramIndex++, $category);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $products = $stmt->fetchAll();

    // Format numeric values
    foreach ($products as &$product) {
        $product['average_rating'] = round((float)$product['average_rating'], 1);
        $product['rating_count'] = (int)$product['rating_count'];
        $product['rental_price'] = number_format((float)$product['rental_price'], 2);
    }

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total 
                FROM products p
                WHERE p.status = 'approved'";

    if (!empty($categories)) {
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $countSql .= " AND p.category IN ($placeholders)";
    }

    if ($sort === 'top_rated') {
        $countSql .= " AND EXISTS (
                        SELECT 1 
                        FROM comments c 
                        WHERE c.product_id = p.id
                        HAVING AVG(c.rating) > 0
                      )";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($categoryParams);
    $total = $countStmt->fetch()['total'];

    // Prepare response
    $response = [
        'products' => $products,
        'pagination' => [
            'total_items' => (int)$total,
            'total_pages' => ceil($total / $limit),
            'current_page' => $page,
            'per_page' => $limit
        ]
    ];

    echo json_encode($response);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>