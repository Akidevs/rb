<?php
// addid.php
session_start();
require_once 'db/db.php'; // This file must create a PDO instance in $conn

if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uploadDir = "img/verification/";
    
    // Ensure the uploads directory exists.
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $error = "Failed to create upload directory.";
        }
    }
    
    // Allowed MIME types and maximum file size (5 MB)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
    
    // Validate and process valid ID photo
    if (isset($_FILES['valid_id_photo']) && $_FILES['valid_id_photo']['error'] === 0) {
        // Check file size
        if ($_FILES['valid_id_photo']['size'] > $maxFileSize) {
            $error = "Valid ID photo must be 5MB or less.";
        }
        // Check MIME type using PHP's finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['valid_id_photo']['tmp_name']);
        if (!in_array($mimeType, $allowedTypes)) {
            $error = "Valid ID photo must be an image (JPEG, PNG, GIF).";
        }
        if (!$error) {
            // Use time() to generate a unique prefix for the file name.
            $validIdName = $uploadDir . time() . "_" . basename($_FILES['valid_id_photo']['name']);
            if (!move_uploaded_file($_FILES['valid_id_photo']['tmp_name'], $validIdName)) {
                $error = "Error uploading valid ID photo.";
            }
        }
    } else {
        $error = "Error uploading valid ID photo.";
    }
    
    // Validate and process selfie photo
    if (isset($_FILES['selfie_photo']) && $_FILES['selfie_photo']['error'] === 0) {
        if ($_FILES['selfie_photo']['size'] > $maxFileSize) {
            $error = "Selfie photo must be 5MB or less.";
        }
        $mimeType = $finfo->file($_FILES['selfie_photo']['tmp_name']);
        if (!in_array($mimeType, $allowedTypes)) {
            $error = "Selfie photo must be an image (JPEG, PNG, GIF).";
        }
        if (!$error) {
            $selfieName = $uploadDir . time() . "_" . basename($_FILES['selfie_photo']['name']);
            if (!move_uploaded_file($_FILES['selfie_photo']['tmp_name'], $selfieName)) {
                $error = "Error uploading selfie photo.";
            }
        }
    } else {
        $error = "Error uploading selfie photo.";
    }
    
    // If no error so far, update the database.
    if (!$error) {
        $sql = "UPDATE user_verification 
                SET valid_id_photo = :valid_id_photo, selfie_photo = :selfie_photo 
                WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $params = [
            ':valid_id_photo' => $validIdName,
            ':selfie_photo'   => $selfieName,
            ':user_id'        => $_SESSION['user_id']
        ];
        if ($stmt->execute($params)) {
            header("Location: addcosignee.php");
            exit;
        } else {
            $errorInfo = $stmt->errorInfo();
            $error = "Error updating verification record: " . $errorInfo[2];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Rentbox</title>
        <link rel="icon" type="image/png" href="images/rb logo white.png">
        <link href="vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="vendor/font/bootstrap-icons.css">
    </head>
    <style>
    </style>
    <body>
        <?php require_once 'includes/navbar.php'; ?>
        <main class="container-fluid">
            <div class="container-fluid">
                <div class="card mx-auto mb-5 border border-0" style="width:500px;">
                    <div class="card-body d-flex flex-column flex-nowrap justify-content-center">
                        <div class="mt-4 text-center d-flex justify-content-center">
                            <h3 class="bg-success text-white rounded-circle pt-1" style="width: 40px; height: 40px">2</h3>
                        </div>
                        <h5 class="text-center mb-1 fw-bold">Verify your Account</h5>
                        <h6 class="text-center mx-4 mt-1 mb-4">
                            Rentbox requires a three-step verification process to ensure your account is secure.
                        </h6>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form action="addid.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3 mx-3">
                                <small class="mb-3">
                                    Upload a photo of your valid id. View <a href="">Valid ID list</a> for details.
                                </small>
                                <input type="file" name="valid_id_photo" class="form-control rounded-5" required>
                            </div>
                            <div class="mb-3 mx-3">
                                <small class="mb-3">
                                    Upload a recent photo of yourself to verify your ID. View <a href="">details.</a>
                                </small>
                                <input type="file" name="selfie_photo" class="form-control rounded-5" required>
                            </div>
                            <button type="submit" class="btn btn-success rounded-5 mx-5 my-3 shadow">
                                Save & Continue
                            </button>
                            <div class="d-flex mb-3 mx-4 justify-content-center" style="font-size: 12px;">
                                <p class="text-center">
                                    Signing up for a Rentbox account means you agree to the <br>
                                    <a href="" class="text-secondary">Privacy Policy</a> and 
                                    <a href="" class="text-secondary">Terms of Service</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        <footer class="mt-5 px-3 bg-body fixed-bottom">
            <div class="d-flex flex-column flex-sm-row justify-content-between py-2 border-top">
                <p>© 2024 Rentbox. All rights reserved.</p>
                <ul class="list-unstyled d-flex">
                    <li class="ms-3"><a href=""><i class="bi bi-facebook text-body"></i></a></li>
                    <li class="ms-3"><a href=""><i class="bi bi-twitter-x text-body"></i></a></li>
                    <li class="ms-3"><a href=""><i class="bi bi-linkedin text-body"></i></a></li>
                </ul>
            </div>
        </footer>
        <script src="vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>