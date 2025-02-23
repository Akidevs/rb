<?php
// owner/rentals.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db/db.php';

// Check if owner is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../owner/login.php');
    exit();
}

$ownerId = $_SESSION['id'];

// Fetch rentals for the owner
$sql = "SELECT r.*, p.name AS product_name, u.name AS renter_name
        FROM rentals r
        INNER JOIN products p ON r.product_id = p.id
        INNER JOIN users u ON r.renter_id = u.id
        WHERE r.owner_id = :ownerId
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':ownerId', $ownerId, PDO::PARAM_INT);
$stmt->execute();
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate remaining_days for each rental
foreach ($rentals as &$rental) {
    if (in_array($rental['status'], ['completed', 'returned'])) {
        $rental['remaining_days'] = 'Completed';
    } elseif ($rental['status'] === 'cancelled') {
        $rental['remaining_days'] = 'Cancelled';
    } elseif ($rental['status'] === 'overdue') {
        $rental['remaining_days'] = 'Overdue';
    } elseif (!empty($rental['end_date'])) { 
        $today = new DateTime();
        $endDate = new DateTime($rental['end_date']);
        $interval = $today->diff($endDate);
        $days = (int)$interval->format('%R%a'); 

        if ($days > 0) {
            $rental['remaining_days'] = $days . ' day' . ($days > 1 ? 's' : '');
        } elseif ($days < 0) {
            $rental['remaining_days'] = 'Overdue';
            if ($rental['status'] !== 'overdue') {
                $updateSql = "UPDATE rentals SET status = 'overdue', updated_at = NOW() WHERE id = :rentalId";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bindParam(':rentalId', $rental['id'], PDO::PARAM_INT);
                $updateStmt->execute();
                $rental['status'] = 'overdue';
            }
        } else {
            $rental['remaining_days'] = 'Due Today';
        }
    } else {
        $rental['remaining_days'] = 'N/A';
    }
}
unset($rental);

// Define the status flow
$statusFlow = [
    'pending_confirmation' => 'Rent Pending',
    'approved' => 'Rent Confirmed',
    'delivery_in_progress' => 'Delivery in Progress',
    'delivered' => 'Delivered',
    'renting' => 'Renting',
    'completed' => 'Completed',
    'returned' => 'Returned',
    'cancelled' => 'Cancelled',
    'overdue' => 'Overdue'
];

// Helper function to determine if a status should be active
function isStatusActive($statusKey, $currentStatus, $statusFlow) {
    $keys = array_keys($statusFlow);
    $currentIndex = array_search($currentStatus, $keys);
    $statusIndex = array_search($statusKey, $keys);

    if ($statusIndex === false) {
        return false;
    }

    return $statusIndex <= $currentIndex;
}

// Generate CSRF token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <title>Rentals Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Custom Styles */
        #sidebarMenu {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding-top: 56px;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        main {
            padding-top: 56px;
        }
        .table-container {
            padding: 20px;
        }
        .badge-overdue {
            background-color: #dc3545;
        }
        .badge-completed {
            background-color: #28a745;
        }
        .badge-due-today {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-n-a {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="bg-secondary-subtle my-3">
                    <div class="card rounded-3">
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-2 mx-5">
                            <h2 class="mb-0">Rentals Management</h2>
                        </div>

                        <div class="card-body rounded-5">
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered text-center">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>No.</th>
                                                <th>Renter Name</th>
                                                <th>Product Name</th>
                                                <th>Start Date</th>
                                                <th>Due Date</th>
                                                <th>Days Remaining</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
    <?php if (!empty($rentals)): ?>
        <?php foreach ($rentals as $index => $rental): ?>
            <tr>
                <td><?= htmlspecialchars($index + 1) ?></td>
                <td><?= htmlspecialchars($rental['renter_name'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($rental['product_name'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($rental['start_date'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($rental['end_date'] ?? 'N/A') ?></td>
                <td>
                    <?php
                        $remaining = htmlspecialchars($rental['remaining_days']);
                        switch ($remaining) {
                            case 'Completed':
                                echo '<span class="badge bg-success">Completed</span>';
                                break;
                            case 'Overdue':
                                echo '<span class="badge bg-danger">Overdue</span>';
                                break;
                            case 'Due Today':
                                echo '<span class="badge bg-warning text-dark">Due Today</span>';
                                break;
                            case 'N/A':
                                echo '<span class="badge bg-secondary">N/A</span>';
                                break;
                            default:
                                if (strpos($remaining, 'day') !== false) {
                                    echo '<span class="badge bg-info">' . $remaining . '</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">N/A</span>';
                                }
                                break;
                        }
                    ?>
                </td>
                <td>
                    <?php
                        $statusClass = 'secondary'; 
                        $statusLabel = 'Unknown';

                        switch ($rental['status'] ?? 'unknown') {
                            case 'pending_confirmation':
                                $statusClass = 'warning';
                                $statusLabel = 'Rent Pending';
                                break;
                            case 'approved':
                                $statusClass = 'primary';
                                $statusLabel = 'Rent Confirmed';
                                break;
                            case 'delivery_in_progress':
                                $statusClass = 'info';
                                $statusLabel = 'Delivery in Progress';
                                break;
                            case 'delivered':
                                $statusClass = 'info';
                                $statusLabel = 'Delivered';
                                break;
                            case 'renting':
                                $statusClass = 'info';
                                $statusLabel = 'Renting';
                                break;
                            case 'completed':
                                $statusClass = 'success';
                                $statusLabel = 'Completed';
                                break;
                            case 'returned':
                                $statusClass = 'success';
                                $statusLabel = 'Returned';
                                break;
                            case 'cancelled':
                                $statusClass = 'danger';
                                $statusLabel = 'Cancelled';
                                break;
                            case 'overdue':
                                $statusClass = 'danger';
                                $statusLabel = 'Overdue';
                                break;
                            default:
                                $statusClass = 'secondary';
                                $statusLabel = 'Unknown';
                                break;
                        }

                        echo '<span class="badge bg-' . $statusClass . '">' . htmlspecialchars($statusLabel) . '</span>';
                    ?>
                </td>
                <td>
                    <!-- View Button -->
                    <a href="view_rental.php?rental_id=<?= htmlspecialchars($rental['id']) ?>" class="btn btn-info btn-sm">View</a>
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
                        </div>
                    </div>
                </main>
            </div>
        </div>

    </body>
</html>