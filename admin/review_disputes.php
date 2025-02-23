<?php
// admin/review_disputes.php
session_start();
require_once 'includes/auth.php'; 
require_once __DIR__ . '/../db/db.php';

// Check if admin is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin/login.php');
    exit();
}

$adminId = $_SESSION['id'];

// Handle dispute status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check (implement if needed)

    $dispute_id = intval($_POST['dispute_id']);
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes']);

    // Validate status
    $valid_statuses = ['open', 'under_review', 'resolved', 'closed'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error_message'] = "Invalid status selected.";
        header('Location: review_disputes.php');
        exit();
    }

    // Update dispute
    $stmt = $conn->prepare("UPDATE disputes SET status = :status, admin_notes = :admin_notes, resolved_at = IF(:status = 'resolved', NOW(), resolved_at) WHERE id = :dispute_id");
    $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
    $stmt->bindParam(':admin_notes', $admin_notes, PDO::PARAM_STR);
    $stmt->bindParam(':dispute_id', $dispute_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount()) {
        $_SESSION['success_message'] = "Dispute updated successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to update dispute.";
    }

    header('Location: review_disputes.php');
    exit();
}

// Fetch all disputes
$stmt = $conn->prepare("SELECT d.*, u.name AS user_name, p.name AS product_name 
                        FROM disputes d 
                        JOIN users u ON d.initiated_by = u.id 
                        JOIN rentals r ON d.rental_id = r.id 
                        JOIN products p ON r.product_id = p.id 
                        ORDER BY d.created_at DESC");
$stmt->execute();
$disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Disputes - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../admin/includes/owner-header-sidebar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4">Review Disputes</h2>

                <!-- Success Message -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Product</th>
                            <th>Reason</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Filed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($disputes)): ?>
                            <?php foreach ($disputes as $dispute): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dispute['user_name']) ?></td>
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
                                    <td>
                                        <!-- Button to open modal for updating dispute -->
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateDisputeModal<?= $dispute['id'] ?>">
                                            Update
                                        </button>

                                        <!-- Modal -->
                                        <div class="modal fade" id="updateDisputeModal<?= $dispute['id'] ?>" tabindex="-1" aria-labelledby="updateDisputeModalLabel<?= $dispute['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="review_disputes.php" method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="updateDisputeModalLabel<?= $dispute['id'] ?>">Update Dispute Status</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="dispute_id" value="<?= htmlspecialchars($dispute['id']) ?>">
                                                            <div class="mb-3">
                                                                <label for="status<?= $dispute['id'] ?>" class="form-label">Status</label>
                                                                <select class="form-select" id="status<?= $dispute['id'] ?>" name="status" required>
                                                                    <option value="open" <?= $dispute['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                                                    <option value="under_review" <?= $dispute['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                                                                    <option value="resolved" <?= $dispute['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                                    <option value="closed" <?= $dispute['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="admin_notes<?= $dispute['id'] ?>" class="form-label">Admin Notes</label>
                                                                <textarea class="form-control" id="admin_notes<?= $dispute['id'] ?>" name="admin_notes" rows="3"><?= htmlspecialchars($dispute['admin_notes']) ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No disputes found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>