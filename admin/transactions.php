<?php
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

// Handle Filter Selection (Default to All)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query based on the filter
$filterQuery = "";
$params = [];

if ($search) {
    $filterQuery .= " AND (p.name LIKE :search OR u.name LIKE :search OR ur.name LIKE :search)";
    $params[':search'] = "%$search%";
}

switch ($filter) {
    case 'pickup':  // Change 'meetup' to 'pickup'
        $filterQuery .= " AND r.status = 'delivery_in_progress'"; // Assuming pickup status is handled here
        break;
    case 'rented':
        $filterQuery .= " AND r.status = 'approved'";
        break;
    case 'returned':
        $filterQuery .= " AND r.status = 'returned'";
        break;
    case 'cancelled':
        $filterQuery .= " AND r.status = 'cancelled'";
        break;
    case 'dispute':
        $filterQuery .= " AND d.status = 'open'";
        break;
    default:
        break;
}


// Fetch Transaction Data
$query = "
    SELECT r.id AS rental_id, p.name AS product_name, p.image AS product_image, u.name AS owner_name, 
           ur.name AS renter_name, r.start_date, r.end_date, r.status
    FROM rentals r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.owner_id = u.id
    JOIN users ur ON r.renter_id = ur.id
    LEFT JOIN disputes d ON r.id = d.rental_id
    WHERE 1=1 $filterQuery
    ORDER BY r.created_at DESC
";

$transactions = fetchAllData($conn, $query, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Transaction Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .main-content {
            margin-left: 260px; /* Matches the sidebar width */
            padding: 80px 20px; /* Adjust padding for header spacing */
            background: #f8f9fa;
            min-height: 100vh;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .nav-tabs .nav-link {
            font-size: 1rem;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            background-color: #f8f9fa;
            border-color: #dee2e6 #dee2e6 #f8f9fa;
        }
        .table img {
            width: 50px;
            height: auto;
            border-radius: 5px;
        }
        .action-icons i {
            cursor: pointer;
            margin-right: 10px;
        }
    </style>
</head>
<body>
<?php include '../admin/includes/owner-header-sidebar.php'; ?>

<div class="main-content">
    <div class="container">
        <h2 class="mb-4">Transaction Management</h2>

        <div class="d-flex justify-content-between mb-3">
    <form method="get" action="" class="w-100">
        <div class="d-flex w-100">
            <input type="text" name="search" class="form-control w-75" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
            <div class="d-flex ms-2">
                <select class="form-select me-2" name="sort_by" style="width: auto;">
                    <option selected>Sort by</option>
                    <option value="1" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == '1' ? 'selected' : '' ?>>Rental ID</option>
                    <option value="2" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == '2' ? 'selected' : '' ?>>Gadget</option>
                </select>
                <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </form>
</div>

        <!-- Tabs for Transaction Filters -->
        <ul class="nav nav-tabs mb-3" id="transactionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filter == 'all' ? 'active' : '' ?>" href="?filter=all&search=<?= htmlspecialchars($search) ?>" role="tab">All</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filter == 'pickup' ? 'active' : '' ?>" href="?filter=pickup&search=<?= htmlspecialchars($search) ?>" role="tab">For Pickup/Delivery</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filter == 'rented' ? 'active' : '' ?>" href="?filter=rented&search=<?= htmlspecialchars($search) ?>" role="tab">Rented</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filter == 'returned' ? 'active' : '' ?>" href="?filter=returned&search=<?= htmlspecialchars($search) ?>" role="tab">Returned</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filter == 'cancelled' ? 'active' : '' ?>" href="?filter=cancelled&search=<?= htmlspecialchars($search) ?>" role="tab">Cancelled</a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $filter == 'dispute' ? 'active' : '' ?>" href="?filter=dispute&search=<?= htmlspecialchars($search) ?>" role="tab">For Dispute</a>
    </li>
</ul>


        <!-- Transactions Table -->
        <div class="table-container">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Rental ID</th>
                        <th>Gadgets</th>
                        <th>Owners</th>
                        <th>Renters</th>
                        <th>Started On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= htmlspecialchars($transaction['rental_id']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="/rb/img/uploads/<?= htmlspecialchars($transaction['product_image']) ?>" alt="Gadget">
                                        <span class="ms-3"><?= htmlspecialchars($transaction['product_name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($transaction['owner_name']) ?></td>
                                <td><?= htmlspecialchars($transaction['renter_name']) ?></td>
                                <td><?= htmlspecialchars($transaction['start_date']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $transaction['status'] === 'approved' ? 'primary' : ($transaction['status'] === 'returned' ? 'success' : 'danger') ?>">
                                        <?= ucfirst($transaction['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>