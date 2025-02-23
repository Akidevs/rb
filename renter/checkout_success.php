<?php
// checkout.success.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout Successful - Rentbox</title>
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <style>
        /* Add any additional styles here */
    </style>
</head>
<body>
    <?php require_once '../includes/navbarr.php'; ?>

    <div class="container mt-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <div class="text-center">
            <h1 class="display-4">Thank You for Your Rental!</h1>
            <p class="lead">Your rental request has been received and is awaiting owner approval.</p>
            <a href="browse.php" class="btn btn-primary mt-3"><i class="bi bi-arrow-left-circle"></i> Continue Browsing</a>
        </div>
    </div>
    
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>