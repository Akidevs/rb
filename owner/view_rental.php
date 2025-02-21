<?php
// owner/view_rental.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
require_once '../db/db.php';

// Check if owner is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../owner/login.php');
    exit();
}

$ownerId = $_SESSION['id'];

// Get rental ID from query parameters
if (!isset($_GET['rental_id'])) {
    header('Location: rentals.php'); // Redirect back if rental_id is not provided
    exit();
}

$rentalId = intval($_GET['rental_id']);



// Fetch rental details including brand and rental_period
$sql = "SELECT r.*, p.name AS product_name, p.brand, p.rental_period, u.name AS renter_name, 
        p.quantity AS product_quantity, p.image AS product_image
        FROM rentals r
        INNER JOIN products p ON r.product_id = p.id
        INNER JOIN users u ON r.renter_id = u.id
        WHERE r.id = :rentalId AND r.owner_id = :ownerId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
$stmt->bindParam(':ownerId', $ownerId, PDO::PARAM_INT);
$stmt->execute();

$rental = $stmt->fetch();

if (!$rental) {
    $_SESSION['error'] = "Rental not found.";
    header('Location: rentals.php');
    exit();
}
$currentStatus = $rental['status'];
$checkOwnerReview = $conn->prepare("SELECT * FROM renter_reviews WHERE rental_id = ? AND owner_id = ?");
$checkOwnerReview->execute([$rentalId, $ownerId]);
$hasOwnerReview = $checkOwnerReview->fetch();
// Define the status flow
$statusFlow = [
    'pending_confirmation' => 'Pending',
    'approved' => 'Approved',
    'delivery_in_progress' => 'On Delivery',
    'delivered' => 'Delivered',
    'renting' => 'Renting',
    'completed' => 'Completed',
    'returned' => 'Returned',
    'cancelled' => 'Cancelled',
    'overdue' => 'Overdue'
];

$filteredStatusFlow = array_filter($statusFlow, function ($key) use ($currentStatus) {
    // If the status is overdue, remove "completed", "returned", and "cancelled"
    if ($currentStatus === 'overdue') {
        return !in_array($key, ['completed', 'returned', 'cancelled']);
    }

    // If the status is renting, we should skip cancelled and overdue
    if ($currentStatus === 'renting') {
        return $key !== 'cancelled' && $key !== 'overdue';
    }

    // Otherwise, include all statuses except for cancelled and overdue
    $specialStatuses = ['cancelled', 'overdue'];
    return !in_array($key, $specialStatuses);
}, ARRAY_FILTER_USE_KEY);

