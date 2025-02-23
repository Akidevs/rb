<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Transactions</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <h3 class="mt-4">All Transactions</h3>

                <!-- Transactions Table -->
                <div class="card my-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
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
                                // Placeholder: Replace this with your database query
                                $transactions = [
                                    [
                                        "rental_id" => "#3352655445",
                                        "status" => "IN PROGRESS",
                                        "date" => "Dec 30, 2019 05:18",
                                        "total" => "₱500",
                                    ],
                                    [
                                        "rental_id" => "#3352655445",
                                        "status" => "COMPLETED",
                                        "date" => "Feb 2, 2019 19:28",
                                        "total" => "₱80",
                                    ],
                                    [
                                        "rental_id" => "#3352655445",
                                        "status" => "CANCELED",
                                        "date" => "Mar 20, 2019 23:14",
                                        "total" => "₱160",
                                    ],
                                    [
                                        "rental_id" => "#3352655445",
                                        "status" => "COMPLETED",
                                        "date" => "Feb 2, 2019 19:28",
                                        "total" => "₱90",
                                    ],
                                ];

                                // Display transactions dynamically
                                if (!empty($transactions)) {
                                    foreach ($transactions as $transaction) {
                                        // Assign color based on status
                                        $statusClass = match (strtolower($transaction['status'])) {
                                            'in progress' => 'text-warning',
                                            'completed' => 'text-success',
                                            'canceled' => 'text-danger',
                                            default => 'text-dark',
                                        };

                                        echo "<tr>
                                            <td>{$transaction['rental_id']}</td>
                                            <td class='{$statusClass}'>{$transaction['status']}</td>
                                            <td>{$transaction['date']}</td>
                                            <td>{$transaction['total']}</td>
                                            <td><a href='transaction-details.php?rental_id={$transaction['rental_id']}' class='btn btn-sm btn-primary'>View Details</a></td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>No transactions found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
