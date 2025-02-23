<?php
session_start();
require_once __DIR__ . '/../db/db.php';

if (isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];

    try {
        // Check current role and verification status
        $query = "
            SELECT u.role, uv.verification_status
            FROM users u
            LEFT JOIN user_verification uv ON u.id = uv.user_id
            WHERE u.id = :user_id
        ";

        $stmt = $conn->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();

        // Log the fetched user data to confirm it's correct
        error_log("Fetched user data: " . print_r($user, true));

        if ($user) {
            $response = [
                'success' => true,
                'currentRole' => $user['role'],
                'verificationStatus' => $user['verification_status']
            ];
            echo json_encode($response);
        } else {
            error_log("User not found for ID: " . $userId);
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    }
} else {
    error_log("No user session found.");
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
}