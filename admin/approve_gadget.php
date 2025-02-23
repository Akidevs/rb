<?php
// admin/approve_gadget.php

require_once 'includes/auth.php'; // Authentication check
require_once '../db/db.php';      // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: verification-confirmation.php");
        exit();
    }

    if (isset($_POST['gadget_id'])) {
        $gadget_id = intval($_POST['gadget_id']); // Ensure it's an integer

        // Approve the gadget by updating its status
        $stmt = $conn->prepare("UPDATE products SET status = 'approved' WHERE id = :id");
        $stmt->execute(['id' => $gadget_id]);

        if ($stmt->rowCount()) {
            $_SESSION['success_message'] = "Gadget approved successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to approve the gadget. It may not exist or is already approved.";
        }

        // Redirect back to verification-confirmation.php
        header("Location: verification-confirmation.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Gadget ID is missing.";
        header("Location: verification-confirmation.php");
        exit();
    }
}

// If accessed without POST, redirect back
header("Location: verification-confirmation.php");
exit();
?>