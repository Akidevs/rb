<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/db.php';

// Check if owner ID is provided in the URL
if (isset($_GET['owner_id'])) {
    $ownerId = intval($_GET['owner_id']);
} else {
    // Redirect if no owner ID is provided
    header('Location: ../renter/browse.php');
    exit();
}

// Fetch owner details from users table
$stmt = $conn->prepare("SELECT name, profile_picture, role FROM users WHERE id = :ownerId");
$stmt->execute([':ownerId' => $ownerId]);
$owner = $stmt->fetch();

// Fetch the account creation date from the user_verification table
$verificationStmt = $conn->prepare("SELECT created_at FROM user_verification WHERE user_id = :ownerId");
$verificationStmt->execute([':ownerId' => $ownerId]);
$verification = $verificationStmt->fetch();
$joinDate = $verification ? $verification['created_at'] : 'Unknown';

// Fetch products of the owner
$productStmt = $conn->prepare("SELECT * FROM products WHERE owner_id = :ownerId");
$productStmt->execute([':ownerId' => $ownerId]);
$products = $productStmt->fetchAll();

// Fetch reviews of the owner (from owner_reviews table)
$reviewStmt = $conn->prepare("SELECT * FROM owner_reviews WHERE owner_id = :ownerId");
$reviewStmt->execute([':ownerId' => $ownerId]);
$reviews = $reviewStmt->fetchAll();

// Fetch the average rating from owner_reviews
$ratingStmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM owner_reviews WHERE owner_id = :ownerId");
$ratingStmt->execute([':ownerId' => $ownerId]);
$rating = $ratingStmt->fetch();
$averageRating = $rating['avg_rating'] ? round($rating['avg_rating'], 1) : 0;

// Fetch reviews based on the logged-in user's role (owner or renter)
$isRenter = true; // Assume logged-in user is a renter

// Initialize reviewTable and filterColumn
$reviewTable = '';
$filterColumn = '';
$reviewerRole = '';

// Sorting logic (Newest, Oldest, Highest Rating, Lowest Rating)
$sortOrder = "created_at DESC"; // Default sorting by newest
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'oldest':
            $sortOrder = "created_at ASC";
            break;
        case 'highest_rating':
            $sortOrder = "rating DESC";
            break;
        case 'lowest_rating':
            $sortOrder = "rating ASC";
            break;
        default:
            $sortOrder = "created_at DESC";
            break;
    }
}

// Fetch reviews based on the user's role
if ($isRenter) {
    $reviewTable = "renter_reviews";  // Reviews from renter
    $filterColumn = "renter_id"; // Filter by renter ID for reviews from renter to owner
    $reviewerRole = "seller";  // Renter is the seller
} else {
    $reviewTable = "owner_reviews";  // Reviews from owner
    $filterColumn = "owner_id";  // Filter by owner ID for reviews from owner to renter
    $reviewerRole = "buyer";  // Owner is the buyer
}

// Fetch reviews based on sorting and filtering
$filter = "1"; // Default: show all reviews
if (isset($_GET['filter'])) {
    if ($_GET['filter'] == 'buyer') {
        // Filter by reviews from owners (buyer)
        $filterColumn = "owner_id";
    } elseif ($_GET['filter'] == 'seller') {
        // Filter by reviews from renters (seller)
        $filterColumn = "renter_id";
    }
}

$reviewsStmt = $conn->prepare("SELECT * FROM $reviewTable WHERE $filterColumn = :userId ORDER BY $sortOrder");
$reviewsStmt->execute([':userId' => $_SESSION['id']]);
$reviews = $reviewsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Rentbox - Owner Profile</title>
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
</head>
<style>
    /* Make the product grid display horizontally with space between */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); /* Automatically fill available space */
    gap: 20px;
    padding: 20px;
}

/* Product image styling */
.product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

/* Product card hover effect */
.listing-item {
    display: block;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    text-decoration: none;
    transition: transform 0.3s ease;
}

/* Add hover effect to make product item interactive */
.listing-item:hover {
    transform: translateY(-10px); /* Lift effect */
}

/* Listing details section */
.listing-details {
    padding: 10px;
    text-align: center;
}

.listing-price {
    font-size: 1.2em;
    font-weight: bold;
    color: #27ae60;
}

