<?php
require_once 'includes/auth.php';
require_once '../db/db.php';

// Handle User Approvals and Rejections
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: user-confirmation.php");
        exit();
    }

    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = $_POST['action'];
        $user_id = intval($_POST['user_id']); // Ensure it's an integer

        if ($action === 'approve') {
            approveUser($user_id);
        } elseif ($action === 'reject') {
            rejectUser($user_id);
        }
        header("Location: user-confirmation.php");
        exit();
    }
}

// Generate CSRF Token if needed.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch pending users for verification
$pendingUsers = getPendingUsers();

function approveUser($user_id) {
    global $conn;

    $stmt = $conn->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    
    $stmt2 = $conn->prepare("UPDATE user_verification SET verification_status = 'verified' WHERE user_id = :user_id");
    $stmt2->execute([':user_id' => $user_id]);

    if ($stmt->rowCount() && $stmt2->rowCount()) {
        $_SESSION['success_message'] = "User approved successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to approve the user.";
    }
}

function rejectUser($user_id) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);

    if ($stmt->rowCount()) {
        $_SESSION['success_message'] = "User rejected and removed successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to reject the user.";
    }
}

function getPendingUsers() {
    global $conn;

    $sql = "SELECT u.id, u.name, u.email, u.created_at, 
            uv.valid_id_photo, uv.selfie_photo, uv.cosignee_id_photo, uv.cosignee_selfie,
            uv.cosignee_email, uv.cosignee_first_name, uv.cosignee_last_name, uv.cosignee_relationship,
            uv.verification_status
            FROM users u 
            JOIN user_verification uv ON u.id = uv.user_id 
            WHERE u.role = 'renter' AND u.email_verified_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Verification - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>User Verification</h2>
    <?php include '../includes/admin-navbar.php'; ?>
    <!-- Success/Error messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Users Table -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Applied On</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pendingUsers as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars(date('d M, Y', strtotime($user['created_at']))) ?></td>
                    <td>
                        <!-- Approve and Reject Actions for User -->
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                            <button type="submit" class="btn btn-success">Approve</button>
                        </form>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
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