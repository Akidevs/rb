<?php
require_once 'db/db.php'; // Include your database connection

// Define the new password
$newPassword = "Owner#123"; // Replace with the desired password

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// The user's ID to identify the user (replace with the correct ID)
$userId = 11; // Change this to the ID of the user you want to update

try {
    // Update the password in the database
    $stmt = $conn->prepare("UPDATE users SET password = :hashedPassword WHERE id = :userId");
    $stmt->bindParam(':hashedPassword', $hashedPassword, PDO::PARAM_STR);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();

    echo "Password updated successfully for user ID $userId.";
} catch (PDOException $e) {
    echo "Error updating password: " . $e->getMessage();
}
?>