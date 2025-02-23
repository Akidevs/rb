<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "PROJECT";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch transaction data
$sql = "SELECT rentals.id AS rental_id, rentals.status, rentals.start_date, rentals.total_cost, products.name AS product_name
        FROM rentals
        JOIN products ON rentals.product_id = products.id
        JOIN users ON rentals.renter_id = users.id
        ORDER BY rentals.created_at DESC LIMIT 7"; // Limit to 7 most recent transactions

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        .content-container {
            padding: 20px;
        }
        .status-completed {
            color: green;
            font-weight: bold;
        }
        .status-canceled {
            color: red;
            font-weight: bold;
        }
        .status-in-progress {
            color: orange;
            font-weight: bold;
        }
        .view-all {
            text-decoration: none;
            font-weight: bold;
            color: orange;
        }
        .view-all:hover {
            color: darkorange;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>
    <?php include 'header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Profile</a></li>
                        <li aria-current="page" class="breadcrumb-item active">Transactions</li>
                    </ol>
                </nav>

                <div class="col-md-9 col-lg-10 content-container">
                    <h4 class="mb-4">Transactions</h4>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Orders</h5>
                            <a href="all-transaction.php" class="view-all">View All →</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Rental ID</th>
                                        <th scope="col">Product</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Total</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            // Determine the status class
                                            $statusClass = strtolower(str_replace(' ', '-', $row['status']));
                                            echo "<tr>
                                                <td>#{$row['rental_id']}</td>
                                                <td>{$row['product_name']}</td>
                                                <td class='status-{$statusClass}'>{$row['status']}</td>
                                                <td>{$row['start_date']}</td>
                                                <td>₱{$row['total_cost']}</td>
                                                <td><a href='transaction-details.php?rental_id={$row['rental_id']}' class='text-decoration-none'>View Details →</a></td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No transactions found</td></tr>";
                                    }
                                    $conn->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