// Helper function to determine if a status should be active
function isStatusActive($statusKey, $currentStatus, $statusFlow) {
    $keys = array_keys($statusFlow);
    $currentIndex = array_search($currentStatus, $keys);
    $statusIndex = array_search($statusKey, $keys);

    if ($statusIndex === false) {
        return false;
    }

    return $statusIndex <= $currentIndex;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: view_rental.php?rental_id=$rentalId");
        exit();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        try {
            $conn->beginTransaction();
            
            // Update rental status
            $stmt = $conn->prepare("
                UPDATE rentals 
                SET status = 'approved', updated_at = NOW()
                WHERE id = :rentalId
            ");
            $stmt->execute([':rentalId' => $rentalId]);
    
            // Decrease product quantity
            $stmt = $conn->prepare("
                UPDATE products 
                SET quantity = quantity - 1 
                WHERE id = :productId AND quantity > 0
            ");
            $stmt->execute([':productId' => $rental['product_id']]);
    
            $conn->commit();
            $_SESSION['success'] = "Rental approved successfully";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Approval failed: " . $e->getMessage();
        }
    } elseif ($action === 'cancel') {
        try {
            $conn->beginTransaction();
            
            // Update rental status
            $stmt = $conn->prepare("
                UPDATE rentals 
                SET status = 'cancelled', updated_at = NOW()
                WHERE id = :rentalId
            ");
            $stmt->execute([':rentalId' => $rentalId]);
    
            // Restore quantity if previously approved
            if ($rental['status'] === 'approved') {
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET quantity = quantity + 1 
                    WHERE id = :productId
                ");
                $stmt->execute([':productId' => $rental['product_id']]);
            }
    
            $conn->commit();
            $_SESSION['success'] = "Rental cancelled successfully";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Cancellation failed: " . $e->getMessage();
        }
    } elseif ($action === 'upload_proof') {
        // Owner uploads proof of delivery
        try {
            $conn->beginTransaction();

            // Validate file upload
            $file = $_FILES['proof_file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload error.");
            }

            if ($file['size'] > $maxSize) {
                throw new Exception("File size exceeds 2MB limit.");
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Invalid file type. Allowed: JPG, PNG, GIF.");
            }

            // Save file
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid("proof_delivery_") . '.' . $ext;
            $uploadPath = "../img/proofs/$filename";
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to save file.");
            }

            // Insert proof
            $stmt = $conn->prepare("
                INSERT INTO proofs (rental_id, proof_type, proof_url)
                VALUES (:rentalId, 'delivery', :url)
            ");
            $stmt->execute([
                ':rentalId' => $rentalId,
                ':url' => $uploadPath
            ]);

            // Update rental status
            $stmt = $conn->prepare("
                UPDATE rentals 
                SET status = 'delivery_in_progress', updated_at = NOW()
                WHERE id = :rentalId
            ");
            $stmt->execute([':rentalId' => $rentalId]);

            $conn->commit();
            $_SESSION['success'] = "Proof uploaded. Status: On Delivery.";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'start_renting') {
        // Start rental period
        try {
            $conn->beginTransaction();
            
            $period = strtolower($rental['rental_period']);
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+1 $period", strtotime($startDate)));

            $stmt = $conn->prepare("
                UPDATE rentals 
                SET status = 'renting',
                    start_date = :startDate,
                    end_date = :endDate,
                    updated_at = NOW()
                WHERE id = :rentalId
            ");
            $stmt->execute([
                ':startDate' => $startDate,
                ':endDate' => $endDate,
                ':rentalId' => $rentalId
            ]);

            $conn->commit();
            $_SESSION['success'] = "Rental period started!";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }

        
    } elseif ($action === 'end_rent') {
        // End rental period
        try {
            $conn->beginTransaction();
    
            // Update rental status to completed and set the actual end date
            $stmt = $conn->prepare("
                UPDATE rentals 
                SET status = 'completed', actual_end_date = NOW(), updated_at = NOW()
                WHERE id = :rentalId
            ");
            $stmt->execute([':rentalId' => $rentalId]);
    
            $conn->commit();
            $_SESSION['success'] = "Rental ended successfully!";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        header("Location: view_rental.php?rental_id=$rentalId");
        exit();
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
        $renterRating = filter_input(INPUT_POST, 'renter_rating', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5]
        ]);
        $productRating = filter_input(INPUT_POST, 'product_rating', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5]
        ]);
        $renterComment = filter_input(INPUT_POST, 'renter_comment', FILTER_SANITIZE_SPECIAL_CHARS);
        $productComment = filter_input(INPUT_POST, 'product_comment', FILTER_SANITIZE_SPECIAL_CHARS);
    
        if (!$renterRating || !$productRating || !$renterComment || !$productComment) {
            $_SESSION['error'] = "All fields are required";
            header("Location: view_rental.php?rental_id=$rentalId");
            exit();
        }
    
        try {
            $conn->beginTransaction();
            
            // Insert owner feedback for renter
            $stmt = $conn->prepare("
                INSERT INTO renter_reviews (renter_id, owner_id, rental_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$rental['renter_id'], $ownerId, $rentalId, $renterRating, $renterComment]);
            
            // Insert owner feedback for product
            $stmt = $conn->prepare("
                INSERT INTO renter_reviews (renter_id, owner_id, rental_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$rental['renter_id'], $ownerId, $rentalId, $productRating, $productComment]);
    
            $conn->commit();
            $_SESSION['success'] = "Feedback submitted successfully.";
            header("Location: view_rental.php?rental_id=$rentalId");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Error submitting feedback: " . $e->getMessage();
            header("Location: view_rental.php?rental_id=$rentalId");
            exit();
        }
    }
    
}

// Fetch proofs (update query to include 'delivery')
$sql = "SELECT * FROM proofs WHERE rental_id = :rentalId AND proof_type IN ('delivery', 'delivered', 'return') ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
$stmt->execute();
$proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize proofs by type (both owner and renter)
$proofsByType = [
    'delivery' => [], // Owner's proof
    'return' => [],   // Renter's return proof (if any)
];
foreach ($proofs as $proof) {
    $proofsByType[$proof['proof_type']][] = $proof;
}


// Update current status after actions
$currentStatus = $rental['status'];

// Calculate remaining days if renting
$remainingDays = 'N/A';
if ($currentStatus === 'renting' && !empty($rental['end_date'])) {
    $today = new DateTime();
    $endDate = new DateTime($rental['end_date']);
    $interval = $today->diff($endDate);
    $days = (int)$interval->format('%R%a');

    if ($days > 0) {
        $remainingDays = $days . ' day' . ($days > 1 ? 's left' : ' left');
    } elseif ($days < 0) {
        $remainingDays = 'Overdue by ' . abs($days) . ' day' . (abs($days) > 1 ? 's' : '');
        // Automatically update status to 'overdue' if past end date
        if ($days < 0 && $currentStatus !== 'overdue') {
            $updateSql = "UPDATE rentals SET status = 'overdue', updated_at = NOW() WHERE id = :rentalId";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bindParam(':rentalId', $rentalId, PDO::PARAM_INT);
            $updateStmt->execute();
            header("Location: view_rental.php?rental_id=$rentalId");
            exit();
        }
    } else {
        $remainingDays = 'Due Today';
    }
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
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../css/owner/renter_details.css">

    <style>
        /* Add these changes to your existing CSS */
.progress-container {
    position: relative;
    display: flex;
    justify-content: space-between;
    margin: 40px 0 60px; /* Add bottom margin for proof links */
}

.progress-line {
    position: absolute;
    top: 20px; /* Center vertically relative to circles */
    left: 0;
    right: 0;
    height: 4px;
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

/* Position labels below progress line */
.progress-step .label {
    position: absolute;
    top: 50px; /* Below progress line */
    width: 120px;
    text-align: center;
}

/* Proof links container */
.proof-links {
    position: absolute;
    top: 80px; /* Below labels */
    width: 100%;
    display: flex;
    justify-content: space-between;
}

/* Individual proof link positioning */
.proof-link {
    width: 120px;
    text-align: center;
}

.progress-step.overdue {
    color: white;
    background-color: red; /* Red background for Overdue status */
    border-color: red; /* Red border */
}

.progress-step.overdue .circle {
    background-color: red; /* Red circle for Overdue */
}
    </style>
    
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>

    <main>
        <div class="card">
            <div class="card-header">Rental Details</div>
            <div class="card-body">
                <!-- Alerts -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Rental Info -->
                <h5 class="card-title">Rental ID: <?= htmlspecialchars($rental['id']) ?></h5>
                <p class="card-text"><strong>Rental Date:</strong> <?= htmlspecialchars($rental['created_at'] ?? 'N/A') ?></p>
                <p class="card-text"><strong>Meet-up Date:</strong> <?= htmlspecialchars($rental['start_date'] ?? 'N/A') ?></p>

                <?php if ($currentStatus === 'returned' && !$hasOwnerReview): ?>
    <!-- Button to trigger the modal -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
        Give Feedback
    </button>
<?php endif; ?>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Submit Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <h4>Give Feedback</h4>

                    <!-- Rating for the renter -->
                    <div class="mb-3">
                        <label for="renter_rating" class="form-label">Renter Rating (1-5)</label>
                        <select class="form-select" name="renter_rating" required>
                            <option value="" disabled selected>Select rating</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="renter_comment" class="form-label">Commend for Renter</label>
                        <textarea class="form-control" name="renter_comment" rows="3" required></textarea>
                    </div>

                    <!-- Rating for the product -->
                    <div class="mb-3">
                        <label for="product_rating" class="form-label">Product Rating (1-5)</label>
                        <select class="form-select" name="product_rating" required>
                            <option value="" disabled selected>Select rating</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="product_comment" class="form-label">Comment for Product</label>
                        <textarea class="form-control" name="product_comment" rows="3" required></textarea>
                    </div>

                    <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
                </form>
            </div>
        </div>
    </div>
</div>

                <!-- Action Buttons -->
                <?php if ($currentStatus === 'pending_confirmation'): ?>
                    <div class="mb-4">
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success me-2">Approve Rental</button>
                        </form>
                        
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn btn-danger">Cancel Rental</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($currentStatus === 'approved'): ?>
                    <form method="post" enctype="multipart/form-data" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="upload_proof">
                        <div class="mb-3">
                            <label class="form-label">Upload Delivery Proof (Max 2MB, PNG/JPG)</label>
                            <input type="file" class="form-control" name="proof_file" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Owner's Delivery Proof</button>
                    </form>
                <?php endif; ?>

                <?php if ($currentStatus === 'delivered'): ?>
                    <form method="post" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="start_renting">
                        <button type="submit" class="btn btn-success">Start Rental Period</button>
                    </form>
                <?php endif; ?>

                <?php if ($currentStatus === 'renting'): ?>
    <form method="post" class="mb-4">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="end_rent">
        <button type="submit" class="btn btn-warning">End Rental</button>
    </form>
<?php endif; ?>


                <?php if ($currentStatus === 'pending_confirmation') : ?>

<?php endif; ?>

                <!-- Progress Steps -->
                <div class="progress-container">
                    <div class="progress-line"></div>

                    

                    
                    <?php 
    // Sort delivery proofs by creation time
    $deliveryProofs = $proofsByType['delivery'];
    usort($deliveryProofs, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
?>

<?php foreach ($filteredStatusFlow as $key => $label): ?>
    <div class="progress-step <?= isStatusActive($key, $currentStatus, $filteredStatusFlow) ? 'active' : '' ?>">
        <div class="circle"><?= $key === $currentStatus ? "✔" : "" ?></div>
        <div class="label">
            <?= htmlspecialchars($label) ?>

            <!-- For "delivery_in_progress", show proof uploaded by the owner (delivery proof) -->
            <?php if ($key === 'delivery_in_progress' && isset($proofsByType['delivery'][0])): ?>
                <div class="mt-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#proofModal" 
                       data-bs-url="<?= htmlspecialchars($proofsByType['delivery'][0]['proof_url']) ?>"
                       data-bs-type="<?= htmlspecialchars($proofsByType['delivery'][0]['proof_type']) ?>"
                       data-bs-date="<?= htmlspecialchars(date('F j, Y', strtotime($proofsByType['delivery'][0]['created_at']))) ?>">
                        View (Owner's Proof of Delivery)
                    </a>
                </div>
            <?php endif; ?>

            <!-- For "delivered", show proof uploaded by the renter (delivery completion proof) -->
            <?php if ($key === 'delivered' && isset($proofsByType['delivered'][0])): ?>
                <div class="mt-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#proofModal" 
                       data-bs-url="<?= htmlspecialchars($proofsByType['delivered'][0]['proof_url']) ?>"
                       data-bs-type="<?= htmlspecialchars($proofsByType['delivered'][0]['proof_type']) ?>"
                       data-bs-date="<?= htmlspecialchars(date('F j, Y', strtotime($proofsByType['delivered'][0]['created_at']))) ?>">
                        View (Renter's Proof of Delivery Completion)
                    </a>
                </div>
            <?php endif; ?>

            <!-- For "return", show return proof -->
            <?php if ($key === 'returned' && isset($proofsByType['return'][0])): ?>
                <div class="mt-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#proofModal" 
                       data-bs-url="<?= htmlspecialchars($proofsByType['return'][0]['proof_url']) ?>"
                       data-bs-type="<?= htmlspecialchars($proofsByType['return'][0]['proof_type']) ?>"
                       data-bs-date="<?= htmlspecialchars(date('F j, Y', strtotime($proofsByType['return'][0]['created_at']))) ?>">
                        View (Renter's Proof of Return)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Viewing Proofs -->
<!-- Modal for Viewing Proofs -->
<div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;"> <!-- Adjusted modal size -->
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <h5 class="modal-title m-0" id="proofModalLabel"></h5>
                    <span id="proofDate" class="text-muted small me-3"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="proofImage" src="" alt="Proof Image" style="max-width: 80%; height: auto; margin: 0 auto;">
            </div>
        </div>
    </div>
</div>

<script>
    const proofModal = document.getElementById('proofModal');
    proofModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const proofUrl = button.getAttribute('data-bs-url');
        const proofType = button.getAttribute('data-bs-type');
        const proofDate = button.getAttribute('data-bs-date');

        // Title mapping
        const titleMap = {
            'delivery': 'Proof of Delivery',
            'return': 'Proof of Return',
            'delivered': 'Proof of Delivery Completion'
        };

        // Update elements
        proofModal.querySelector('.modal-title').textContent = titleMap[proofType] || 'Proof';
        proofModal.querySelector('#proofDate').textContent = proofDate;
        proofModal.querySelector('#proofImage').src = proofUrl;
    });
</script>

    <!-- Bootstrap JS Bundle -->
    <script>
        // JavaScript to update modal with the proof image URL
        const proofModal = document.getElementById('proofModal');
        proofModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const proofUrl = button.getAttribute('data-bs-url'); // Extract info from data-bs-url attribute
            const proofImage = proofModal.querySelector('#proofImage');
            proofImage.src = proofUrl; // Update the modal image source with the proof URL
        });
    </script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>