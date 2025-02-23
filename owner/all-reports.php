<?php
ini_set('display_errors', 0); // Disable error display in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../db/db.php'; // Include your database connection
require_once 'functions.php'; // Include your custom functions (for CSRF validation and others)
    // Ensure the user is logged in and is an owner
    if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
        header("Location: /rb/login.php");
        exit();
    }

    $userId = $_SESSION['id']; // The logged-in user ID

    // Fetch data for charts and tables

    // Total Income Chart (Monthly)
    $currentYear = date('Y');
    $incomeData = array_fill(0, 12, 0);
    $stmt = $conn->prepare("SELECT MONTH(created_at) AS month, SUM(total_cost) AS total 
                          FROM rentals 
                          WHERE YEAR(created_at) = ? AND owner_id = ? AND status IN ('completed', 'returned')
                          GROUP BY MONTH(created_at)");
    $stmt->execute([$currentYear, $userId]);
    while ($row = $stmt->fetch()) {
        $incomeData[$row['month'] - 1] = $row['total'];
    }

    // Earning This Month Chart (Weekly)
    $currentMonth = date('n');
    $weeksData = array_fill(0, 4, 0);
    $stmt = $conn->prepare("SELECT WEEK(created_at, 1) - WEEK(DATE_FORMAT(created_at, '%Y-%m-01'), 1) + 1 AS week_number,
                                  SUM(total_cost) AS total
                           FROM rentals
                           WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
                           AND owner_id = ? AND status IN ('completed', 'returned')
                           GROUP BY week_number");
    $stmt->execute([$currentMonth, $currentYear, $userId]);
    while ($row = $stmt->fetch()) {
        if ($row['week_number'] <= 4) {
            $weeksData[$row['week_number'] - 1] = $row['total'];
        }
    }

    // Rental Frequency Chart
    $rentalFrequency = [];
    $stmt = $conn->prepare("SELECT p.category, COUNT(r.id) AS count 
                        FROM rentals r 
                        JOIN products p ON r.product_id = p.id 
                        WHERE r.owner_id = ? AND r.status IN ('completed', 'returned')
                        GROUP BY p.category");
    $stmt->execute([$userId]);
    $rentalFrequency = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Maintenance & Issues
    $maintenanceIssues = [];
    $stmt = $conn->prepare("SELECT p.name AS gadget, gc.condition_description AS issue_reported, gc.reported_at 
                        FROM gadget_conditions gc
                        JOIN products p ON gc.product_id = p.id
                        WHERE p.owner_id = ?");
    $stmt->execute([$userId]);
    $maintenanceIssues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transaction History
    $transactions = [];
    $stmt = $conn->prepare("SELECT r.created_at AS date, p.name AS gadget, u.name AS renter, 
                        r.total_cost AS amount, r.status AS payment_status
                        FROM rentals r
                        JOIN products p ON r.product_id = p.id
                        JOIN users u ON r.renter_id = u.id
                        WHERE p.owner_id = ?
                        ORDER BY r.created_at DESC");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gadget Availability
    $available = $conn->prepare("SELECT SUM(quantity) FROM products WHERE owner_id = ? AND status = 'approved'");
    $available->execute([$userId]);
    $available = $available->fetchColumn();

    $rented = $conn->prepare("SELECT COUNT(*) FROM rentals WHERE owner_id = ? AND status = 'renting'");
    $rented->execute([$userId]);
    $rented = $rented->fetchColumn();

    $inMaintenance = $conn->prepare("SELECT COUNT(DISTINCT product_id) FROM gadget_conditions gc
                                    JOIN products p ON gc.product_id = p.id WHERE p.owner_id = ?");
    $inMaintenance->execute([$userId]);
    $inMaintenance = $inMaintenance->fetchColumn();

    // Ratings
    $ratings = [];
    $stmt = $conn->prepare("SELECT p.category, AVG(c.rating) AS avg_rating
                        FROM comments c
                        JOIN products p ON c.product_id = p.id
                        WHERE p.owner_id = ?
                        GROUP BY p.category");
    $stmt->execute([$userId]);
    $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Commission
    $totalEarnings = $conn->prepare("SELECT SUM(total_cost) FROM rentals WHERE status IN ('completed', 'returned') AND owner_id = ?");
    $totalEarnings->execute([$userId]);
    $totalEarnings = $totalEarnings->fetchColumn();

    $commissionRate = 0.10;
    $commission = $totalEarnings * $commissionRate;
    $netEarnings = $totalEarnings - $commission;
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="mb-0">All Reports</h2>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Income</h5>
                                <?php if(array_sum($incomeData) > 0): ?>
                                    <canvas id="totalIncomeChart"></canvas>
                                <?php else: ?>
                                    <p class="text-muted">No income data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Earning This Month</h5>
                                <?php if(array_sum($weeksData) > 0): ?>
                                    <canvas id="earningThisMonthChart"></canvas>
                                <?php else: ?>
                                    <p class="text-muted">No earnings data for this month</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rental Frequency & Maintenance -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Rental Frequency</h5>
                                <?php if(!empty($rentalFrequency)): ?>
                                    <canvas id="rentalFrequencyChart"></canvas>
                                <?php else: ?>
                                    <p class="text-muted">No rental data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Maintenance & Issues</h5>
                                <?php if(!empty($maintenanceIssues)): ?>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Gadget</th>
                                                <th>Issue Reported</th>
                                                <th>Date Reported</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($maintenanceIssues as $issue): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($issue['gadget']) ?></td>
                                                    <td><?= htmlspecialchars($issue['issue_reported']) ?></td>
                                                    <td><?= date('M j, Y', strtotime($issue['reported_at'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">No maintenance issues reported</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History & Availability -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Transaction History</h5>
                                <?php if(!empty($transactions)): ?>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Gadget</th>
                                                <th>Renter</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($transactions as $transaction): ?>
                                                <tr>
                                                    <td><?= date('Y-m-d', strtotime($transaction['date'])) ?></td>
                                                    <td><?= htmlspecialchars($transaction['gadget']) ?></td>
                                                    <td><?= htmlspecialchars($transaction['renter']) ?></td>
                                                    <td>₱<?= number_format($transaction['amount'], 2) ?></td>
                                                    <td><?= ucfirst($transaction['payment_status']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">No transaction history available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Gadgets Availability & Ratings</h5>
                                <p>Available: <strong><?= $available ?></strong></p>
                                <p>Rented: <strong><?= $rented ?></strong></p>
                                <p>In Maintenance: <strong><?= $inMaintenance ?></strong></p>
                                <?php foreach($ratings as $rating): ?>
                                    <p><?= $rating['category'] ?>: 
                                        <span class="text-warning">
                                            <?= str_repeat('⭐', round($rating['avg_rating'])) ?>
                                            (<?= number_format($rating['avg_rating'], 1) ?>)
                                        </span>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commission -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Commission Deducted</h5>
                                <p>Total Commission Deducted: <strong>₱<?= number_format($commission ?? 0, 2) ?></strong></p>
<p>Commission Rate: <strong><?= $commissionRate * 100 ?>%</strong></p>
<p>Earnings Before Deduction: <strong>₱<?= number_format($totalEarnings ?? 0, 2) ?></strong></p>
<p>Net Earnings After Deduction: <strong>₱<?= number_format($netEarnings ?? 0, 2) ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Total Income Chart
        <?php if(array_sum($incomeData) > 0): ?>
            new Chart(document.getElementById('totalIncomeChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Total Income',
                        data: <?= json_encode($incomeData) ?>,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false
                    }]
                }
            });
        <?php else: ?>
            document.getElementById('totalIncomeChart').parentElement.innerHTML = '<p class="text-muted">No income data available</p>';
        <?php endif; ?>

        // Earning This Month Chart
        <?php if(array_sum($weeksData) > 0): ?>
            new Chart(document.getElementById('earningThisMonthChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{
                        label: 'Earnings',
                        data: <?= json_encode($weeksData) ?>,
                        backgroundColor: 'rgba(153, 102, 255, 0.6)'
                    }]
                }
            });
        <?php else: ?>
            document.getElementById('earningThisMonthChart').parentElement.innerHTML = '<p class="text-muted">No earnings data for this month</p>';
        <?php endif; ?>

        // Rental Frequency Chart
        <?php if(!empty($rentalFrequency)): ?>
            new Chart(document.getElementById('rentalFrequencyChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_column($rentalFrequency, 'category')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($rentalFrequency, 'count')) ?>,
                        backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(75, 192, 192, 0.6)']
                    }]
                }
            });
        <?php else: ?>
            document.getElementById('rentalFrequencyChart').parentElement.innerHTML = '<p class="text-muted">No rental data available</p>';
        <?php endif; ?>
    </script>
</body>
</html>