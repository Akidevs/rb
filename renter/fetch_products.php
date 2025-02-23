<?php
// Enable error reporting
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
    // Get category from request
    $category = isset($_GET['category']) ? trim($_GET['category']) : '';

    // Validate category exists in your list
    $validCategories = [
        'Mobile Phones', 'Laptops', 'Tablets', 'Cameras',
        'Accessories', 'Gaming Consoles', 'Audio Devices', 'Drones'
    ];
    
    if (!in_array($category, $validCategories)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid category']);
        exit;
    }

    // Query to fetch products with status check
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.brand,
            p.description,
            p.rental_price,
            p.image,
            p.category,
            p.rental_period,
            COALESCE(AVG(c.rating), 0) AS average_rating,
            COUNT(c.id) AS rating_count
        FROM products p
        LEFT JOIN comments c ON p.id = c.product_id
        WHERE p.category = :category
            AND p.status = 'approved'  -- Changed to match your actual status values
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':category' => $category]);
    $products = $stmt->fetchAll();

    // Format numeric values
    foreach ($products as &$product) {
        $product['average_rating'] = round((float)$product['average_rating'], 1);
        $product['rating_count'] = (int)$product['rating_count'];
        $product['rental_price'] = number_format((float)$product['rental_price'], 2);
    }

    echo json_encode($products);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
