<?php
session_start();
require_once __DIR__ . '/../db/db.php';

if (isset($_POST['role']) && isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];
    $newRole = $_POST['role'];
    
    if (!in_array($newRole, ['owner', 'renter'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid role']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Update the role in the users table
        $query = "UPDATE users SET role = :role WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['role' => $newRole, 'user_id' => $userId]);

        // Update session with new role
        $_SESSION['role'] = $newRole;

        $conn->commit();

        // Determine the target URL based on the new role
        $targetUrl = $newRole === 'owner' ? '../owner/dashboard.php' : '../renter/browse.php';
        
        echo json_encode([
            'success' => true,
            'redirectUrl' => $targetUrl,
            'message' => 'Successfully switched to ' . ucfirst($newRole) . ' role'
        ]);
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Database Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing required data']);
}