<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "PROJECT";

try {
    // Establish database connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Utility Functions
function fetchData(PDO $conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return false;
    }
}

function fetchAllData(PDO $conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return [];
    }
}

// Check Admin Login
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch Key Statistics
$statsQuery = "SELECT 
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM rentals) AS total_rentals,
                (SELECT COUNT(*) FROM products WHERE status = 'pending_approval') AS pending_gadgets,
                (SELECT COUNT(*) FROM support_requests WHERE status = 'open') AS open_support_requests,
                (SELECT COUNT(*) FROM disputes WHERE status = 'open') AS open_disputes,
                (SELECT COUNT(*) FROM products) AS total_gadgets,
                (SELECT COUNT(*) FROM users WHERE role = 'owner') AS total_owners,
                (SELECT COUNT(*) FROM users WHERE role = 'renter') AS total_renters,
                (SELECT COUNT(*) FROM disputes) AS total_disputes,
                (SELECT COUNT(*) FROM support_requests) AS total_support_requests";
$stats = fetchData($conn, $statsQuery);

// Fetch Recent Rentals
$recentRentalsQuery = "SELECT r.*, p.name AS product_name, u.name AS renter_name 
                       FROM rentals r 
                       JOIN products p ON r.product_id = p.id 
                       JOIN users u ON r.renter_id = u.id 
                       ORDER BY r.created_at DESC 
                       LIMIT 5";
$recentRentals = fetchAllData($conn, $recentRentalsQuery);

// Fetch Recent Support Requests
$recentSupportQuery = "SELECT sr.*, u.name AS user_name 
                       FROM support_requests sr 
                       JOIN users u ON sr.user_id = u.id 
                       ORDER BY sr.created_at DESC 
                       LIMIT 5";
$recentSupport = fetchAllData($conn, $recentSupportQuery);

// Fetch Monthly Rentals for Chart
$monthlyRentalsQuery = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS rentals 
                        FROM rentals 
                        GROUP BY month 
                        ORDER BY month ASC";
$monthlyRentals = fetchAllData($conn, $monthlyRentalsQuery);

// Handle missing stats
if (!$stats) {
    $stats = [
        'total_users' => 0,
        'total_rentals' => 0,
        'pending_gadgets' => 0,
        'open_support_requests' => 0,
        'open_disputes' => 0,
        'total_gadgets' => 0,
        'total_owners' => 0,
        'total_renters' => 0,
        'total_disputes' => 0,
        'total_support_requests' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Rentbox</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Custom styles for better visualization */
        .mt{
            margin-top: 80px;
        }
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt">Admin Dashboard</h2>

                <!-- Statistics Cards -->
                <div class="row mt-4">
                    <!-- Total Users -->
                    <div class="col-md-3">
                        <div class="card text-white bg-primary bg-gradient mb-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-people-fill card-icon"></i>
                                <div class="ms-3">
                                    <h5 class="card-title">Total Users</h5>
                                    <p class="card-text display-6"><?= htmlspecialchars($stats['total_users']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Total Rentals -->
                    <div class="col-md-3">
                        <div class="card text-white bg-success bg-gradient mb-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-cart-fill card-icon"></i>
                                <div class="ms-3">
                                    <h5 class="card-title">Total Rentals</h5>
                                    <p class="card-text display-6"><?= htmlspecialchars($stats['total_rentals']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Pending Gadgets -->
                    <div class="col-md-3">
                        <div class="card text-white bg-warning bg-gradient mb-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-hourglass-split card-icon"></i>
                                <div class="ms-3">
                                    <h5 class="card-title">Pending Gadgets</h5>
                                    <p class="card-text display-6"><?= htmlspecialchars($stats['pending_gadgets']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Open Support Requests -->
                    <div class="col-md-3">
                        <div class="card text-white bg-danger bg-gradient mb-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-chat-dots-fill card-icon"></i>
                                <div class="ms-3">
                                    <h5 class="card-title">Open Support Requests</h5>
                                    <p class="card-text display-6"><?= htmlspecialchars($stats['open_support_requests']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Statistics -->
                <div class="row">
                    <!-- Total Gadgets -->
                    <div class="col-md-3">
                        <div class="card text-white bg-info bg-gradient mb-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-box-seam card-icon"></i>
                                <div class="ms-3">
                                    <h5 class="card-title">Total Gadgets</h5>
                                    <p class="card-text display-6"><?= htmlspecialchars($stats['total_gadgets']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Total Owners -->
                    <div class="col-md-3">
                        <div class="card text-white bg-secondary bg-gradient mb-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-person-workspace card-icon"></i>
                                <div class="ms-3">
                                    <h5 class="card-title">Total Owners</h5>
                                    <p class="card-text display-6"><?= htmlspecialchars($stats['total_owners']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Total Renters -->
                    <div class="col-md-3">
                        <div class="card text-white bg-dark bg-gradient mb-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-person-check-fill card-icon"></i>
                                <div class="ms-3">
                                    <h5 class="card-title">Total Renters</h5>
                                    <p class="card-text display-6"><?= htmlspecialchars($stats['total_renters']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Total Disputes -->
                    <div class="col-md-3">
                        <div class="card text-white bg-secondary bg-gradient mb-3">
                            <div class="card-body d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill card-icon"></i>
                                <div class="ms-3">
                                    <h5 class="card-title">Total Disputes</h5>
                                    <p class="card-text display-6"><?= htmlspecialchars($stats['total_disputes']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Rentals and Support Requests -->
                <div class="row">
                    <!-- Recent Rentals -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Recent Rentals</span>
                                <a href="rentals.php" class="btn btn-sm btn-outline-secondary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Renter</th>
                                            <th>Start Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentRentals)): ?>
                                            <?php foreach ($recentRentals as $rental): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($rental['product_name']) ?></td>
                                                    <td><?= htmlspecialchars($rental['renter_name']) ?></td>
                                                    <td><?= htmlspecialchars(date('d M, Y', strtotime($rental['start_date']))) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= 
                                                            $rental['status'] === 'pending_confirmation' ? 'warning' : 
                                                            ($rental['status'] === 'approved' ? 'primary' : 
                                                            ($rental['status'] === 'completed' ? 'success' : 'secondary'))
                                                        ?>">
                                                            <?= ucfirst($rental['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No recent rentals.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Recent Support Requests -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Recent Support Requests</span>
                                <a href="support_requests.php" class="btn btn-sm btn-outline-secondary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentSupport)): ?>
                                            <?php foreach ($recentSupport as $support): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($support['user_name']) ?></td>
                                                    <td><?= htmlspecialchars($support['subject']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= 
                                                            $support['status'] === 'open' ? 'warning' : 
                                                            ($support['status'] === 'in_progress' ? 'info' : 'success')
                                                        ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $support['status'])) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No recent support requests.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Rentals Chart -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-3">
                            <div class="card-header">
                                Monthly Rentals
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="monthlyRentalsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Analytics and Reports -->
                <!-- You can add more charts or tables here as needed -->
            </main>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script>
        const ctx = document.getElementById('monthlyRentalsChart').getContext('2d');
        const labels = [<?php foreach ($monthlyRentals as $mr) echo '"' . htmlspecialchars($mr['month']) . '",'; ?>];
        const data = [<?php foreach ($monthlyRentals as $mr) echo htmlspecialchars($mr['rentals']) . ','; ?>];

        const monthlyRentalsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Number of Rentals',
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0
                    }
                }
            }
        });
    </script>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>