<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db/db.php';

// Check if owner is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../owner/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rentalId = intval($_POST['rental_id']);
    $newStatus = $_POST['new_status'];

    // Fetch current status
    $sql = "SELECT current_status FROM rentals WHERE id = :rentalId AND owner_id = :ownerId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
    $stmt->bindParam(':ownerId', $_SESSION['id'], PDO::PARAM_INT);
    $stmt->execute();
    $rental = $stmt->fetch();

    if (!$rental) {
        $_SESSION['error'] = "Rental not found.";
        header('Location: rentals.php');
        exit();
    }

    $currentStatus = $rental['current_status'];

    // Define allowed transitions
    $allowedTransitions = [
        'pending_confirmation' => ['approved', 'cancelled'],
        'approved' => ['delivery_in_progress', 'cancelled'],
        'delivery_in_progress' => ['delivered'],
        'delivered' => ['renting'],
        'renting' => ['completed', 'overdue'],
        'completed' => ['returned'],
        // 'returned', 'cancelled', 'overdue' are terminal states
    ];

    if (isset($allowedTransitions[$currentStatus]) && in_array($newStatus, $allowedTransitions[$currentStatus])) {
        // Update status
        $updateSql = "UPDATE rentals SET current_status = :newStatus, status = :newStatus WHERE id = :rentalId";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bindParam(':newStatus', $newStatus, PDO::PARAM_STR);
        $updateStmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
        $updateStmt->execute();

        $_SESSION['success'] = "Rental status updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".";
    } else {
        $_SESSION['error'] = "Invalid status transition.";
    }

    header('Location: rentals.php');
    exit();
}
?>