<?php
ini_set('display_errors', 0); // Disable error display in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/db.php';
require_once 'functions.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    header("Location: /rb/login.php");
    exit();
}

$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 2 * 1024 * 1024; // 2MB

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    try {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            throw new Exception("CSRF token verification failed.");
        }

        // Retrieve and sanitize form data
        $name = trim($_POST['name']);
        $brand = trim($_POST['brand']);
        $description = trim($_POST['description']);
        $rental_price = floatval($_POST['rental_price']);
        $rental_period = isset($_POST['rental_period']) ? trim($_POST['rental_period']) : null;
        $category = trim($_POST['category']);
        $quantity = intval($_POST['quantity']);
        $overdue_price = floatval($_POST['overdue_price']);  // New field for overdue price
        $real_price = floatval($_POST['real_price']);  // New field for real price
        $condition_description = trim($_POST['condition_description']);  // New field for condition description

        // Validate form data
        if (empty($name) || empty($brand) || empty($description) || $rental_price <= 0 || empty($rental_period) || empty($category) || $quantity <= 0) {
            throw new Exception("Validation failed: Missing or invalid fields.");
        }

        // Handle image upload
        $image_filename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $image_upload = handle_image_upload($_FILES['image'], $allowed_extensions, $max_file_size);
            if ($image_upload['success']) {
                $image_filename = $image_upload['filename'];
            } else {
                log_error("Add Product Image Upload Error: " . $image_upload['message']);
                throw new Exception("Image upload failed: " . htmlspecialchars($image_upload['message']));
            }
        }

        // Insert into database
        $owner_id = $_SESSION['id'];
        $stmt = $conn->prepare("INSERT INTO products (owner_id, name, brand, description, rental_price, rental_period, status, created_at, updated_at, image, quantity, category, overdue_price, real_price, condition_description) VALUES (?, ?, ?, ?, ?, ?, 'pending_approval', NOW(), NOW(), ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$owner_id, $name, $brand, $description, $rental_price, $rental_period, $image_filename, $quantity, $category, $overdue_price, $real_price, $condition_description]);

        // Set success message
        $_SESSION['success'] = "Product added successfully! Awaiting approval.";
        header("Location: gadget.php");
        exit();
    } catch (Exception $e) {
        log_error("Add Product Error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: gadget.php");
        exit();
    }
}


// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    try {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            throw new Exception("CSRF token verification failed.");
        }

        // Retrieve and sanitize form data
        $product_id = intval($_POST['product_id']);
        $name = trim($_POST['name']);
        $brand = trim($_POST['brand']);
        $description = trim($_POST['description']);
        $rental_price = floatval($_POST['rental_price']);
        $rental_period = isset($_POST['rental_period']) ? trim($_POST['rental_period']) : null;
        $category = trim($_POST['category']);
        $quantity = intval($_POST['quantity']);

        // Validate form data
        if (empty($name) || empty($brand) || empty($description) || $rental_price <= 0 || empty($rental_period) || empty($category) || $quantity <= 0) {
            throw new Exception("Validation failed: Missing or invalid fields.");
        }

        // Handle image upload
        $image_filename = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $image_upload = handle_image_upload($_FILES['image'], $allowed_extensions, $max_file_size);
            if ($image_upload['success']) {
                $image_filename = $image_upload['filename'];
            } else {
                log_error("Edit Product Image Upload Error: " . $image_upload['message']);
                throw new Exception("Image upload failed: " . htmlspecialchars($image_upload['message']));
            }
        }

        // Prepare SQL statement
        if ($image_filename) {
            $sql = "UPDATE products SET name = ?, brand = ?, description = ?, rental_price = ?, rental_period = ?, image = ?, quantity = ?, category = ?, status = 'pending_approval', updated_at = NOW() WHERE id = ? AND owner_id = ?";
            $params = [$name, $brand, $description, $rental_price, $rental_period, $image_filename, $quantity, $category, $product_id, $_SESSION['id']];
        } else {
            $sql = "UPDATE products SET name = ?, brand = ?, description = ?, rental_price = ?, rental_period = ?, quantity = ?, category = ?, status = 'pending_approval', updated_at = NOW() WHERE id = ? AND owner_id = ?";
            $params = [$name, $brand, $description, $rental_price, $rental_period, $quantity, $category, $product_id, $_SESSION['id']];
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Set success message
        $_SESSION['success'] = "Product updated successfully! Awaiting approval.";
        header("Location: gadget.php");
        exit();
    } catch (Exception $e) {
        log_error("Edit Product Error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: gadget.php");
        exit();
    }
}

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    try {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            throw new Exception("CSRF token verification failed.");
        }

        // Retrieve and sanitize form data
        $product_id = intval($_POST['product_id']);

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND owner_id = ?");
        $stmt->execute([$product_id, $_SESSION['id']]);

        // Set success message
        $_SESSION['success'] = "Product deleted successfully!";
        header("Location: gadget.php");
        exit();
    } catch (Exception $e) {
        log_error("Delete Product Error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: gadget.php");
        exit();
    }
}

