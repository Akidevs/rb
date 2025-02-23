<?php
ini_set('display_errors', 0); // Disable error display in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php'; // Include your database connection
require_once 'functions.php'; // Include your custom functions (for CSRF validation and others)

session_start();

// Ensure the user is logged in and authorized to submit disputes
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    header("Location: /rb/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rentalId = $_POST['rental_id']; // Rental ID from the form
    $reasons = isset($_POST['reason']) ? implode(', ', $_POST['reason']) : ''; // Reasons for dispute
    $description = $_POST['description']; // Optional description
    $proof = $_FILES['proof']; // Proof file

    // Validate inputs (example: ensure rental ID is valid and reasons are provided)
    if (empty($rentalId) || empty($reasons)) {
        $_SESSION['error'] = "Rental ID and at least one reason are required.";
        header("Location: /rb/transactions.php"); // Redirect back to transactions page
        exit();
    }

    // Handle the file upload (proof)
    $proofFilePath = null;
    if ($proof && $proof['error'] === 0) {
        // Validate file type (e.g., image, pdf)
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (in_array($proof['type'], $allowedTypes)) {
            $uploadDir = __DIR__ . '/../uploads/proofs/';
            $proofFilePath = uniqid() . '_' . basename($proof['name']);
            if (move_uploaded_file($proof['tmp_name'], $uploadDir . $proofFilePath)) {
                // File uploaded successfully
            } else {
                $_SESSION['error'] = "Failed to upload proof file.";
                header("Location: /rb/transactions.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPEG, PNG, and PDF are allowed.";
            header("Location: /rb/transactions.php");
            exit();
        }
    }

    // Insert dispute into the database
    try {
        $stmt = $conn->prepare("INSERT INTO disputes (rental_id, initiated_by, reason, description, proof, status) 
                                VALUES (:rental_id, :initiated_by, :reason, :description, :proof, 'open')");
        $stmt->execute([
            'rental_id' => $rentalId,
            'initiated_by' => $_SESSION['id'],
            'reason' => $reasons,
            'description' => $description,
            'proof' => $proofFilePath
        ]);

        // Optionally update the rental status to 'under_review'
        $updateStmt = $conn->prepare("UPDATE rentals SET rental_status = 'under_review' WHERE id = :rental_id");
        $updateStmt->execute(['rental_id' => $rentalId]);

        $_SESSION['success'] = "Dispute has been successfully submitted.";
        header("Location: /rb/transactions.php"); // Redirect to transactions page
    } catch (Exception $e) {
        $_SESSION['error'] = "Error occurred while submitting dispute: " . $e->getMessage();
        header("Location: /rb/transactions.php");
        exit();
    }
}
?>