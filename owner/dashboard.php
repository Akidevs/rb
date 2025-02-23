<?php
ini_set('display_errors', 0); // Disable error display in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php'; // Include your database connection
require_once 'functions.php'; // Include your custom functions (for CSRF validation and others)

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    header("Location: /rb/login.php");
    exit();
}

// Fetch the user's name from the session
$userId = $_SESSION['id']; // Assuming user ID is stored in the session
$query = "SELECT name FROM users WHERE id = :userId";
$stmt = $conn->prepare($query);
$stmt->execute(['userId' => $userId]);
$user = $stmt->fetch();
$username = $user ? $user['name'] : 'User';

// Fetch the earnings for the month
try {
    // Filter earnings by owner_id to get the logged-in user's earnings
    $earningsQuery = "SELECT SUM(total_cost) AS total_earnings FROM rentals WHERE MONTH(start_date) = MONTH(CURRENT_DATE) AND owner_id = :userId";
    $earningsStmt = $conn->prepare($earningsQuery);
    $earningsStmt->execute(['userId' => $userId]);
    $earnings = $earningsStmt->fetch();
    $totalEarnings = $earnings['total_earnings'] ?? 0.00;
} catch (Exception $e) {
    log_error("Earnings Fetch Error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch earnings: " . $e->getMessage();
    $totalEarnings = 0.00;
}

// Fetch rental count for the logged-in owner
try {
    $rentalQuery = "SELECT COUNT(id) AS total_rentals FROM rentals WHERE status = 'approved' AND owner_id = :userId";
    $rentalStmt = $conn->prepare($rentalQuery);
    $rentalStmt->execute(['userId' => $userId]);
    $rentalData = $rentalStmt->fetch();
    $totalRentals = $rentalData['total_rentals'] ?? 0;
} catch (Exception $e) {
    log_error("Rentals Fetch Error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch rentals: " . $e->getMessage();
    $totalRentals = 0;
}

// Fetch top earning gadgets for the logged-in owner
try {
    $gadgetsQuery = "SELECT p.name, COUNT(r.id) AS rentals_count, p.image 
                     FROM products p 
                     JOIN rentals r ON p.id = r.product_id 
                     WHERE p.owner_id = :userId
                     GROUP BY p.name 
                     ORDER BY rentals_count DESC LIMIT 2";
    $gadgetsStmt = $conn->prepare($gadgetsQuery);
    $gadgetsStmt->execute(['userId' => $userId]);
    $gadgets = $gadgetsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    log_error("Top Gadgets Fetch Error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch top gadgets: " . $e->getMessage();
    $gadgets = [];
}

// Fetch listed gadgets for the logged-in owner
try {
    $productsQuery = "SELECT * FROM products WHERE owner_id = :userId";
    $productsStmt = $conn->prepare($productsQuery);
    $productsStmt->execute(['userId' => $userId]);
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    log_error("Products Fetch Error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch products: " . $e->getMessage();
    $products = [];
}

// Fetch alerts for the user (you can customize this based on your needs)
$alertsQuery = "SELECT * FROM support_requests WHERE status = 'open' ORDER BY created_at DESC LIMIT 3";
$alertsStmt = $conn->query($alertsQuery);
$alerts = $alertsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

    <?php include '../includes/owner-header-sidebar.php'; ?>

    <div class="main-content">
        <div class="row">
            <div class="col-md-9 offset-md-3 mt-4">
                <!-- Welcome Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="welcome">Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
                        <p class="overview">Here's Your Current Sales Overview</p>
                    </div>
                </div>

                <!-- Overview Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-hover shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Earnings this Month</h5>
                                <div>
                                    <h3>₱ <?php echo number_format($totalEarnings, 2); ?> <span class="text-success fs-5">&#x25B2;</span></h3>
                                    <p class="card-text text-muted">Increase compared to last week</p>
                                    <a href="#" class="text-decoration-none">Revenues report &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-hover shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Total Rentals</h5>
                                <div>
                                    <h3><?php echo $totalRentals; ?></h3>
                                    <p class="card-text text-muted">You closed <?php echo $totalRentals; ?> rentals this month.</p>
                                    <a href="#" class="text-decoration-none">All Rentals &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-hover shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Top Earning Gadgets</h5>
                                <div>
                                    <?php foreach ($gadgets as $gadget): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <img src="../img/uploads/<?php echo $gadget['image']; ?>" alt="<?php echo $gadget['name']; ?>" class="prod-img-db me-3">
                                            <div>
                                                <p class="mb-0 device-name"><?php echo $gadget['name']; ?></p>
                                                <span class="device-status text-muted"><?php echo $gadget['rentals_count']; ?> Rentals</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Listed Gadgets -->
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Listed Gadgets</h5>
                                <div>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Gadget</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($products as $product): ?>
                                                <tr>
                                                    <td class="d-flex align-items-center">
                                                        <img src="../img/uploads/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="prod-img-db me-3">
                                                        <span class="device-name"><?php echo $product['name']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div><?php echo $product['status']; ?></div>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
                                                        <button class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <a href="gadget.php" class="btn btn-primary">+ Add Gadget</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alerts & Reminders -->
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Alerts & Reminders</h5>
                                <div>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($alerts as $alert): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($alert['subject']); ?></h6>
                                                    <small><?php echo htmlspecialchars($alert['message']); ?></small>
                                                </div>
                                                <span class="badge bg-danger"><?php echo timeAgo($alert['created_at']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <a href="#" class="text-decoration-none">View All &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>