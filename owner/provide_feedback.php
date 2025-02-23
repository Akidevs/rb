<?php
session_start();

// Include database connection
require_once '../db/db.php';

// CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid CSRF token";
    header("Location: view_rental.php?rental_id=" . $_POST['rental_id']);
    exit();
}

// Get the rental_id, product and owner reviews
$rentalId = $_POST['rental_id'];
$productRating = $_POST['product_rating'];
$productComment = $_POST['product_comment'];
$ownerRating = $_POST['owner_rating'];
$ownerComment = $_POST['owner_comment'];
$ownerId = $_SESSION['id']; // Assuming owner is logged in

// Fetch rental details to get renter_id
$stmt = $conn->prepare("SELECT renter_id FROM rentals WHERE id = :rentalId");
$stmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
$stmt->execute();
$rental = $stmt->fetch();

if (!$rental) {
    $_SESSION['error'] = "Rental not found.";
    header("Location: view_rental.php?rental_id=$rentalId");
    exit();
}

$renterId = $rental['renter_id']; // Get renter_id from rental details

// Check if feedback already exists for the rental
$stmt = $conn->prepare("SELECT * FROM owner_reviews WHERE rental_id = :rentalId");
$stmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
$stmt->execute();
$existingReview = $stmt->fetch();

if ($existingReview) {
    $_SESSION['error'] = "Feedback already provided for this rental.";
    header("Location: view_rental.php?rental_id=$rentalId");
    exit();
}

// Insert the feedback
try {
    $stmt = $conn->prepare("
        INSERT INTO owner_reviews (owner_id, renter_id, rental_id, rating, comment)
        VALUES (:ownerId, :renterId, :rentalId, :ownerRating, :ownerComment)
    ");
    $stmt->execute([
        ':ownerId' => $ownerId,
        ':renterId' => $renterId, // Use the fetched renter_id
        ':rentalId' => $rentalId,
        ':ownerRating' => $ownerRating,
        ':ownerComment' => $ownerComment
    ]);

    $_SESSION['success'] = "Feedback submitted successfully!";
} catch (Exception $e) {
    $_SESSION['error'] = "Error submitting feedback: " . $e->getMessage();
}

header("Location: view_rental.php?rental_id=$rentalId");
exit();
?>