<?php
// renter/rentals.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db/db.php';

// Check if renter is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'renter') {
    header('Location: ../renter/login.php');
    exit();
}

$renterId = $_SESSION['id'];

// Fetch rentals for the renter
$sql = "SELECT r.*, p.name AS product_name, p.brand, p.image, p.rental_period, p.rental_price, u.name AS owner_name
        FROM rentals r
        INNER JOIN products p ON r.product_id = p.id
        INNER JOIN users u ON r.owner_id = u.id
        WHERE r.renter_id = :renterId
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':renterId', $renterId, PDO::PARAM_INT);
$stmt->execute();
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate remaining_days for each rental
// Calculate remaining_days for each rental
foreach ($rentals as &$rental) {
    if ($rental['status'] === 'returned') {
        $rental['remaining_days'] = 'Completed';
    } elseif ($rental['status'] === 'cancelled') {
        $rental['remaining_days'] = 'Cancelled';
    } elseif ($rental['status'] === 'overdue') {
        $today = new DateTime();
        $endDate = new DateTime($rental['end_date']);
        $interval = $today->diff($endDate);
        $days = $interval->days;
        
        // If the rental is overdue, show the negative days as overdue
        $rental['remaining_days'] = -$days;
    } elseif (!empty($rental['end_date'])) {
        $today = new DateTime();
        $endDate = new DateTime($rental['end_date']);
        $startDate = new DateTime($rental['start_date']);
        
        // Include both start and end dates in the calculation by adding 1 day
        $interval = $today->diff($endDate);
        
        // Total days difference
        $days = $interval->days; // absolute days difference
        
        if ($today < $endDate) {
            $rental['remaining_days'] = $days; // days remaining if the end date is in the future
        } elseif ($today > $endDate) {
            $rental['remaining_days'] = -$days; // negative days if the end date has passed
        } else {
            $rental['remaining_days'] = 0; // if today matches the end date
        }
    } else {
        $rental['remaining_days'] = 'N/A';
    }
}
unset($rental); // Break reference

// Helper functions
function getStatusBadgeColor($status) {
    switch($status) {
        case 'pending_confirmation':
            return 'warning';
        case 'approved':
            return 'success';
        case 'delivery_in_progress':
            return 'info';
        case 'delivered':
            return 'info';
        case 'renting':
            return 'primary';
        case 'completed':
            return 'success';
        case 'returned':
            return 'success';
        case 'cancelled':
            return 'danger';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getRemainingDaysBadgeColor($remaining_days) {
    if ($remaining_days === 'Completed') {
        return 'success';
    } elseif ($remaining_days === 'Cancelled') {
        return 'danger';
    } elseif ($remaining_days === 'Overdue') {
        return 'danger';
    } elseif ($remaining_days === 'N/A') {
        return 'secondary';
    } elseif ($remaining_days > 0) {
        return 'success';
    } elseif ($remaining_days < 0) {
        return 'danger';
    } else { 
        return 'warning text-dark';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Rentals</title>
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        main {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            height: auto;
            padding-top: 20px;
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            width: 100%;
            max-width: 1200px;
            padding: 2rem;
        }
        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }
        .img-thumbnail {
            height: 100px;
            width: 100px;
            object-fit: cover;
            margin: auto;
        }
        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
            height: 50px;
        }
    </style>
    
</head>
<body>
    <?php include '../includes/navbarr.php'; ?>

    <main>
        <div class="card">
            <h2 class="text-center mb-4">My Rentals</h2>

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

            <div class="table-responsive">
                <table class="table table-striped table-bordered text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Gadget</th>
                            <th>Owner</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Remaining Days</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php if (!empty($rentals)): ?>
        <?php foreach ($rentals as $index => $rental): ?>
            <tr>
                <td><?= htmlspecialchars($index + 1) ?></td>
                <td>
                    <div class="d-flex flex-column align-items-center">
                        <img src="../img/uploads/<?= htmlspecialchars($rental['image']) ?>" 
                             alt="<?= htmlspecialchars($rental['product_name']) ?>" 
                             class="img-thumbnail">
                        <p class="small mt-1 mb-0"><?= htmlspecialchars($rental['product_name']) ?> (<?= htmlspecialchars($rental['brand']) ?>)</p>
                    </div>
                </td>
                <td><?= htmlspecialchars($rental['owner_name'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($rental['start_date'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($rental['end_date'] ?? 'N/A') ?></td>
                <td>
                <span class="badge bg-<?= getStatusBadgeColor($rental['status']) ?>"> 
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $rental['status']))) ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?= getRemainingDaysBadgeColor($rental['remaining_days']) ?>">
                        <?= htmlspecialchars($rental['remaining_days']) ?>
                    </span>
                </td>
                <td>
                    <a href="rental_details.php?rental_id=<?= htmlspecialchars($rental['id']) ?>" class="btn btn-info btn-sm">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-center">No rentals found.</td>
        </tr>
    <?php endif; ?>
    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
