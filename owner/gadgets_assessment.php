<?php
// owner/gadgets_assessment.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';

// Check if owner is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    header('Location: login.php');
    exit();
}

$ownerId = $_SESSION['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check (implement if needed)
    // Validate inputs
    $product_id = intval($_POST['product_id']);
    $condition_description = trim($_POST['condition_description']);
    
    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];
        $fileType = $_FILES['photo']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Check allowed file extensions
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory in which to save the uploaded file
            $uploadFileDir = '../uploads/gadget_conditions/';
            $dest_path = $uploadFileDir . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $photo = $newFileName;
            } else {
                $_SESSION['error'] = "There was an error uploading the file.";
                header('Location: gadgets_assessment.php');
                exit();
            }
        } else {
            $_SESSION['error'] = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
            header('Location: gadgets_assessment.php');
            exit();
        }
    } else {
        $photo = null; // Photo is optional
    }

    if (empty($condition_description)) {
        $_SESSION['error'] = "Condition description cannot be empty.";
        header('Location: gadgets_assessment.php');
        exit();
    }

    // Insert into gadget_conditions table
    $stmt = $conn->prepare("INSERT INTO gadget_conditions (product_id, owner_id, condition_description, photo) VALUES (:product_id, :owner_id, :condition_description, :photo)");
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':owner_id', $ownerId, PDO::PARAM_INT);
    $stmt->bindParam(':condition_description', $condition_description, PDO::PARAM_STR);
    $stmt->bindParam(':photo', $photo, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Gadget condition assessed successfully.";
    } else {
        $_SESSION['error'] = "Failed to assess gadget condition.";
    }

    header('Location: gadgets_assessment.php');
    exit();
}

// Fetch owner's products
$stmt = $conn->prepare("SELECT id, name FROM products WHERE owner_id = :ownerId");
$stmt->bindParam(':ownerId', $ownerId, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing gadget condition assessments
$stmt = $conn->prepare("SELECT gc.*, p.name AS product_name FROM gadget_conditions gc JOIN products p ON gc.product_id = p.id WHERE gc.owner_id = :ownerId ORDER BY gc.reported_at DESC");
$stmt->bindParam(':ownerId', $ownerId, PDO::PARAM_INT);
$stmt->execute();
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gadget Condition Assessment - Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4">Gadget Condition Assessment</h2>

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

                <!-- Assessment Form -->
                <div class="card my-4">
                    <div class="card-header">
                        Assess Gadget Condition
                    </div>
                    <div class="card-body">
                        <form action="gadgets_assessment.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="product_id" class="form-label">Select Gadget</label>
                                <select class="form-select" id="product_id" name="product_id" required>
                                    <option value="" disabled selected>Select a gadget</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= htmlspecialchars($product['id']) ?>"><?= htmlspecialchars($product['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="condition_description" class="form-label">Condition Description</label>
                                <textarea class="form-control" id="condition_description" name="condition_description" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="photo" class="form-label">Upload Photo (Optional)</label>
                                <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Assessment</button>
                        </form>
                    </div>
                </div>

                <!-- Existing Assessments -->
                <h3>Existing Assessments</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Gadget</th>
                            <th>Description</th>
                            <th>Photo</th>
                            <th>Reported At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assessments)): ?>
                            <?php foreach ($assessments as $assessment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($assessment['product_name']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($assessment['condition_description'])) ?></td>
                                    <td>
                                        <?php if ($assessment['photo']): ?>
                                            <a href="../uploads/gadget_conditions/<?= htmlspecialchars($assessment['photo']) ?>" target="_blank">
                                                <img src="../uploads/gadget_conditions/<?= htmlspecialchars($assessment['photo']) ?>" alt="Photo" width="100">
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($assessment['reported_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No assessments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

</body>
</html>