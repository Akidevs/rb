<?php
session_start();
require_once __DIR__ . '/../db/db.php';

$userId = $_SESSION['id']; // Get the user ID from session

// Query the user table to get the updated user details
$query = "SELECT * FROM users WHERE id = :userId";
$stmt = $conn->prepare($query);
$stmt->bindValue(':userId', $userId);
$stmt->execute();
$user = $stmt->fetch();

// Return the updated details as JSON
echo json_encode([
    'user_name' => $user['name'],
    'profile_picture' => $user['profile_picture'], // Ensure you have this in the database
    'user_role' => $user['role']
]);
?>