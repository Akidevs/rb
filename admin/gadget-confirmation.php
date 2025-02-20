<?php
require_once 'includes/auth.php';
require_once '../db/db.php';

// Handle Gadget Approvals and Rejections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gadget_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: gadget-confirmation.php");
        exit();
    }

    $gadget_id = intval($_POST['gadget_id']); // Ensure it's an integer
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        approveGadget($gadget_id);
    } elseif ($action === 'reject') {
        rejectGadget($gadget_id);
    }
    header("Location: gadget-confirmation.php");
    exit();
}

// Fetch pending gadgets
$pendingGadgets = getPendingGadgets();

function approveGadget($gadget_id) {
    global $conn;

    // Approve the gadget
    $stmt = $conn->prepare("UPDATE products SET status = 'approved' WHERE id = :id");
    $stmt->execute([':id' => $gadget_id]);

    if ($stmt->rowCount()) {
        $_SESSION['success_message'] = "Gadget approved successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to approve the gadget. It may not exist.";
    }
}

function rejectGadget($gadget_id) {
    global $conn;

    // Reject the gadget by deleting it
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $gadget_id]);

    if ($stmt->rowCount()) {
        $_SESSION['success_message'] = "Gadget rejected and removed successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to reject the gadget. It may not exist.";
    }
}

function getPendingGadgets() {
    global $conn;

    $stmt = $conn->prepare("SELECT p.id, p.name, u.name AS owner_name, p.category, p.created_at 
                            FROM products p 
                            JOIN users u ON p.owner_id = u.id 
                            WHERE p.status = 'pending_approval'");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gadget Verification - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
<?php include '../includes/admin-navbar.php'; ?>
    <h2>Gadget Verification</h2>
    <!-- Success/Error messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Gadgets Table -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Owner</th>
                <th>Category</th>
                <th>Applied On</th>
                <th>Action</th>
                </tr>
    </thead>
    <tbody>
        <?php foreach ($pendingGadgets as $gadget): ?>
            <tr>
                <td><?= htmlspecialchars($gadget['name']) ?></td>
                <td><?= htmlspecialchars($gadget['owner_name']) ?></td>
                <td><?= htmlspecialchars($gadget['category']) ?></td>
                <td><?= htmlspecialchars(date('d M, Y', strtotime($gadget['created_at']))) ?></td>
                <td>
                    <!-- Approve and Reject Actions for Gadget -->
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="gadget_id" value="<?= htmlspecialchars($gadget['id']) ?>">
                        <button type="submit" class="btn btn-success">Approve</button>
                    </form>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="gadget_id" value="<?= htmlspecialchars($gadget['id']) ?>">
                        <button type="submit" class="btn btn-danger">Reject</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>