<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
    <!-- Bootstrap CSS -->
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
        /* Add hover effect to table rows */
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="#">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="#">Profile</a>
                        </li>
                        <li aria-current="page" class="breadcrumb-item active">
                            Transactions
                        </li>
                    </ol>
                </nav>

                <!-- Main Content -->
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
                                        <th scope="col">Status</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Total</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Placeholder: Replace with database query
                                    $transactions = [
                                        ["id" => "#3352655445", "status" => "IN PROGRESS", "date" => "Dec 30, 2019 05:18", "total" => "₱500"],
                                        ["id" => "#3352655446", "status" => "COMPLETED", "date" => "Feb 2, 2019 19:28", "total" => "₱80"],
                                        ["id" => "#3352655447", "status" => "CANCELED", "date" => "Mar 20, 2019 23:14", "total" => "₱160"],
                                        ["id" => "#3352655448", "status" => "COMPLETED", "date" => "Feb 2, 2019 19:28", "total" => "₱90"],
                                        ["id" => "#3352655449", "status" => "COMPLETED", "date" => "Feb 2, 2019 19:28", "total" => "₱100"],
                                        ["id" => "#3352655450", "status" => "CANCELED", "date" => "Dec 30, 2019 07:52", "total" => "₱100"],
                                        ["id" => "#3352655451", "status" => "COMPLETED", "date" => "Dec 7, 2019 23:26", "total" => "₱100"],
                                    ];

                                    foreach ($transactions as $transaction) {
                                        // Add status-specific classes for styling
                                        $statusClass = strtolower(str_replace(' ', '-', $transaction["status"]));
                                        echo "<tr>
                                            <td>{$transaction['id']}</td>
                                            <td class='status-{$statusClass}'>{$transaction['status']}</td>
                                            <td>{$transaction['date']}</td>
                                            <td>{$transaction['total']}</td>
                                            <td><a href='transaction-details.php?rental_id={$transaction['id']}' class='text-decoration-none'>View Details →</a></td>
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
