<?php


session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db/db.php';
function updateProductQuantity($rentalId, $newStatus, $pdo) {
    try {
        // Get the product_id from the rental
        $stmt = $pdo->prepare("SELECT product_id FROM rentals WHERE id = :rentalId");
        $stmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Rental not found");
        }

        $productId = $product['product_id'];

        // Get the current quantity of the product
        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = :productId");
        $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$productDetails) {
            throw new Exception("Product not found");
        }

        $currentQuantity = $productDetails['quantity'];

        // Handle rental status changes
        if (in_array($newStatus, ['approved', 'delivery_in_progress']) && !in_array($productDetails['current_status'], ['approved', 'delivery_in_progress'])) {
            // Decrease quantity when product is rented
            if ($currentQuantity > 0) {
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = :productId");
                $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                throw new Exception("No product available to rent");
            }
        } elseif (in_array($newStatus, ['returned', 'completed']) && !in_array($productDetails['current_status'], ['returned', 'completed'])) {
            // Increase quantity when product is returned
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + 1 WHERE id = :productId");
            $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmt->execute();
        }

        // Update the rental status
        $stmt = $pdo->prepare("UPDATE rentals SET current_status = :newStatus WHERE id = :rentalId");
        $stmt->bindParam(':newStatus', $newStatus, PDO::PARAM_STR);
        $stmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
        $stmt->execute();

        echo "Product quantity updated successfully and rental status changed.";

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>