<?php
// Static transaction data (simulates a database)
$transactions = [
    '3352655445' => [
        'status' => 'IN PROGRESS',
        'date' => 'Dec 30, 2019 05:18',
        'total' => '₱500',
        'item_name' => 'PlayStation 5',
        'customer_name' => 'Juan Dela Cruz',
        'customer_contact' => '+63-945-555-0118',
    ],
    '1234567890' => [
        'status' => 'COMPLETED',
        'date' => 'Feb 2, 2019 19:28',
        'total' => '₱100',
        'item_name' => 'Xbox One',
        'customer_name' => 'Maria Clara',
        'customer_contact' => '+63-912-345-6789',
    ],
    '9876543210' => [
        'status' => 'CANCELED',
        'date' => 'Mar 20, 2019 23:14',
        'total' => '₱160',
        'item_name' => 'Nintendo Switch',
        'customer_name' => 'Pedro Penduko',
        'customer_contact' => '+63-987-654-3210',
    ],
];

// Get the Rental ID from the URL query parameter
$rental_id = isset($_GET['rental_id']) ? $_GET['rental_id'] : null;

// Fetch the transaction details based on rental ID
$transaction = $rental_id && isset($transactions[$rental_id]) ? $transactions[$rental_id] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details</title>
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
        .alert {
            padding: 20px;
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
                            Transaction Details
                        </li>
                    </ol>
                </nav>

                <!-- Main Content -->
                <div class="col-md-9 col-lg-10 content-container">
                    <h4 class="mb-4">Transaction Details</h4>

                    <?php if ($transaction): ?>
                        <div class="alert alert-info">
                            <h5>Rental ID: <?php echo $rental_id; ?></h5>
                            <p><strong>Status:</strong> <span class="status-<?php echo strtolower(str_replace(' ', '-', $transaction['status'])); ?>"><?php echo $transaction['status']; ?></span></p>
                            <p><strong>Date:</strong> <?php echo $transaction['date']; ?></p>
                            <p><strong>Total:</strong> <?php echo $transaction['total']; ?></p>
                            <p><strong>Item Name:</strong> <?php echo $transaction['item_name']; ?></p>
                            <p><strong>Customer Name:</strong> <?php echo $transaction['customer_name']; ?></p>
                            <p><strong>Customer Contact:</strong> <?php echo $transaction['customer_contact']; ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-danger">Transaction not found.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
