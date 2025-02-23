<?php
// admin/reject_gadget.php

require_once 'includes/auth.php'; // Ensure correct path
require_once '../db/db.php';      // Adjust path if necessary

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    if (isset($_POST['gadget_id'])) {
        $gadget_id = intval($_POST['gadget_id']); // Ensure it's an integer

        // Reject the gadget by deleting or updating its status
        $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute(['id' => $gadget_id]);

        // Optionally, add a success message
        $_SESSION['success_message'] = "Gadget rejected and removed successfully.";

        // Redirect back to the verification-confirmation page
        header("Location: verification-confirmation.php");
        exit();
    } else {
        // Gadget ID not provided
        $_SESSION['error_message'] = "Gadget ID is missing.";
        header("Location: verification-confirmation.php");
        exit();
    }
}

// If accessed without POST, redirect back or show error
header("Location: verification-confirmation.php");
exit();
?>