<?php
session_start();
require_once __DIR__ . '/../db/db.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    try {
        $userId = $_SESSION['id'];
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
        $search = $_POST['search'] ?? '';
        $page = $_POST['page'] ?? 1;

        // Check product availability
        $productCheck = $conn->prepare("
            SELECT id, quantity 
            FROM products 
            WHERE id = :productId 
            AND status = 'approved'
        ");
        $productCheck->execute([':productId' => $productId]);
        
        if ($productCheck->rowCount() === 0) {
            throw new Exception("Product not available");
        }

        // Check existing cart items
        $cartCheck = $conn->prepare("
            SELECT id 
            FROM cart_items 
            WHERE renter_id = :userId 
            AND product_id = :productId
        ");
        $cartCheck->execute([':userId' => $userId, ':productId' => $productId]);

        if ($cartCheck->rowCount() === 0) {
            $insert = $conn->prepare("
                INSERT INTO cart_items (renter_id, product_id, created_at, updated_at)
                VALUES (:userId, :productId, NOW(), NOW())
            ");
            $insert->execute([':userId' => $userId, ':productId' => $productId]);
        }

        // Redirect back with success message
        header("Location: browse.php?search=".urlencode($search)."&page=".$page."&success=1");
        exit();

    } catch (Exception $e) {
        header("Location: browse.php?search=".urlencode($search)."&page=".$page."&error=1");
        exit();
    }

    
}
header('Location: browse.php');
exit();












