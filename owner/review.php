<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/db.php';

// Check if renter ID is provided in the URL
if (!isset($_GET['renter_id'])) {
    header('Location: ../owner/rentals.php');
    exit();
}

$renterId = intval($_GET['renter_id']);

// Database queries
$queries = [
    'renter' => $conn->prepare("SELECT name, profile_picture FROM users WHERE id = :renterId"),
    'rentals' => $conn->prepare("SELECT r.*, p.name AS product_name FROM rentals r
        INNER JOIN products p ON r.product_id = p.id
        WHERE r.renter_id = :renterId"),
    'reviews' => $conn->prepare("SELECT rr.*, u.name AS reviewer_name, u.profile_picture AS reviewer_picture 
        FROM renter_reviews rr
        INNER JOIN users u ON rr.owner_id = u.id
        WHERE rr.renter_id = :renterId"),
    'rating' => $conn->prepare("SELECT AVG(rating) as avg_rating FROM renter_reviews WHERE renter_id = :renterId")
];

// Execute all queries
foreach ($queries as $query) {
    $query->execute([':renterId' => $renterId]);
}

// Fetch results
$renter = $queries['renter']->fetch();
$rentals = $queries['rentals']->fetchAll();
$reviews = $queries['reviews']->fetchAll();
$rating = $queries['rating']->fetch();
$averageRating = $rating['avg_rating'] ? round($rating['avg_rating'], 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renter Profile - <?= htmlspecialchars($renter['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Main Layout */
        body {
            background-color: #f8f9fa;
        }
        
        .main-content {
            margin-left: 250px; /* Match sidebar width */
            padding: 50px;
            min-height: 100vh;
        }

        /* Profile Section */
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 59px;
            margin-left: 300px;
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-left: 300px;
            margin-bottom: 30px;
        }

        .table th {
            background-color: #f8f9fa;
        }

        /* Reviews */
        .review-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            margin-left: 0px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .reviewer-picture {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .rating-stars {
            color: #ffd700;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/owner-header-sidebar.php'; ?>

    <main class="main-content">
        <!-- Profile Section -->
        <div class="profile-card">
            <div class="profile-header">
                <img src="../<?= htmlspecialchars($renter['profile_picture'] ?: 'images/user/default.png') ?>" 
                     alt="Profile" 
                     class="profile-picture">
                <div>
                    <h2 class="mb-2"><?= htmlspecialchars($renter['name']) ?></h2>
                    <div class="rating-stars">
                        <?= str_repeat('⭐', $averageRating) ?>
                        <span class="text-muted ms-2">(<?= $averageRating ?>)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rentals Section -->
        <div class="table-container">
            <h3 class="mb-4">Rental History</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rentals)): ?>
                            <?php foreach ($rentals as $index => $rental): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($rental['product_name']) ?></td>
                                    <td><?= htmlspecialchars($rental['start_date']) ?></td>
                                    <td><?= htmlspecialchars($rental['end_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No rentals found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="table-container">
            <h3 class="mb-4">Reviews</h3>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="reviewer-info">
                            <img src="../<?= htmlspecialchars($review['reviewer_picture'] ?: 'images/user/default.png') ?>" 
                                 alt="Reviewer" 
                                 class="reviewer-picture">
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars($review['reviewer_name']) ?></h5>
                                <small class="text-muted">
                                    <?= date('F d, Y', strtotime($review['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                        <div class="rating-stars mb-2">
                            <?= str_repeat('⭐', $review['rating']) ?>
                        </div>
                        <p class="mb-0"><?= htmlspecialchars($review['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">No reviews available</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>