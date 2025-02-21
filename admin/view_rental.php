<?php
// view_rental.php
session_start();
require_once __DIR__ . '/../db/db.php';

// Check if rental_id is passed in the URL
if (isset($_GET['rental_id'])) {
    $rentalId = intval($_GET['rental_id']);

    // Fetch the rental details with an explicit column list
    $stmt = $conn->prepare("
        SELECT 
            r.id, 
            r.product_id, 
            r.renter_id, 
            r.owner_id, 
            r.start_date, 
            r.end_date, 
            r.delivery_date, 
            r.actual_end_date, 
            r.rental_price, 
            r.total_cost, 
            r.payment_method, 
            r.status, 
            r.notification_sent, 
            r.created_at, 
            r.updated_at, 
            p.name AS product_name, 
            u.name AS renter_name
        FROM rentals r
        INNER JOIN products p ON r.product_id = p.id
        INNER JOIN users u ON r.renter_id = u.id
        WHERE r.id = :rental_id
    ");
    $stmt->bindParam(':rental_id', $rentalId, PDO::PARAM_INT);
    $stmt->execute();
    $rental = $stmt->fetch();

    if (!$rental) {
        $_SESSION['error_message'] = "Rental not found.";
        header('Location: review_disputes.php');
        exit();
    }
} else {
    $_SESSION['error_message'] = "No rental selected.";
    header('Location: review_disputes.php');
    exit();
}

// Handle actions like banning a user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    // Use the fetched rental details to get the renter's user ID
    $userId = $rental['renter_id'];

    if ($action === 'ban') {
        // Update the user's status to banned
        $stmt = $conn->prepare("UPDATE users SET status = 'banned' WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success_message'] = "User has been banned.";
        header('Location: review_disputes.php'); // Redirect after banning
        exit();
    } elseif ($action === 'resolve') {
        // Mark the dispute as resolved
        $stmt = $conn->prepare("UPDATE disputes SET status = 'resolved' WHERE rental_id = :rental_id");
        $stmt->bindParam(':rental_id', $rentalId, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['success_message'] = "Dispute has been resolved.";
        header('Location: review_disputes.php'); // Redirect after resolving
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Rental - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <h2 class="mt-4">Rental Details</h2>

            <!-- Success Message -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="card my-4">
                <div class="card-header">
                    Rental Information
                </div>
                <div class="card-body">
                    <h5>Rental ID: <?= htmlspecialchars($rental['id']) ?></h5>
                    <p><strong>Product Name:</strong> <?= htmlspecialchars($rental['product_name']) ?></p>
                    <p><strong>Renter Name:</strong> <?= htmlspecialchars($rental['renter_name']) ?></p>
                    <p><strong>Rental Start Date:</strong> <?= htmlspecialchars($rental['start_date']) ?></p>
                    <p><strong>Rental End Date:</strong> <?= htmlspecialchars($rental['end_date']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($rental['status']) ?></p>

                    <!-- Admin actions: ban user or mark dispute as resolved -->
                    <form method="POST" action="view_rental.php?rental_id=<?= htmlspecialchars($rental['id']) ?>">
                        <input type="hidden" name="rental_id" value="<?= htmlspecialchars($rental['id']) ?>">
                        <button type="submit" name="action" value="ban" class="btn btn-danger">Ban User</button>
                        <button type="submit" name="action" value="resolve" class="btn btn-success">Mark as Resolved</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>