// Fetch and display products
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE owner_id = ?");
    $stmt->execute([$_SESSION['id']]);
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    log_error("Fetch Products Error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to fetch products: " . $e->getMessage();
    $products = [];
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #sidebarMenu {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding-top: 56px;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        main {
            padding-top: 56px;
        }
        @media (max-width: 768px) {
            #sidebarMenu {
                position: relative;
                height: auto;
                padding-top: 0;
            }
            main {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-2 d-md-block bg-light sidebar collapse">
                <!-- Sidebar content -->
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="bg-secondary-subtle my-3">
                    <div class="card rounded-3">
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-2 mx-5">
                            <h2 class="mb-0">My Gadgets</h2>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="bi bi-plus-lg"></i> Add Item
                            </button>
                        </div>

                        <!-- Display Success Message -->
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show mx-5" role="alert">
                                <?= htmlspecialchars($_SESSION['success']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <!-- Display Error Message -->
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show mx-5" role="alert">
                                <?= htmlspecialchars($_SESSION['error']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <?php include __DIR__ . '/add-modal.html'; ?>

                        <hr class="mx-3 my-0">

                        <div class="card-body rounded-5">
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered text-center">
                                        <thead class="table-dark">
                                            <tr>
                                                <th scope="col" style="width: 5%;">No.</th>
                                                <th scope="col" style="width: 15%;">Name</th>
                                                <th scope="col" style="width: 15%;">Brand</th>
                                                <th scope="col" style="width: 20%;">Description</th>
                                                <th scope="col" style="width: 10%;">Price</th>
                                                <th scope="col" style="width: 10%;">Quantity</th>
                                                <th scope="col" style="width: 10%;">Category</th>
                                                <th scope="col" style="width: 10%;">Status</th>
                                                <th scope="col" style="width: 15%;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($products): ?>
                                                <?php foreach ($products as $index => $product): ?>
                                                    <tr class="align-middle">
                                                        <th scope="row"><?= htmlspecialchars($index + 1) ?></th>
                                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                                        <td><?= htmlspecialchars($product['brand']) ?></td>
                                                        <td><?= nl2br(htmlspecialchars($product['description'])) ?></td>
                                                        <td>
                                                            PHP <?= number_format($product['rental_price'], 2) ?>
                                                            <?php if (isset($product['rental_period'])): ?>
                                                                per <?= htmlspecialchars($product['rental_period']) ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($product['quantity']) ?></td>
                                                        <td><?= htmlspecialchars($product['category']) ?></td>
                                                        <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($product['status']))) ?></td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $product['id'] ?>" title="View">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $product['id'] ?>" title="Edit">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $product['id'] ?>" title="Delete">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php include __DIR__ . '/view-modal.html'; ?>
                                                    <?php include __DIR__ . '/edit-modal.html'; ?>
                                                    <?php include __DIR__ . '/delete-modal.html'; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">No gadgets found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        var addModal = document.getElementById('addModal');
        if (addModal) {
            addModal.addEventListener('shown.bs.modal', function () {
                document.getElementById('productName').focus();
            });
        }
        <?php foreach ($products as $product): ?>
            var editModal<?= $product['id'] ?> = document.getElementById('editModal<?= $product['id'] ?>');
            if (editModal<?= $product['id'] ?>) {
                editModal<?= $product['id'] ?>.addEventListener('shown.bs.modal', function () {
                    document.getElementById('editName<?= $product['id'] ?>').focus();
                });
            }
        <?php endforeach; ?>
    </script>

</body>
</html>