<?php
// admin/respond_support.php
session_start();
require_once 'includes/auth.php'; 
require_once __DIR__ . '/../db/db.php';

// Check if admin is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin/login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $support_id = intval($_POST['support_id']);
    $admin_response = trim($_POST['admin_response']);

    // Validate inputs
    $errors = [];
    if (empty($admin_response)) $errors[] = "Response cannot be empty.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE support_requests SET admin_response = :admin_response, status = 'closed' WHERE id = :support_id");
        $stmt->bindParam(':admin_response', $admin_response, PDO::PARAM_STR);
        $stmt->bindParam(':support_id', $support_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Support request responded successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to respond to support request.";
        }

        header('Location: respond_support.php');
        exit();
    } else {
        $_SESSION['error_message'] = implode('<br>', $errors);
        header('Location: respond_support.php');
        exit();
    }
}

// Fetch all support requests
$stmt = $conn->prepare("SELECT sr.*, u.name AS user_name 
                        FROM support_requests sr 
                        JOIN users u ON sr.user_id = u.id 
                        ORDER BY sr.created_at DESC");
$stmt->execute();
$supportRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Respond to Support Requests - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4">Respond to Support Requests</h2>

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
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Response</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($supportRequests)): ?>
                            <?php foreach ($supportRequests as $request): ?>
                                <tr>
                                    <td><?= htmlspecialchars($request['user_name']) ?></td>
                                    <td><?= htmlspecialchars($request['subject']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($request['message'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $request['status'] === 'open' ? 'warning' : 
                                            ($request['status'] === 'in_progress' ? 'info' : 'success')
                                        ?>">
                                            <?= ucfirst($request['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= nl2br(htmlspecialchars($request['admin_response'])) ?></td>
                                    <td>
                                        <?php if ($request['status'] !== 'closed'): ?>
                                            <!-- Button to open modal for responding -->
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#respondModal<?= $request['id'] ?>">
                                                Respond
                                            </button>

                                            <!-- Modal -->
                                            <div class="modal fade" id="respondModal<?= $request['id'] ?>" tabindex="-1" aria-labelledby="respondModalLabel<?= $request['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="respond_support.php" method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="respondModalLabel<?= $request['id'] ?>">Respond to Support Request</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="support_id" value="<?= htmlspecialchars($request['id']) ?>">
                                                                <div class="mb-3">
                                                                    <label for="admin_response<?= $request['id'] ?>" class="form-label">Response</label>
                                                                    <textarea class="form-control" id="admin_response<?= $request['id'] ?>" name="admin_response" rows="5" required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Send Response</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No support requests found.</td>
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