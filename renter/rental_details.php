<?php
error_log("DEBUG: Current rental status: " . ($rental['status'] ?? 'unknown'));
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
    $owner_delivery_proofs = [];
    $renter_delivery_proofs = [];
    $return_proofs = [];
    
    foreach ($allProofs as $proof) {
        if ($proof['proof_type'] === 'delivery') {
            // Owner's delivery proof (On Delivery)
            $owner_delivery_proofs[] = $proof;
        } elseif ($proof['proof_type'] === 'delivered') {
            // Renter's delivery confirmation (Delivered)
            $renter_delivery_proofs[] = $proof;
        } elseif ($proof['proof_type'] === 'return') {
            $return_proofs[] = $proof;
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error retrieving rental details";
    header("Location: rentals.php");
    exit();
}

// Handle overdue status update
function checkOverdueStatus($rentalId, $currentStatus, $endDate, $conn) {
    if (in_array($currentStatus, ['renting', 'delivered']) && date('Y-m-d') > $endDate) {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("UPDATE rentals SET status = 'overdue' WHERE id = ?");
            $stmt->execute([$rentalId]);
            $conn->commit();
            return 'overdue';
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Overdue update error: " . $e->getMessage());
        }
    }
    return $currentStatus;
}

// Overdue check
$currentStatus = checkOverdueStatus($rentalId, $rental['status'], $rental['end_date'], $conn);