</style>
<body>
    <?php include '../includes/navbarr.php'; ?>

    <div class="container">
    <!-- Owner Profile Section -->
    <div class="profile-header">
        <img src="../<?php echo htmlspecialchars($owner['profile_picture'] ?: 'images/user/default.png'); ?>" alt="Owner Profile Picture" class="rounded-circle" width="80">
        <div>
            <h2><?php echo htmlspecialchars($owner['name']); ?></h2>
            <p class="text-muted">Joined: <?php echo date('Y-m-d', strtotime($joinDate)); ?></p>
            <div class="rating">
                <span class="text-warning"><?php echo str_repeat('⭐', floor($averageRating)); ?><?php echo str_repeat('☆', 5 - floor($averageRating)); ?></span>
                <span>(<?php echo count($reviews); ?> Reviews)</span>
            </div>
        </div>
    </div>

    <!-- Tabs for Listings and Reviews -->
    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="listings-tab" data-bs-toggle="tab" href="#listings" role="tab" aria-controls="listings" aria-selected="true">Listings</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="reviews-tab" data-bs-toggle="tab" href="#reviews" role="tab" aria-controls="reviews" aria-selected="false">Reviews</a>
        </li>
    </ul>

    <div class="tab-content" id="profileTabsContent">
        <!-- Listings Tab -->
        <div class="tab-pane fade show active" id="listings" role="tabpanel" aria-labelledby="listings-tab">
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <a href="../renter/item.php?id=<?php echo $product['id']; ?>" class="listing-item">
                        <img src="../img/uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="product-image">
                        <div class="listing-details">
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="listing-price">PHP <?php echo number_format($product['rental_price'], 2); ?></p>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Reviews Tab -->
        <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                <h5>Reviews for <?php echo htmlspecialchars($owner['name']); ?></h5>
                
                <!-- Sort and Filter Controls -->
                <div class="sort-filter">
                    <div class="d-flex justify-content-between">
                        <div class="filter">
                            <a href="<?php echo $_SERVER['REQUEST_URI']; ?>&filter=all" class="btn btn-outline-secondary">All</a>
                            <a href="<?php echo $_SERVER['REQUEST_URI']; ?>&filter=buyer" class="btn btn-outline-secondary">From Owners</a>
                            <a href="<?php echo $_SERVER['REQUEST_URI']; ?>&filter=seller" class="btn btn-outline-secondary">From Renters</a>
                        </div>
                    </div>
                </div>

                <!-- Reviews Display -->
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
    <div class="review-item">
        <?php
            // Fetch reviewer name and profile picture
            if ($reviewerRole == 'buyer') {
                $reviewerStmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = :userId");
                $reviewerStmt->execute([':userId' => $review['renter_id']]);
            } else {
                $reviewerStmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = :userId");
                $reviewerStmt->execute([':userId' => $review['owner_id']]);
            }

            $reviewer = $reviewerStmt->fetch();
            
            // Determine the correct profile picture path
            if ($reviewer && !empty($reviewer['profile_picture'])) {
                // Full debug output of the profile picture path
                $reviewerImage = '../' . $reviewer['profile_picture'];
            } else {
                $reviewerImage = '../images/user/default.png';
            }
            
            $reviewerName = $reviewer ? $reviewer['name'] : 'Anonymous';
        ?>

        <div class="d-flex align-items-start mb-3">
            <img src="<?php echo htmlspecialchars($reviewerImage); ?>" 
                 alt="Reviewer Image" 
                 class="rounded-circle me-3" 
                 width="50" 
                 height="50"
                 onerror="this.src='../images/user/default.png';">
            <div class="review-content">
                <p class="mb-1"><strong><?php echo htmlspecialchars($reviewerName); ?></strong> 
                   <span class="text-muted">(<?php echo date('Y-m-d', strtotime($review['created_at'])); ?>)</span></p>
                <p class="mb-1">Rating: <?php echo str_repeat('⭐', $review['rating']); ?><?php echo str_repeat('☆', 5 - $review['rating']); ?></p>
                <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
            </div>
        </div>
    </div>
<?php endforeach; ?>


                <?php else: ?>
                    <p>No reviews available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
