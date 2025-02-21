<?php
// upload_proof.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db/db.php';

// Function to log errors
function log_error($message) {
    $logFile = '../logs/error_log.txt';
    $currentDate = date('Y-m-d H:i:s');
    $formattedMessage = "[$currentDate] $message\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Check if user is logged in and is a renter
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'renter') {
    header('Location: ../renter/login.php');
    exit();
}

$renterId = $_SESSION['id'];

// Get rental ID from query parameters
if (!isset($_GET['rental_id'])) {
    header('Location: rentals.php'); // Redirect back if rental_id is not provided
    exit();
}

$rentalId = intval($_GET['rental_id']);

// Fetch rental details to ensure it belongs to the renter and is approved
$sql = "SELECT r.*, p.name AS product_name, p.image, u.name AS owner_name
        FROM rentals r
        INNER JOIN products p ON r.product_id = p.id
        INNER JOIN users u ON r.owner_id = u.id
        WHERE r.id = :rentalId AND r.renter_id = :renterId AND r.rental_status = 'approved'";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
$stmt->bindParam(':renterId', $renterId, PDO::PARAM_INT);
$stmt->execute();

$rental = $stmt->fetch();

if (!$rental) {
    $_SESSION['error'] = "Rental not found or not eligible for uploading proof.";
    header('Location: rentals.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file is uploaded
    if (isset($_FILES['proof_of_transaction']) && $_FILES['proof_of_transaction']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['proof_of_transaction']['tmp_name'];
        $fileName = $_FILES['proof_of_transaction']['name'];
        $fileSize = $_FILES['proof_of_transaction']['size'];
        $fileType = $_FILES['proof_of_transaction']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Check allowed file extensions
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory in which the uploaded file will be moved
            $uploadFileDir = '../uploads/proofs/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // Update rental record with proof_of_transaction and update status to 'in_progress'
                $updateSql = "UPDATE rentals 
                              SET proof_of_transaction = :proof, rental_status = 'in_progress', updated_at = NOW() 
                              WHERE id = :rentalId AND renter_id = :renterId";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bindParam(':proof', $newFileName);
                $updateStmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
                $updateStmt->bindParam(':renterId', $renterId, PDO::PARAM_INT);
                
                if($updateStmt->execute()) {
                    $_SESSION['success'] = "Proof of transaction uploaded successfully.";
                    header('Location: rentals.php');
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to update rental with proof.";
                    log_error("Failed to update rental ID: $rentalId with proof for renter ID: $renterId");
                }
            } else {
                $_SESSION['error'] = "There was an error moving the uploaded file.";
                log_error("Error moving uploaded file for rental ID: $rentalId");
            }
        } else {
            $_SESSION['error'] = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
            log_error("Invalid file type uploaded for rental ID: $rentalId");
        }
    } else {
        $_SESSION['error'] = "No file uploaded or there was an upload error.";
        log_error("File upload error for rental ID: $rentalId");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Proof of Transaction - Rentbox</title>
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <style>
        /* Add any additional styles here */
        .container {
            max-width: 600px;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/navbarr.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Upload Proof of Transaction</h2>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="upload_proof.php?rental_id=<?= htmlspecialchars($rentalId) ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="proof_of_transaction" class="form-label">Proof of Transaction (e.g., Receipt, Screenshot)</label>
                <input class="form-control" type="file" id="proof_of_transaction" name="proof_of_transaction" accept=".jpg,.jpeg,.png,.pdf" required>
                <div class="form-text">Allowed file types: JPG, JPEG, PNG, PDF. Max size: 5MB.</div>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
            <a href="rentals.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>