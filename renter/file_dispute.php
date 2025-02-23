<?php
// renter/file_dispute.php
session_start();
require_once __DIR__ . '/../db/db.php';

// Check if user is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'renter') {
    header('Location: ../renter/login.php');
    exit();
}

$userId = $_SESSION['id'];

// Fetch rentals of the user to select for dispute
$stmt = $conn->prepare("SELECT r.id, p.name 
                        FROM rentals r 
                        JOIN products p ON r.product_id = p.id 
                        WHERE r.renter_id = :userId");
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->execute();
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = intval($_POST['rental_id']);
    $reason = trim($_POST['reason']);
    $description = trim($_POST['description']);

    // Validate inputs
    $errors = [];
    if (empty($rental_id)) $errors[] = "Rental selection is required.";
    if (empty($reason)) $errors[] = "Reason is required.";
    if (empty($description)) $errors[] = "Description is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO disputes (rental_id, initiated_by, reason, description) VALUES (:rental_id, :initiated_by, :reason, :description)");
        $stmt->bindParam(':rental_id', $rental_id, PDO::PARAM_INT);
        $stmt->bindParam(':initiated_by', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Dispute filed successfully.";
        } else {
            $_SESSION['error'] = "Failed to file dispute.";
        }

        header('Location: file_dispute.php');
        exit();
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: file_dispute.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File a Dispute - Renter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php require_once '../includes/navbarr.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4">File a Dispute</h2>

                <!-- Success Message -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card my-4">
                    <div class="card-header">
                        Dispute Form
                    </div>
                    <div class="card-body">
                        <form action="file_dispute.php" method="POST">
                            <div class="mb-3">
                                <label for="rental_id" class="form-label">Select Rental</label>
                                <select class="form-select" id="rental_id" name="rental_id" required>
                                    <option value="" disabled selected>Select a rental</option>
                                    <?php foreach ($rentals as $rental): ?>
                                        <option value="<?= htmlspecialchars($rental['id']) ?>"><?= htmlspecialchars($rental['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason</label>
                                <input type="text" class="form-control" id="reason" name="reason" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Dispute</button>
                        </form>
                    </div>
                </div>

                <!-- Existing Disputes -->
                <h3>Your Disputes</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Rental</th>
                            <th>Reason</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Filed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT d.*, p.name AS product_name 
                                                FROM disputes d 
                                                JOIN rentals r ON d.rental_id = r.id 
                                                JOIN products p ON r.product_id = p.id 
                                                WHERE d.initiated_by = :userId 
                                                ORDER BY d.created_at DESC");
                        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                        $stmt->execute();
                        $userDisputes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (!empty($userDisputes)): ?>
                            <?php foreach ($userDisputes as $dispute): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dispute['product_name']) ?></td>
                                    <td><?= htmlspecialchars($dispute['reason']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($dispute['description'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $dispute['status'] === 'open' ? 'warning' : 
                                            ($dispute['status'] === 'under_review' ? 'info' : 
                                            ($dispute['status'] === 'resolved' ? 'success' : 'secondary'))
                                        ?>">
                                            <?= ucfirst($dispute['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($dispute['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No disputes filed.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>