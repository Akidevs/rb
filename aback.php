<?php
// item.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/../db/db.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    // Redirect to login page
    header('Location: ../renter/login.php');
    exit();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get the user ID from the session
$userId = $_SESSION['id'];

// Get the product ID from the URL
if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
} else {
    // If no ID is provided, show an error message
    die("Product ID not specified.");
}

// Handle form submission to add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    // Optional: Verify CSRF token for add to cart action
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid CSRF token.";
    } else {
        // Check product availability
        $productSql = "SELECT quantity FROM products WHERE id = :productId";
        $productStmt = $conn->prepare($productSql);
        $productStmt->bindParam(':productId', $productId, PDO::PARAM_INT);
        $productStmt->execute();
        $product = $productStmt->fetch();

        if (!$product || $product['quantity'] < 1) {
            $message = "Product is currently unavailable.";
        } else {
            // Check if the item is already in the cart
            $checkCartSql = "SELECT * FROM cart_items WHERE renter_id = :userId AND product_id = :productId";
            $checkCartStmt = $conn->prepare($checkCartSql);
            $checkCartStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $checkCartStmt->bindParam(':productId', $productId, PDO::PARAM_INT);
            $checkCartStmt->execute();
            $existingCartItem = $checkCartStmt->fetch();

            if ($existingCartItem) {
                // Item already in cart
                $message = "Item is already in your cart.";
            } else {
                // Insert the item into the cart
                $insertCartSql = "INSERT INTO cart_items (renter_id, product_id, created_at, updated_at) VALUES (:userId, :productId, NOW(), NOW())";
                $insertCartStmt = $conn->prepare($insertCartSql);
                $insertCartStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                $insertCartStmt->bindParam(':productId', $productId, PDO::PARAM_INT);
                if ($insertCartStmt->execute()) {
                    $message = "Item added to cart successfully.";
                } else {
                    $message = "Failed to add item to cart.";
                }
            }
        }
    }
}

// Query to fetch the product
$sql = "SELECT * FROM products WHERE id = :productId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Format the product data as needed
$productData = [
    'id' => $product['id'],
    'owner_id' => $product['owner_id'],
    'name' => htmlspecialchars($product['name']),
    'brand' => htmlspecialchars($product['brand']),
    'description' => htmlspecialchars($product['description']),
    'rental_price' => number_format($product['rental_price'], 2),
    'status' => htmlspecialchars($product['status']),
    'created_at' => $product['created_at'],
    'updated_at' => $product['updated_at'],
    'image' => $product['image'],
    'quantity' => $product['quantity'],
    'category' => htmlspecialchars($product['category']),
    'rental_period' => htmlspecialchars($product['rental_period']),
];

// Pagination Settings
$commentsPerPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $commentsPerPage;

// Fetch total number of comments
$totalCommentsSql = "SELECT COUNT(*) FROM comments WHERE product_id = :productId";
$totalCommentsStmt = $conn->prepare($totalCommentsSql);
$totalCommentsStmt->bindParam(':productId', $productId, PDO::PARAM_INT);
$totalCommentsStmt->execute();
$totalComments = $totalCommentsStmt->fetchColumn();
$totalPages = ceil($totalComments / $commentsPerPage);

// Fetch comments with pagination
$commentsSql = "SELECT c.*, u.name AS renter_name 
               FROM comments c
               INNER JOIN users u ON c.renter_id = u.id
               WHERE c.product_id = :productId
               ORDER BY c.created_at DESC
               LIMIT :limit OFFSET :offset";
