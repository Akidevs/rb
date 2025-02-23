<?php
// Static rental data to simulate active rentals
$rentals = [
    ["rental_id" => "#3352655445", "item_name" => "PlayStation 5", "status" => "IN PROGRESS", "rental_date" => "Dec 30, 2019 05:18", "total_amount" => "₱500"],
    ["rental_id" => "#3352655446", "item_name" => "Xbox One", "status" => "COMPLETED", "rental_date" => "Feb 2, 2019 19:28", "total_amount" => "₱80"],
    ["rental_id" => "#3352655447", "item_name" => "Nintendo Switch", "status" => "CANCELED", "rental_date" => "Mar 20, 2019 23:14", "total_amount" => "₱160"],
    ["rental_id" => "#3352655448", "item_name" => "MacBook Pro", "status" => "IN PROGRESS", "rental_date" => "Nov 20, 2023 10:22", "total_amount" => "₱3,500"],
    ["rental_id" => "#3352655449", "item_name" => "iPhone 14", "status" => "COMPLETED", "rental_date" => "Dec 5, 2023 13:45", "total_amount" => "₱700"],
    ["rental_id" => "#3352655450", "item_name" => "GoPro Hero 10", "status" => "IN PROGRESS", "rental_date" => "Nov 15, 2023 09:30", "total_amount" => "₱600"]
];

// Simulate empty rentals message
if (empty($rentals)) {
    $message = "You don't have any active rentals at the moment.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rentals</title>
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
                            My Rentals
                        </li>
                    </ol>
                </nav>

                <!-- Main Content -->
                <div class="col-md-9 col-lg-10 content-container">
                    <h4 class="mb-4">My Current Rentals</h4>

                    <?php if (isset($message)) { ?>
                        <div class="alert alert-warning">
                            <?php echo $message; ?>
                        </div>
                    <?php } else { ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Active Rentals</h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Rental ID</th>
                                            <th scope="col">Item</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Date</th>
                                            <th scope="col">Total</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rentals as $rental) { 
                                            $statusClass = strtolower(str_replace(' ', '-', $rental['status']));
                                        ?>
                                            <tr>
                                                <td><?php echo $rental['rental_id']; ?></td>
                                                <td><?php echo $rental['item_name']; ?></td>
                                                <td class="status-<?php echo $statusClass; ?>"><?php echo $rental['status']; ?></td>
                                                <td><?php echo $rental['rental_date']; ?></td>
                                                <td><?php echo $rental['total_amount']; ?></td>
                                                <td><a href="transaction-details.php?rental_id=<?php echo $rental['rental_id']; ?>" class="text-decoration-none">View Details →</a></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
