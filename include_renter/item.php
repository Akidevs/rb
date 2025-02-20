<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/../db/db.php'; // Ensure the path is correct

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}

// Check if the product_id is set in the URL
if (isset($_GET['id'])) {
    $product_id = $_GET['id']; // Fetch the product ID from the URL

    // Query to fetch comments and user data
    $query = "SELECT c.*, u.name, u.profile_picture 
              FROM comments c 
              JOIN users u ON c.renter_id = u.id 
              WHERE c.product_id = ?";
    $stmt = $conn->prepare($query); // Use $conn here
    $stmt->execute([$product_id]);
    $comments = $stmt->fetchAll();

    // Query to fetch product details
    $query = "SELECT name, description, category, quantity, condition_description, rental_price, rental_period, image, owner_id FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        // Fetch data
        $product_description = $product['description'];
        $category = $product['category'];
        $quantity = $product['quantity'];
        $condition_description = $product['condition_description'];
        $rental_price = $product['rental_price'];
        $rental_period = $product['rental_period'];
        $image = $product['image']; // Handle the image
        $owner_id = $product['owner_id']; // Fetch owner_id from the product
    } else {
        echo "Product not found.";
        exit();
    }

    // Query to fetch the owner's name
    $query = "SELECT name FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $owner_name = $stmt->fetchColumn();

    // Query to fetch the active status (verification status)
    $query = "SELECT verification_status FROM user_verification WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $active_status = $stmt->fetchColumn();

    // Query to fetch ratings for the owner
    $query = "SELECT SUM(rating) AS total_ratings 
              FROM renter_reviews 
              WHERE owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]); // Use the product's owner_id
    $total_ratings = $stmt->fetchColumn();

    // Query to fetch owner data (joined date)
    $query = "SELECT created_at FROM user_verification WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]); // Use the owner_id
    $owner_data = $stmt->fetch();

    if ($owner_data && isset($owner_data['created_at'])) {
        $join_date = new DateTime($owner_data['created_at']);
    } else {
        echo "Owner join date is not available.";
        exit();
    }

    $current_date = new DateTime(); // Current date and time
    $interval = $join_date->diff($current_date);

    // Get the time difference in years, months, and days
    $joined_duration = "";
    if ($interval->y > 0) {
        $joined_duration .= $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ";
    }
    if ($interval->m > 0) {
        $joined_duration .= $interval->m . " month" . ($interval->m > 1 ? "s" : "") . " ";
    }
    $joined_duration .= $interval->d . " day" . ($interval->d > 1 ? "s" : "");

    // Get rental count for the owner
    $query = "SELECT COUNT(*) FROM rentals WHERE owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $rental_count = $stmt->fetchColumn();

    // Handle availability
    $availabilityClass = $quantity > 0 ? 'bg-success-subtle' : 'bg-danger-subtle';
    $availabilityText = $quantity > 0 ? 'Available' : 'Unavailable';

    // Split images if necessary (assuming comma-separated values)
    $images = explode(',', $image);

    // Calculate the average rating
    $total_ratings = 0;
    $rating_count = count($comments);
    foreach ($comments as $comment) {
        $total_ratings += $comment['rating'];
    }

    if ($rating_count > 0) {
        $average_rating = round($total_ratings / $rating_count, 1); // Round to 1 decimal
    } else {
        $average_rating = 0;
    }

    // Filter comments based on selected rating
    $selected_rating = isset($_GET['rating']) ? $_GET['rating'] : 'all';
    if ($selected_rating != 'all') {
        $comments = array_filter($comments, function($comment) use ($selected_rating) {
            return $comment['rating'] == $selected_rating;
        });
    }
} else {
    echo "Product ID not provided.";
    exit();
}
?>