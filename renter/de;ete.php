<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/db.php';

// Authentication check
if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit();
}

$renterId = $_SESSION['id'];
$rentalId = filter_input(INPUT_GET, 'rental_id', FILTER_VALIDATE_INT);

if (!$rentalId) {
    $_SESSION['error'] = "Invalid rental ID";
    header('Location: rentals.php');
    exit();
}

// Fetch rental details
try {
    $stmt = $conn->prepare("
        SELECT r.*, p.name AS product_name, p.brand, p.image, 
               p.rental_period, p.rental_price, u.name AS owner_name
        FROM rentals r
        INNER JOIN products p ON r.product_id = p.id
        INNER JOIN users u ON r.owner_id = u.id
        WHERE r.id = ? AND r.renter_id = ?
    ");
    $stmt->execute([$rentalId, $renterId]);
    $rental = $stmt->fetch();

    if (!$rental) {
        $_SESSION['error'] = "Rental not found";
        header('Location: rentals.php');
        exit();
    }

    $proofStmt = $conn->prepare("
    SELECT * FROM proofs 
    WHERE rental_id = ? 
    ORDER BY created_at
");
$proofStmt->execute([$rentalId]);
$allProofs = $proofStmt->fetchAll();

// Organize proofs
$deliveryProofs = [];
$returnProofs = [];
foreach ($allProofs as $proof) {
    if ($proof['proof_type'] === 'delivery') {
        $deliveryProofs[] = $proof;
    } elseif ($proof['proof_type'] === 'return') {
        $returnProofs[] = $proof;
    }
}

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error retrieving rental details";
    header("Location: rentals.php");
    exit();
}

// Handle status updates
$currentStatus = $rental['status'];
$isOverdue = false;

// Overdue check
if (in_array($currentStatus, ['renting', 'delivered']) && 
    date('Y-m-d') > $rental['end_date']) {
    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("UPDATE rentals SET status = 'overdue' WHERE id = ?");
        $stmt->execute([$rentalId]);
        $conn->commit();
        $currentStatus = 'overdue';
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Overdue update error: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// File upload handler function
function handleFileUpload($file, $proofType, $conn, $rentalId, $rental) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error");
    }

    if ($file['size'] > $maxSize) {
        throw new Exception("File size exceeds 2MB limit");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception("Invalid file type. Allowed: JPG, PNG, GIF");
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid("proof_{$proofType}_") . '.' . $ext;
    $uploadPath = "../img/proofs/$filename";

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception("Failed to save uploaded file");
    }

    return $uploadPath;
}
// Handle POST requests



    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: rental_details.php?rental_id=$rentalId");
        exit();
    }


    // File upload handler
    $handleFileUpload = function($file, $proofType) use ($conn, $rentalId, $rental) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error");
        }

        if ($file['size'] > $maxSize) {
            throw new Exception("File size exceeds 2MB limit");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Invalid file type. Allowed: JPG, PNG, GIF");
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid("proof_{$proofType}_") . '.' . $ext;
        $uploadPath = "../img/proofs/$filename";

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception("Failed to save uploaded file");
        }

        return $uploadPath;
    };

    // Handle proof of delivery
    if (isset($_FILES['proof_of_delivered']) && $currentStatus === 'delivery_in_progress') {
        try {
            $conn->beginTransaction();
            
            $filePath = $handleFileUpload($_FILES['proof_of_delivered'], 'delivery');
            
            // Insert proof record
            $conn->prepare("
            INSERT INTO proofs (rental_id, proof_type, proof_url)
            VALUES (?, 'delivery', ?)
        ")->execute([$rentalId, $filePath]);
        
        // Update rental status to 'delivered'
        $conn->prepare("
            UPDATE rentals 
            SET status = 'delivered', updated_at = NOW()
            WHERE id = ?
        ")->execute([$rentalId]);
        
        $conn->commit();
        $_SESSION['success'] = "Delivery proof uploaded successfully";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

    // Handle proof of return


    // Handle rent start confirmation
    if (isset($_POST['confirm_rent']) && $currentStatus === 'delivered') {
        try {
            $periodMap = [
                'day' => '+1 day',
                'week' => '+1 week',
                'month' => '+1 month'
            ];
            
            $interval = $periodMap[strtolower($rental['rental_period'])] ?? '+1 day';
            $endDate = date('Y-m-d', strtotime($interval));

            $conn->prepare("
                UPDATE rentals 
                SET status = 'renting', 
                    start_date = CURDATE(),
                    end_date = ?,
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([$endDate, $rentalId]);
            
            $_SESSION['success'] = "Rental period started successfully";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error starting rental: " . $e->getMessage();
        }
    }
    if (isset($_POST['confirm_end_rental'])) {
        try {
            $conn->beginTransaction();
            
            $conn->prepare("
                UPDATE rentals 
                SET status = 'completed', actual_end_date = CURDATE()
                WHERE id = ?
            ")->execute([$rentalId]);
            
            $conn->commit();
            $_SESSION['success'] = "Rental ended successfully. Please return the item.";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error ending rental: " . $e->getMessage();
        }
    }


    if (isset($_POST['confirm_return'])) {
        try {
            $conn->beginTransaction();
            
            // Update rental status
            $conn->prepare("
                UPDATE rentals 
                SET status = 'returned', actual_end_date = CURDATE()
                WHERE id = ?
            ")->execute([$rentalId]);
            
            // Update product quantity
            $conn->prepare("
                UPDATE products 
                SET quantity = quantity + 1 
                WHERE id = ?
            ")->execute([$rental['product_id']]);
            
            $conn->commit();
            $_SESSION['success'] = "Item returned successfully!";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Return failed: " . $e->getMessage();
        }
    }


    // Handle feedback submission for both product and owner
// Handle feedback submission for both product and owner
if (isset($_POST['submit_feedback'])) {
    $productRating = filter_input(INPUT_POST, 'product_rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    $productComment = filter_input(INPUT_POST, 'product_comment', FILTER_SANITIZE_SPECIAL_CHARS);
    $ownerRating = filter_input(INPUT_POST, 'owner_rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    $ownerComment = filter_input(INPUT_POST, 'owner_comment', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$productRating || !$productComment || !$ownerRating || !$ownerComment) {
        $_SESSION['error'] = "All fields are required";
        header("Location: rental_details.php?rental_id=$rentalId");
        exit();
    }

    try {
        $conn->beginTransaction();

        // Insert product review into comments
        $conn->prepare("
            INSERT INTO comments (product_id, renter_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ")->execute([
            $rental['product_id'],
            $renterId,
            $productRating,
            $productComment
        ]);

        // Insert owner review into owner_reviews
        $conn->prepare("
            INSERT INTO owner_reviews (owner_id, renter_id, rental_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ")->execute([
            $rental['owner_id'],
            $renterId,
            $rentalId,
            $ownerRating,
            $ownerComment
        ]);

        // Handle proof upload
        if (isset($_FILES['proof_of_returned']) && $_FILES['proof_of_returned']['error'] === UPLOAD_ERR_OK) {
            $filePath = handleFileUpload($_FILES['proof_of_returned'], 'return', $conn, $rentalId, $rental);

            // Insert proof of return record
            $conn->prepare("INSERT INTO proofs (rental_id, proof_type, proof_url) VALUES (?, 'return', ?)")
                ->execute([$rentalId, $filePath]);

            // Update the rental status to 'returned'
            $conn->prepare("UPDATE rentals SET status = 'returned', actual_end_date = CURDATE(), updated_at = NOW() WHERE id = ?")
                ->execute([$rentalId]);
        }

        $conn->commit();
        $_SESSION['success'] = "Feedback and proof submitted successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error submitting feedback: " . $e->getMessage();
    }

    header("Location: rental_details.php?rental_id=$rentalId");
    exit();
}

    header("Location: rental_details.php?rental_id=$rentalId");
    exit();
}

try {
    $feedbackCheck = $conn->prepare("
        SELECT * FROM comments 
        WHERE product_id = ? 
        AND renter_id = ?
    ");
    $feedbackCheck->execute([$rental['product_id'], $renterId]);
    $hasFeedback = $feedbackCheck->fetch();
} catch (PDOException $e) {
    error_log("Feedback check error: " . $e->getMessage());
    $hasFeedback = false;
}

$statusFlow = [
    'pending_confirmation' => 'Pending',
    'approved' => 'Confirmed',
    'delivery_in_progress' => 'On Delivery',
    'delivered' => 'Delivered',
    'renting' => 'Renting',
    'completed' => 'Completed',
    'returned' => 'Returned',
    'overdue' => 'Overdue'
];

// Filter status flow based on current state
if ($currentStatus === 'cancelled') {
    $statusFlow = array_intersect_key($statusFlow, array_flip(['pending_confirmation', 'cancelled']));
}

// Helper function for status display
function isStatusActive($statusKey, $currentStatus, $statusFlow) {
    $statuses = array_keys($statusFlow);
    $currentIndex = array_search($currentStatus, $statuses);
    $targetIndex = array_search($statusKey, $statuses);
    
    return $targetIndex !== false && $targetIndex <= $currentIndex;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/renter/rental_details.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <!-- Alert Messages -->
    <div class="alert-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>

    <main>
        <div class="card centered-card">
            <div class="card-header">Rental Details</div>
            <div class="card-body">

                <h5 class="card-title">Rental ID: <?= htmlspecialchars($rental['id']) ?></h5>
                <p class="card-text"><strong>Rental Date:</strong> <?= htmlspecialchars($rental['created_at'] ?? 'N/A') ?></p>
                <p class="card-text"><strong>Meet-up Date:</strong> <?= htmlspecialchars($rental['start_date'] ?? 'N/A') ?></p>

                <!-- Progress Steps -->
                <div class="progress-container">
                    <div class="progress-line"></div>
                    <?php foreach ($statusFlow as $key => $label): ?>
                        <div class="progress-step <?= isStatusActive($key, $currentStatus, $statusFlow) ? 'active' : '' ?>">
                            <div class="circle"><?= $key === $currentStatus ? "✔" : "" ?></div>
                            <div class="label"><?= htmlspecialchars($label) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Rental Summary -->
                <div class="rental-summary d-flex align-items-center mt-4">
                    <img src="../img/uploads/<?= htmlspecialchars($rental['image']) ?>" alt="<?= htmlspecialchars($rental['product_name']) ?>" style="width: 150px; height: auto; object-fit: cover;">
                    <div class="ms-3">
                        <h5><?= htmlspecialchars($rental['product_name']) ?></h5>
                        <p>Brand: <?= htmlspecialchars($rental['brand']) ?></p>
                        <p><strong>₱<?= number_format($rental['rental_price'], 2) ?></strong> / <?= htmlspecialchars($rental['rental_period']) ?></p>
                    </div>
                </div>

                <!-- Proof Section -->
                <div class="mt-4">
                    <h6>Proof of Delivery from Owner:</h6>
                    <?php if (!empty($deliveryProofs[0])): ?>
                        <img src="<?= htmlspecialchars($deliveryProofs[0]['proof_url']) ?>" 
                             alt="Owner's Delivery Proof" 
                             class="img-thumbnail" 
                             width="200">
                    <?php else: ?>
                        <p>No proof uploaded by owner yet.</p>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <h6>Your Proof of Delivery:</h6>
                    <?php if (!empty($deliveryProofs[1])): ?>
                        <img src="<?= htmlspecialchars($deliveryProofs[1]['proof_url']) ?>" 
                             alt="Your Delivery Proof" 
                             class="img-thumbnail" 
                             width="200">
                    <?php else: ?>
                        <?php if ($currentStatus === 'delivery_in_progress'): ?>
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Upload Your Delivery Confirmation</label>
                                    <input type="file" class="form-control" name="proof_of_delivered" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Upload Proof</button>
                            </form>
                        <?php else: ?>
                            <p>No proof uploaded yet.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-wrap mt-3">
                    <?php if ($currentStatus === 'delivery_in_progress'): ?>
                        <form method="post" enctype="multipart/form-data" class="me-2 mb-2">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                            <div class="mb-3">
                                <label for="proof_of_delivered" class="form-label">Upload Proof of Delivered</label>
                                <input class="form-control" type="file" id="proof_of_delivered" name="proof_of_delivered" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload Proof</button>
                        </form>
                    <?php elseif ($currentStatus === 'delivered'): ?>
                        <form method="post" class="me-2 mb-2">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                            <button type="submit" name="confirm_rent" class="btn btn-success">Start Rent</button>
                        </form>
    <?php
    // Check if feedback already submitted for this rental
    $checkProductReview = $conn->prepare("SELECT * FROM comments WHERE product_id = ? AND renter_id = ?");
    $checkProductReview->execute([$rental['product_id'], $renterId]);
    $hasProductReview = $checkProductReview->fetch();

    $checkOwnerReview = $conn->prepare("SELECT * FROM owner_reviews WHERE rental_id = ? AND renter_id = ?");
    $checkOwnerReview->execute([$rentalId, $renterId]);
    $hasOwnerReview = $checkOwnerReview->fetch();
    ?>

    <?php if (!$hasProductReview && !$hasOwnerReview): ?>
        <?php var_dump($currentStatus); ?>
        <?php if ($currentStatus === 'renting'): ?>
                    <button type="button" class="btn btn-warning mb-3" data-bs-toggle="modal" data-bs-target="#endRentalModal">
                        End Rental
                    </button>
                <?php endif; ?>
                <div class="modal fade" id="endRentalModal" tabindex="-1" aria-labelledby="endRentalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="endRentalModalLabel">Provide Feedback Before Ending Rental</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                    <!-- Product Feedback -->
                    <h6>Product Feedback</h6>
                    <div class="mb-3">
                        <label for="product_rating" class="form-label">Product Rating (1-5)</label>
                        <select class="form-select" id="product_rating" name="product_rating" required>
                            <option value="" selected disabled>Select rating</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="product_comment" class="form-label">Product Comment</label>
                        <textarea class="form-control" id="product_comment" name="product_comment" rows="3" required></textarea>
                    </div>

                    <!-- Owner Feedback -->
                    <h6>Owner Feedback</h6>
                    <div class="mb-3">
                        <label for="owner_rating" class="form-label">Owner Rating (1-5)</label>
                        <select class="form-select" id="owner_rating" name="owner_rating" required>
                            <option value="" selected disabled>Select rating</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="owner_comment" class="form-label">Owner Comment</label>
                        <textarea class="form-control" id="owner_comment" name="owner_comment" rows="3" required></textarea>
                    </div>

                    <!-- Proof of Return Upload -->
                    <div class="mb-3">
                        <label for="proof_of_returned" class="form-label">Upload Proof of Returned</label>
                        <input class="form-control" type="file" id="proof_of_returned" name="proof_of_returned" required>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback and End Rental</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>