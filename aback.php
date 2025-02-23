<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/../db/db.php';


// Initialize search term
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = htmlspecialchars($_GET['search']);
}

// Initialize pagination variables
$limit = 8; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query to fetch products based on the search term, excluding 'pending_confirmation' status, with average rating and rating count
$sql = "
    SELECT 
        p.*, 
        IFNULL(AVG(c.rating), 0) AS average_rating, 
        COUNT(c.rating) AS rating_count
    FROM 
        products p
    LEFT JOIN 
        comments c ON p.id = c.product_id
    WHERE 
        (p.name LIKE :searchTerm OR p.description LIKE :searchTerm)
        AND TRIM(p.status) NOT IN ('pending_confirmation', 'pending_approval')
    GROUP BY 
        p.id
    LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of products (for pagination)
$totalProductsSql = "
    SELECT COUNT(*) as total FROM products p
    WHERE 
        (p.name LIKE :searchTerm OR p.description LIKE :searchTerm)
        AND p.status != 'pending_confirmation'
";
$stmt = $conn->prepare($totalProductsSql);
$stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
$stmt->execute();
$totalProducts = $stmt->fetch()['total'];
$totalPages = ceil($totalProducts / $limit);

// Format the product data as needed
$formattedProducts = [];
foreach ($allProducts as $product) {
    // Round the average rating to one decimal place for consistency
    $averageRating = round(floatval($product['average_rating']), 1);
    $ratingCount = intval($product['rating_count']);

    $formattedProducts[] = [
        'id' => $product['id'],
        'owner_id' => $product['owner_id'],
        'name' => htmlspecialchars($product['name']),
        'brand' => htmlspecialchars($product['brand']),
        'description' => htmlspecialchars($product['description']),
        'rental_price' => number_format($product['rental_price'], 2),
        'status' => htmlspecialchars($product['status']),
        'created_at' => $product['created_at'], // Format as needed
        'updated_at' => $product['updated_at'], // Format as needed
        'image' => $product['image'],
        'quantity' => $product['quantity'],
        'category' => htmlspecialchars($product['category']),
        'rental_period' => htmlspecialchars($product['rental_period']),
        'average_rating' => $averageRating,
        'rating_count' => $ratingCount
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Rentbox</title>
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <style>
        .card:hover {
            transform: scale(1.01); 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }

        .hover-effect {
    transition: transform 0.3s ease;
    position: relative;
}

.hover-effect:hover {
    transform: scale(1.02);
    z-index: 2;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

/* Remove or modify existing card styles */
.card:hover {
    /* Remove or modify this if needed */
}

.no-products {
    text-align: center;
    font-size: 1.2rem;
    color: #888;
    padding: 20px;
}

.toast {
    min-width: 350px;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease-in-out;
}

.toast-header {
    padding: 0.75rem 1rem;
    border-bottom: none;
}

.toast-body {
    padding: 1rem;
    font-weight: 500;
}

.progress {
    border-radius: 0 0 0.375rem 0.375rem;
    overflow: hidden;
}

    </style>
</head>
<body>

<body>
    <!-- Notification Toast -->
    <div class="position-fixed top-0 start-50 translate-middle-x mt-4" style="z-index: 9999">
        <div id="cartToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" 
             data-bs-autohide="true" data-bs-delay="3000">
            <div class="toast-header bg-success text-white">
                <i class="bi bi-cart-check me-2"></i>
                <strong class="me-auto">Success!</strong>
                <small class="text-white">Just now</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body bg-light">
                <span class="text-success"><i class="bi bi-check-circle-fill me-2"></i>Item added to cart!</span>
            </div>
            <div class="progress" style="height: 3px">
                <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
            </div>
        </div>
    </div>



    <div class="container-fluid image-bg m-0 p-0">
        <!-- Include Navbar -->
        <?php include '../includes/navbarr.php'; ?>

        <div class="container bg-body rounded-top-5 d-flex">
            <div class="mx-5 my-4 container-fluid d-flex justify-content-between align-items-center">
                <p class="fs-4 fw-bolder my-auto rb">Rent Gadgets, Your Way</p>
                <form class="d-flex gap-3 my-lg-0" method="GET" action="">
    <input class="form-control rounded-5 px-3 shadow-sm" 
           type="text" 
           placeholder="Type to search..."
           id="searchInput" 
           name="search" 
           value="<?php echo htmlspecialchars($searchTerm); ?>">
    <button class="btn btn-success rounded-5 px-4 py-0 m-0 shadow-sm" type="submit">
        Search
    </button>
</form>
            </div>
        </div>

        <div class="container-fluid bg-light rounded-start-3">
            <div class="row">
                <!-- Include Sidebar -->
                <?php include '../includes/sidebar.php'; ?>


                <!-- Products Display Area -->
                <div id="product-list" class="col-md-9 rounded-start-3 bg-body-secondary">
    <div class="mb-3 mt-0 container rounded-start-3 bg-body-secondary">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
            <?php foreach ($formattedProducts as $product): ?>
                <div class="col">
                    <div class="border rounded-3 p-3 bg-body hover-effect">
                        <a href="item.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                            <img src="../img/uploads/<?php echo $product['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="img-thumbnail shadow-sm product-image">
                            <p class="fs-5 mt-2 ms-2 mb-0 fw-bold"><?php echo htmlspecialchars($product['name']); ?></p>
                        </a>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <small class="ms-1 mb-0 text-secondary">
                                <i class="bi bi-star-fill text-warning me-1"></i>
                                <?php echo $product['average_rating']; ?> (<?php echo $product['rating_count']; ?>)
                            </small>
                            <p class="fs-5 ms-auto mb-0">₱<?php echo $product['rental_price']; ?><small class="text-secondary">/day</small></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
    <form action="add_to_cart.php" method="POST" class="d-inline">
        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page'] ?? 1); ?>">
        <button type="submit" class="btn btn-outline-dark btn-sm rounded-5 shadow-sm">
            Add to Cart
        </button>
    </form>
    <a href="item.php?id=<?php echo $product['id']; ?>" class="btn btn-success btn-sm rounded-5 shadow">
        Rent Now
    </a>
</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<!-- Pagination -->
<div class="mx-3 mb-4">
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-between">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" 
                   href="?search=<?php echo $searchTerm; ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>" 
                   aria-label="Previous">
                    <i class="bi bi-caret-left-fill"></i>
                </a>
            </li>
            <div class="d-flex gap-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" 
                           href="?search=<?php echo $searchTerm; ?>&page=<?php echo $i; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>">
                           <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </div>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" 
                   href="?search=<?php echo $searchTerm; ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>" 
                   aria-label="Next">
                    <i class="bi bi-caret-right-fill"></i>
                </a>
            </li>
        </ul>
        <p class="text-center">Page <?php echo $page; ?> of <?php echo $totalPages; ?></p>
    </nav>
</div>
                </div>
            </div>

            <!-- Recommendations Section -->
            <div class="px-5 py-5 bg-body">
                <div class="d-flex justify-content-between">
                    <p class="fs-5 fw-bold mb-3 active">Explore our Recommendations</p>
                    <div>
                        <button class="btn btn-outline-success"><i class="bi bi-arrow-left"></i></button>
                        <button class="btn btn-outline-success"><i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>
                <div class="row mb-3">
                    <!-- Add recommended products here if available -->
                </div>
            </div>

            <!-- Footer -->
            <footer class="text-center text-lg-start bg-body-tertiary text-muted border-top">
            <div class="d-flex flex-column flex-sm-row justify-content-between py-2 border-top">
                <p class="ps-3">© 2024 Rentbox. All rights reserved.</p>
                <ul class="list-unstyled d-flex pe-3">
                    <li class="ms-3"><a href=""><i class="bi bi-facebook text-body"></i></a></li>
                    <li class="ms-3"><a href=""><i class="bi bi-twitter text-body"></i></a></li>
                    <li class="ms-3"><a href=""><i class="bi bi-linkedin text-body"></i></a></li>
                </ul>
            </div>
            </footer>
        </div>
    </div>



    <script>
// Toast Notification System
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const toastEl = document.getElementById('cartToast');
    const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
    
    const progressBar = toastEl.querySelector('.progress-bar');
    
    if (urlParams.has('success') || urlParams.has('error')) {
        // Clean URL while preserving search state
        const cleanURL = new URL(window.location);
        ['success', 'error'].forEach(param => cleanURL.searchParams.delete(param));
        history.replaceState({}, document.title, cleanURL);

        // Configure toast
        const isSuccess = urlParams.has('success');
        toastEl.querySelector('.toast-header').className = `toast-header ${isSuccess ? 'bg-success' : 'bg-danger'} text-white`;
        toastEl.querySelector('.toast-body').innerHTML = `
            <span class="${isSuccess ? 'text-success' : 'text-danger'}">
                <i class="bi ${isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-2"></i>
                ${isSuccess ? 'Item added to cart!' : 'Failed to add item!'}
            </span>
        `;
        progressBar.className = `progress-bar ${isSuccess ? 'bg-success' : 'bg-danger'}`;

        // Animate progress bar
        progressBar.style.width = '100%';
        toastEl.addEventListener('shown.bs.toast', () => {
            progressBar.style.transition = 'width 3s linear';
            progressBar.style.width = '0%';
        });

        toast.show();
    }
});
</script>



    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search input interaction
        const searchInput = document.getElementById('searchInput');

        searchInput.addEventListener('focus', function() {
            this.classList.add('border-success');
        });

        searchInput.addEventListener('blur', function() {
            this.classList.remove('border-success');
        });
    </script>
</body>
</html>
