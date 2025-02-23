<?php
    require_once 'includes/owner-header-sidebar.php'; // Includes header and sidebar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Gadget Management</title>
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

<div class="main-content">
    <div class="container">
        <h2 class="mb-4">Gadget Management</h2>

        <!-- Search and Sort -->
        <div class="d-flex justify-content-between mb-3">
            <input type="text" class="form-control w-50" placeholder="Search">
            <div class="d-flex">
                <select class="form-select me-2" style="width: auto;">
                    <option selected>Sort by</option>
                    <option value="1">Category</option>
                    <option value="2">Price</option>
                </select>
                <button class="btn btn-outline-secondary"><i class="fas fa-filter"></i></button>
            </div>
        </div>

        <!-- Gadgets Table -->
        <div class="table-container">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Gadgets</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Rental History</th>
                        <th>Listed Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Example Row -->
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="includes/ps5.jpg" alt="Gadget">
                                <span class="ms-3">Playstation 5</span>
                            </div>
                        </td>
                        <td>Console</td>
                        <td>₱500/mo</td>
                        <td>Available</td>
                        <td>5 Rentals</td>
                        <td>1 Sept, 2024</td>
                        <td class="action-icons">
                            <i class="fas fa-pen text-primary"></i>
                            <i class="fas fa-trash text-danger"></i>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="includes/printer.jpg" alt="Gadget">
                                <span class="ms-3">Printer</span>
                            </div>
                        </td>
                        <td>School/Office</td>
                        <td>₱500/mo</td>
                        <td>Unavailable</td>
                        <td>5 Rentals</td>
                        <td>24 Oct, 2015</td>
                        <td class="action-icons">
                            <i class="fas fa-pen text-primary"></i>
                            <i class="fas fa-trash text-danger"></i>
                        </td>
                    </tr>
                    <!-- Add more rows as needed -->
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <small>Items per page:</small>
                <select class="form-select w-auto">
                    <option>4</option>
                    <option>8</option>
                    <option>12</option>
                </select>
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">Next</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>