// CSRF token setup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// File upload handler function
function handleFileUpload($file, $proofType, $conn, $rentalId) {
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

// Modify the status update logic
function handleProofAndStatusUpdate($proofType, $rentalId, $file, $currentStatus, $conn, $rental) {
    try {
        $conn->beginTransaction();
        
        $filePath = handleFileUpload($file, $proofType, $conn, $rentalId);
        
        // Insert proof record
        $conn->prepare("
            INSERT INTO proofs (rental_id, proof_type, proof_url)
            VALUES (?, ?, ?)
        ")->execute([$rentalId, $proofType, $filePath]);

        // Update rental status based on proof type
        if ($proofType === 'delivered') {
            $statusUpdateQuery = "UPDATE rentals SET status = 'delivered' WHERE id = ?";
        } elseif ($proofType === 'return') {
            $statusUpdateQuery = "UPDATE rentals SET status = 'returned' WHERE id = ?";
        } else {
            // Remove this else clause to prevent unintended status changes
            throw new Exception("Invalid proof type");
        }
        
        $conn->prepare($statusUpdateQuery)->execute([$rentalId]);
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error handling proof: " . $e->getMessage());
        return false;
    }
}

// Processing various actions like "confirm rent", "confirm end rental", and "submit feedback"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For example, handle delivery proof
// Change from 'delivery' to 'delivered' for renter's confirmation
if (isset($_FILES['proof_of_delivered']) && $currentStatus === 'delivery_in_progress') {
    if (!handleProofAndStatusUpdate('delivered', $rentalId, $_FILES['proof_of_delivered'], $currentStatus, $conn, $rental)) {
        $_SESSION['error'] = "Error uploading delivery confirmation.";
    }
}
   // End rental confirmation
    if (isset($_POST['confirm_end_rental']) && $currentStatus === 'renting') {
        try {
            $conn->beginTransaction();
            
            // Update rental status to 'completed'
            $conn->prepare("
                UPDATE rentals 
                SET status = 'completed', actual_end_date = CURDATE()
                WHERE id = ?
            ")->execute([$rentalId]);
            
            $conn->commit();
            $_SESSION['success'] = "Rental ended successfully. Please return the item.";
            header("Location: rental_details.php?rental_id=$rentalId");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error ending rental: " . $e->getMessage();
        }
    }

    // Handle item return confirmation
    if (isset($_POST['confirm_return'])) {
        try {
            $conn->beginTransaction();
            
            // Update rental status to 'returned'
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
}
try {
    // Check product feedback
    $feedbackCheck = $conn->prepare("
        SELECT * FROM comments 
        WHERE product_id = ? 
        AND renter_id = ?
    ");
    $feedbackCheck->execute([$rental['product_id'], $renterId]);
    $hasFeedback = $feedbackCheck->fetch();

    // ✅ Add owner review check here
    $checkOwnerReview = $conn->prepare("SELECT * FROM owner_reviews WHERE rental_id = ? AND renter_id = ?");
    $checkOwnerReview->execute([$rentalId, $renterId]);
    $hasOwnerReview = $checkOwnerReview->fetch();

} catch (PDOException $e) {
    error_log("Feedback check error: " . $e->getMessage());
    $hasFeedback = false;
    $hasOwnerReview = false; // ✅ Initialize this variable
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Add matching styles from owner side */
        .progress-container {
            position: relative;
            display: flex;
            justify-content: space-between;
            margin: 40px 0 60px;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #dee2e6;
            z-index: 0;
        }

        .progress-step {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
            width: 100%;
        }

        .progress-step .circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #fff;
            border: 3px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .progress-step.active .circle {
            border-color: #0d6efd;
            background-color: #0d6efd;
            color: white;
        }

        .progress-step .label {
            font-size: 0.9rem;
            color: #6c757d;
            white-space: nowrap;
            position: absolute;
            top: 50px;
            width: 120px;
            text-align: center;
        }

        .proof-links {
            position: absolute;
            top: 80px;
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbarr.php'; ?>

    <main>
        <div class="card">
            <div class="card-header">Rental Details</div>
            <div class="card-body">
                <!-- Alerts -->
                <div class="alert-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</div>

                <!-- Rental Info -->
                <h5 class="card-title">Rental ID: <?= htmlspecialchars($rental['id']) ?></h5>
                <p class="card-text"><strong>Rental Date:</strong> <?= htmlspecialchars($rental['created_at'] ?? 'N/A') ?></p>
                <p class="card-text"><strong>Meet-up Date:</strong> <?= htmlspecialchars($rental['start_date'] ?? 'N/A') ?></p>

                <!-- Progress Steps -->
                <div class="progress-container">
                    <div class="progress-line"></div>
                    <?php foreach ($statusFlow as $key => $label): ?>
    <div class="progress-step <?= isStatusActive($key, $currentStatus, $statusFlow) ? 'active' : '' ?>">
        <div class="circle"><?= $key === $currentStatus ? "✔" : "" ?></div>
        <div class="label">
            <?= htmlspecialchars($label) ?>
            
            <!-- Owner's Delivery Proofs (On Delivery) -->
            <?php if ($key === 'delivery_in_progress' && !empty($owner_delivery_proofs)): ?>
                <div class="mt-2">
                    <?php foreach ($owner_delivery_proofs as $proof): ?>
                        <a href="#" class="d-block" 
                           data-bs-toggle="modal" 
                           data-bs-target="#proofModal"
                           data-bs-url="<?= htmlspecialchars($proof['proof_url']) ?>"
                           data-bs-type="delivery"
                           data-bs-date="<?= htmlspecialchars(date('F j, Y', strtotime($proof['created_at']))) ?>">
                           View
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Renter's Delivery Confirmation (Delivered) -->
            <?php if ($key === 'delivered' && !empty($renter_delivery_proofs)): ?>
                <div class="mt-2">
                    <?php foreach ($renter_delivery_proofs as $proof): ?>
                        <a href="#" class="d-block" 
                           data-bs-toggle="modal" 
                           data-bs-target="#proofModal"
                           data-bs-url="<?= htmlspecialchars($proof['proof_url']) ?>"
                           data-bs-type="delivered"
                           data-bs-date="<?= htmlspecialchars(date('F j, Y', strtotime($proof['created_at']))) ?>">
                           View
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Return Proofs -->
            <?php if ($key === 'returned' && !empty($return_proofs)): ?>
                <div class="mt-2">
                    <?php foreach ($return_proofs as $proof): ?>
                        <a href="#" class="d-block" 
                           data-bs-toggle="modal" 
                           data-bs-target="#proofModal"
                           data-bs-url="<?= htmlspecialchars($proof['proof_url']) ?>"
                           data-bs-type="return"
                           data-bs-date="<?= htmlspecialchars(date('F j, Y', strtotime($proof['created_at']))) ?>">
                           View Return Proof
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
                </div>

                <!-- Rental Summary -->
                <div class="rental-summary d-flex align-items-center mt-4">
                    <img src="../img/uploads/<?= htmlspecialchars($rental['image']) ?>" 
                         alt="<?= htmlspecialchars($rental['product_name']) ?>" 
                         class="img-thumbnail" 
                         style="width: 150px; height: auto; object-fit: cover;">
                    <div class="ms-3">
                        <h5><?= htmlspecialchars($rental['product_name']) ?></h5>
                        <p>Brand: <?= htmlspecialchars($rental['brand']) ?></p>
                        <p><strong>₱<?= number_format($rental['rental_price'], 2) ?></strong> / <?= htmlspecialchars($rental['rental_period']) ?></p>
                    </div>
                </div>          

                <?php if ($currentStatus === 'delivery_in_progress'): ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="mb-3">
            <label class="form-label">Upload Delivery Confirmation</label>
            <input type="file" class="form-control" name="proof_of_delivered" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload Confirmation</button>
    </form>
<?php endif; ?>
                    <?php if ($currentStatus === 'delivered'): ?>
                        <form method="post" class="me-2 mb-2">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                            <button type="submit" name="confirm_rent" class="btn btn-success">Start Rent</button>
                        </form>
                    <?php endif; ?>



                    <div class="rental-actions mt-4">
                    <?php if (!$hasOwnerReview): ?>
                    <?php if ($currentStatus === 'renting'): ?>
                         <form method="post" class="end-rent-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" name="confirm_end_rental" class="btn btn-warning btn-lg">
                    <i class="bi bi-box-arrow-left"></i> End Rental
                  </button>
                    </form>
                      <?php endif; ?>
        
                     <?php if ($currentStatus === 'completed'): ?>
                       <button type="button" class="btn btn-danger btn-lg mb-3" data-bs-toggle="modal" data-bs-target="#endRentalModal">
                <i class="bi bi-box-arrow-left"></i> Return Item Now
            </button>
        <?php endif; ?>
    <?php endif; ?>
</div>

                        <div class="modal fade" id="endRentalModal" tabindex="-1" aria-labelledby="endRentalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="endRentalModalLabel">Provide Feedback Before Returning the Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="returnItemForm" method="post" enctype="multipart/form-data">
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

                </div>
            </div>
        </div>

        <div class="modal fade" id="proofModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <div class="modal-date ms-auto pe-3">
                    <span id="proofDate" class="text-muted"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="proofImage" src="" alt="Proof" class="img-fluid rounded" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>
    </main>
    
    <script>
    // Form validation for return item
    document.getElementById('returnItemForm').addEventListener('submit', function (event) {
        // Get all the form inputs
        const productRating = document.getElementById('product_rating').value;
        const productComment = document.getElementById('product_comment').value;
        const ownerRating = document.getElementById('owner_rating').value;
        const ownerComment = document.getElementById('owner_comment').value;
        const proofOfReturn = document.getElementById('proof_of_returned').files.length;

        // Check if all fields are filled
        if (!productRating || !productComment || !ownerRating || !ownerComment || proofOfReturn === 0) {
            event.preventDefault(); // Prevent form submission
            alert("All fields are required before submitting the form.");
        }
    }); // Fixed closing parenthesis

    // Proof modal handler
    const proofModal = document.getElementById('proofModal');
    proofModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const proofUrl = button.getAttribute('data-bs-url');
        const proofType = button.getAttribute('data-bs-type');
        const proofDate = button.getAttribute('data-bs-date');

        const titleMap = {
            'delivery': 'Proof of Delivery',
            'return': 'Proof of Return',
            'delivered': 'Proof of Delivery Completion'
        };

        proofModal.querySelector('.modal-title').textContent = titleMap[proofType] || 'Proof';
        proofModal.querySelector('#proofDate').textContent = proofDate;
        proofModal.querySelector('#proofImage').src = proofUrl;
    });
</script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>