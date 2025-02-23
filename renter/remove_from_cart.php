<?php
// remove_from_cart.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db/db.php';

// Function to log errors
function log_error($message) {
    $logFile = '../logs/error_log.txt';
    $currentDate = date('Y-m-d H:i:s');
    $formattedMessage = "[$currentDate] $message\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Check if user is logged in and is a renter
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'renter') {
    header('Location: ../renter/login.php');
    exit();
}

$renterId = $_SESSION['id'];

// Check if cart_id is provided
if (!isset($_GET['cart_id'])) {
    $_SESSION['error'] = "Invalid request.";
    header('Location: cart.php');
    exit();
}

$cartId = intval($_GET['cart_id']);

// Delete the cart item
$sql = "DELETE FROM cart_items WHERE id = :cartId AND renter_id = :renterId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':cartId', $cartId, PDO::PARAM_INT);
$stmt->bindParam(':renterId', $renterId, PDO::PARAM_INT);

if ($stmt->execute()) {
    $_SESSION['success'] = "Item removed from cart successfully.";
    header('Location: cart.php');
    exit();
} else {
    $_SESSION['error'] = "Failed to remove item from cart.";
    log_error("Failed to remove cart item ID: $cartId for renter ID: $renterId");
    header('Location: cart.php');
    exit();
}
?>