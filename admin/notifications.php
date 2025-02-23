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
    <title>Notifications</title>
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
        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-card.read {
            opacity: 0.7;
        }
        .notification-content {
            display: flex;
            align-items: center;
        }
        .notification-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            color: #007bff;
        }
        .notification-info {
            flex-grow: 1;
        }
        .notification-info h6 {
            margin: 0;
            font-size: 1rem;
        }
        .notification-info small {
            color: #6c757d;
        }
        .notification-actions i {
            cursor: pointer;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container">
        <h2 class="mb-4">Notifications</h2>

        <!-- Notifications List -->
        <div class="notification-list">
            <!-- Example Notification -->
            <div class="notification-card">
                <div class="notification-content">
                    <i class="fas fa-bell notification-icon"></i>
                    <div class="notification-info">
                        <h6>New Transaction Alert</h6>
                        <small>Your gadget "Playstation 5" has been rented by Juan dela Cruz.</small>
                    </div>
                </div>
                <div class="notification-actions">
                    <i class="fas fa-check text-success" title="Mark as Read"></i>
                    <i class="fas fa-trash text-danger" title="Delete"></i>
                </div>
            </div>

            <!-- Example Notification -->
            <div class="notification-card read">
                <div class="notification-content">
                    <i class="fas fa-envelope-open-text notification-icon"></i>
                    <div class="notification-info">
                        <h6>Verification Approved</h6>
                        <small>Your account verification has been successfully approved.</small>
                    </div>
                </div>
                <div class="notification-actions">
                    <i class="fas fa-trash text-danger" title="Delete"></i>
                </div>
            </div>

            <!-- Add more notifications as needed -->
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>