$commentsStmt = $conn->prepare($commentsSql);
$commentsStmt->bindParam(':productId', $productId, PDO::PARAM_INT);
$commentsStmt->bindParam(':limit', $commentsPerPage, PDO::PARAM_INT);
$commentsStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$commentsStmt->execute();
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Existing head content -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Rentbox - <?= $productData['name']; ?></title>
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../vendor/flatpickr.min.css">
    <link rel="stylesheet" href="../other.css">
    <style>
        /* Comment Section Styles */
        .comment-section {
            margin-top: 40px;
        }
        .comment {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        .comment:last-child {
            border-bottom: none;
        }
        .comment .rating {
            color: #f8ce0b;
        }
        .comment .author {
            font-weight: bold;
        }
        .comment .date {
            font-size: 0.9em;
            color: #6c757d;
        }
        .pagination {
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php
        require_once '../includes/navbarr.php';
    ?>
    <hr class="m-0 p-0 opacity-25">

    <!-- Display message if set -->
    <?php if (isset($message)): ?>
        <div class="alert alert-success text-center" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="bg-body-secondary p-4">      
        <main class="bg-body mx-3 p-5 rounded-5 d-flex flex-column mb-5">
            
            <div class="d-flex flex-row mb-4">
                <!-- Product Images Carousel -->
                <div id="carouselIndicator" class="carousel carousel-dark slide me-3">
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#carouselIndicator" data-bs-slide-to="0" class="active border rounded" aria-current="true" aria-label="Slide 1"></button>
                        <!-- Add more indicators if you have multiple images -->
                    </div>
                    <div class="carousel-inner border" style="width:600px; height:400px;">
                        <div class="carousel-item active">
                            <img src="../img/uploads/<?php echo $productData['image']; ?>" alt="<?php echo $productData['name']; ?>" class="" style="object-fit:contain; width:600px; height:400px;">
                        </div>
                        <!-- Add more carousel items if you have multiple images -->
                    </div>
                    
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselIndicator" data-bs-slide="prev">
                        <div class="d-flex align-items-center position-absolute top-0" style="width: auto; height: 400px;">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </div>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselIndicator" data-bs-slide="next">
                        <div class="d-flex align-items-center position-absolute top-0" style="width: auto; height: 400px;">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </div>
                    </button>
                    
                </div>

                <div class="container-fluid">
                    
                    <div class="d-flex align-items-end gap-2 mb-2">
                        <h1><?php echo $productData['name']; ?></h1>  
                        <h6 class="text-body-secondary pb-1"><?php echo $productData['category']; ?></h6>  
                    </div>
                    
                    <div class="gap-2">
                        <div class="d-flex">
                            <h5 class="pe-2">5</h5>
                            <i class="bi bi-star-fill text-warning border-end pe-2"></i>

                            <h5 class="text-primary ps-2 pe-2">20</h5>
                            <h5 class="border-end pe-2">Ratings</h5>

                            <h5 class="text-success ps-2 pe-2">21</h5>
                            <h5 class="pe-2">Rentals</h5>
                        </div>

                        <div class="d-flex gap-2 mt-5 mb-2 align-items-center">
                            <h6 class="text-body-secondary" style="margin-right: 20px;">Condition</h6>  
                            <div class="d-flex align-items-center border rounded border-success px-2 py-1 text-success">
                                <p class="mb-0 border-success"><?php echo $productData['status']; ?></p>
                            </div>                        
                        </div>

                        <div class="d-flex gap-2 mt-2 mb-2 align-items-center">
                            <h6 class="text-body-secondary" style="margin-right: 20px;">Description</h6>  
                            <div class="d-flex align-items-center border rounded border-success px-2 py-1 text-success">
                                <p class="mb-0"><?php echo $productData['description']; ?></p>
                            </div>                        
                        </div>

                        <!-- Rental Dates Selection -->
                        <div class="d-flex mb-4">
                            <h6 class="text-body-secondary" style="margin-right: 70px;">Set a Date</h6>  
                            <div class="d-flex">
                                <input class="border border-success border-1 rounded-start px-2 text-success" type="text" id="startDate" placeholder="Start Date" required>
                                <input class="border border-success border-1 rounded-end px-2 text-success" type="text" id="endDate" placeholder="End Date" required>
                            </div>
                            
                        </div>

                        <div class="d-flex justify-content-between">
                            <div class="d-flex gap-2">
                                <img src="../images/pfp.png" class="border rounded-circle object-fit-fill" alt="pfp" height="40px" width="40px">
                            </div>
                            <div class="d-flex gap-3 mb-4">
                                <!-- Add to Cart Form -->
                                <form method="post" action="">
                                    <input type="hidden" name="add_to_cart" value="1">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="px-3 py-2 btn rounded-pill shadow-sm btn-light px-3 border ms-auto">
                                        <i class="bi bi-bag-plus pe-1"></i>
                                        Add to Cart
                                    </button>
                                </form>

                                <!-- Direct Checkout Form -->
                                <form method="post" action="checkout.php" class="d-inline">
                                    <!-- CSRF Token -->
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                    <!-- Direct Checkout Indicator -->
                                    <input type="hidden" name="direct_checkout" value="1">
                                    <!-- Product Details -->
                                    <input type="hidden" name="product_id" value="<?= $productData['id']; ?>">
                                    <input type="hidden" name="start_date" id="checkout_start_date" value="">
                                    <input type="hidden" name="end_date" id="checkout_end_date" value="">
                                    <button type="submit" class="px-3 py-2 btn rounded-pill shadow-sm btn-success d-flex align-items-center gap-2" <?php echo ($productData['quantity'] < 1) ? 'disabled' : ''; ?>>
                                        Checkout
                                        <span class="mb-0 ps-1 fw-bold" id="checkoutTotalPrice">₱<?php echo $productData['rental_price']; ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
            <!-- Chat Button -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#chatModal">
                <i class="bi bi-chat-dots-fill"></i> Chat with Owner
            </button>

            <!-- Chat Modal -->
            <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Chat with <?= htmlspecialchars($productData['owner_id']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="chatBox" style="height: 400px; overflow-y: scroll; border: 1px solid #ddd; padding: 10px;">
                                <!-- Messages will be loaded here -->
                            </div>
                            <form id="chatForm" class="mt-3">
                                <div class="input-group">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="product_id" value="<?= $productId; ?>">
                                    <input type="hidden" name="recipient_id" value="<?= htmlspecialchars($productData['owner_id']); ?>">
                                    <input type="text" class="form-control" id="messageInput" name="message" placeholder="Type your message..." required>
                                    <button class="btn btn-success" type="submit">Send</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat AJAX Script -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const chatModal = document.getElementById('chatModal');
                const chatBox = document.getElementById('chatBox');
                const chatForm = document.getElementById('chatForm');
                const messageInput = document.getElementById('messageInput');

                // Function to load messages
                function loadMessages() {
                    const formData = new FormData();
                    formData.append('action', 'fetch_messages');
                    formData.append('product_id', '<?= $productId; ?>');
                    formData.append('csrf_token', '<?= $_SESSION['csrf_token']; ?>');

                    fetch('../chat_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success){
                            chatBox.innerHTML = '';
                            data.messages.forEach(msg => {
                                const messageElement = document.createElement('div');
                                messageElement.classList.add('mb-2');
                                messageElement.innerHTML = `
                                    <strong>${msg.sender_name}:</strong> ${msg.message} 
                                    <small class="text-muted">${msg.created_at}</small>
                                `;
                                chatBox.appendChild(messageElement);
                            });
                            // Scroll to bottom
                            chatBox.scrollTop = chatBox.scrollHeight;
                        } else {
                            chatBox.innerHTML = '<p class="text-danger">Failed to load messages.</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        chatBox.innerHTML = '<p class="text-danger">An error occurred while loading messages.</p>';
                    });
                }

                // Load messages when modal is shown
                chatModal.addEventListener('shown.bs.modal', function () {
                    loadMessages();
                    // Optionally, set up periodic refresh (e.g., every 30 seconds)
                    window.chatInterval = setInterval(loadMessages, 30000);
                });

                // Clear interval when modal is hidden
                chatModal.addEventListener('hidden.bs.modal', function () {
                    clearInterval(window.chatInterval);
                });

                // Handle message submission
                chatForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const message = messageInput.value.trim();
                    if(message === ''){
                        return;
                    }

                    const formData = new FormData(chatForm);
                    formData.append('action', 'send_message');

                    fetch('../chat_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success){
                            messageInput.value = '';
                            loadMessages();
                        } else {
                            alert(data.message || 'Failed to send message.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while sending the message.');
                    });
                });
            });
            </script>
                </main>
            </div>
        </div>

        <!-- Comment Section -->
        <div class="bg-body-secondary p-4">
            <main class="bg-body mx-3 p-5 rounded-5">
                <h3 class="mb-4">Comments & Ratings</h3>

                <?php if ($totalComments > 0): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="author"><?= htmlspecialchars($comment['renter_name']) ?></span>
                                <span class="date"><?= date('F j, Y, g:i a', strtotime($comment['created_at'])) ?></span>
                            </div>
                            <div class="rating mt-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $comment['rating']): ?>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star text-warning"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <nav aria-label="Comments pagination">
                        <ul class="pagination">
                            <!-- Previous Button -->
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?id=<?= $productId ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?id=<?= $productId ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?id=<?= $productId ?>&page=<?= $page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php else: ?>
                    <p>No comments yet. Be the first to comment!</p>
                <?php endif; ?>
            </main>
        </div>

        <footer>
            <div class="d-flex flex-column flex-sm-row justify-content-between py-2 border-top">
                <p class="ps-3">© 2024 Rentbox. All rights reserved.</p>
                <ul class="list-unstyled d-flex pe-3">
                    <li class="ms-3"><a href=""><i class="bi bi-facebook text-body"></i></a></li>
                    <li class="ms-3"><a href=""><i class="bi bi-twitter text-body"></i></a></li>
                    <li class="ms-3"><a href=""><i class="bi bi-linkedin text-body"></i></a></li>
                </ul>
            </div>
        </footer>

        <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../vendor/flatpickr.min.js"></script>
        <script>
        // Initialize flatpickr
        flatpickr("#startDate", {
            dateFormat: "Y-m-d", 
            maxDate: new Date(2025, 11, 1), 
            minDate: "today",     
            disableMobile: true,
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('checkout_start_date').value = dateStr;
                calculateTotal();
            }
        });

        flatpickr("#endDate", {
            dateFormat: "Y-m-d", 
            maxDate: new Date(2025, 11, 1), 
            minDate: "today",     
            disableMobile: true,
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('checkout_end_date').value = dateStr;
                calculateTotal();
            }
        });

        // Calculate total rental price based on selected dates
        function calculateTotal() {
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            const totalPriceDisplay = document.getElementById('checkoutTotalPrice');
            
            const pricePerPeriod = <?php echo floatval($product['rental_price']); ?>; // PHP price
            const rentalPeriod = "<?php echo strtolower($product['rental_period']); ?>"; // e.g., 'day', 'week', 'month'
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            // Validate if both dates are selected and startDate is before or equal to endDate
            if (startDateInput.value && endDateInput.value && startDate <= endDate) {
                const timeDifference = endDate - startDate; // Milliseconds difference
                const daysDifference = Math.ceil(timeDifference / (1000 * 3600 * 24)) + 1; // Convert to days (+1 for inclusive day)
                
                let periods = 1;
                switch (rentalPeriod) {
                    case 'day':
                        periods = daysDifference;
                        break;
                    case 'week':
                        periods = Math.ceil(daysDifference / 7);
                        break;
                    case 'month':
                        periods = Math.ceil(daysDifference / 30);
                        break;
                    default:
                        periods = 1;
                }

                const totalPrice = periods * pricePerPeriod; // Total cost calculation
                totalPriceDisplay.textContent = '₱' + totalPrice.toFixed(2); // Update display
            } else {
                totalPriceDisplay.textContent = '₱' + pricePerPeriod.toFixed(2); // Default price per period
            }
        }

        // Initialize total price on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
        });
        </script>
</body>
</html>