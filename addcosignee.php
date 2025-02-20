<?php
session_start();
require_once 'db/db.php'; // Ensure this file creates a PDO instance in $conn

if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Set the target upload directory
    $uploadDir = "img/verification/";

    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $error = "Failed to create upload directory.";
        }
    }

    // Allowed MIME types and maximum file size (5 MB)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB in bytes

    // Create a finfo object for MIME type checking
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    // Process cosignee ID front photo upload with validation
    if (isset($_FILES['cosignee_id_front']) && $_FILES['cosignee_id_front']['error'] === 0) {
        if ($_FILES['cosignee_id_front']['size'] > $maxFileSize) {
            $error = "Cosignee ID front photo must be 5MB or less.";
        }
        $mimeType = $finfo->file($_FILES['cosignee_id_front']['tmp_name']);
        if (!in_array($mimeType, $allowedTypes)) {
            $error = "Cosignee ID front photo must be an image (JPEG, PNG, or GIF).";
        }
        if (!$error) {
            $cosigneeIdFront = $uploadDir . time() . "_front_" . basename($_FILES['cosignee_id_front']['name']);
            if (!move_uploaded_file($_FILES['cosignee_id_front']['tmp_name'], $cosigneeIdFront)) {
                $error = "Error uploading cosignee ID front photo.";
            }
        }
    } else {
        $error = "Error uploading cosignee ID front photo.";
    }

    // Process cosignee ID back photo upload with validation
    if (isset($_FILES['cosignee_id_back']) && $_FILES['cosignee_id_back']['error'] === 0) {
        if ($_FILES['cosignee_id_back']['size'] > $maxFileSize) {
            $error = "Cosignee ID back photo must be 5MB or less.";
        }
        $mimeType = $finfo->file($_FILES['cosignee_id_back']['tmp_name']);
        if (!in_array($mimeType, $allowedTypes)) {
            $error = "Cosignee ID back photo must be an image (JPEG, PNG, or GIF).";
        }
        if (!$error) {
            $cosigneeIdBack = $uploadDir . time() . "_back_" . basename($_FILES['cosignee_id_back']['name']);
            if (!move_uploaded_file($_FILES['cosignee_id_back']['tmp_name'], $cosigneeIdBack)) {
                $error = "Error uploading cosignee ID back photo.";
            }
        }
    } else {
        $error = "Error uploading cosignee ID back photo.";
    }

    // Process cosignee selfie photo upload with validation
    if (isset($_FILES['cosignee_selfie']) && $_FILES['cosignee_selfie']['error'] === 0) {
        if ($_FILES['cosignee_selfie']['size'] > $maxFileSize) {
            $error = "Cosignee selfie must be 5MB or less.";
        }
        $mimeType = $finfo->file($_FILES['cosignee_selfie']['tmp_name']);
        if (!in_array($mimeType, $allowedTypes)) {
            $error = "Cosignee selfie must be an image (JPEG, PNG, or GIF).";
        }
        if (!$error) {
            $cosigneeSelfie = $uploadDir . time() . "_selfie_" . basename($_FILES['cosignee_selfie']['name']);
            if (!move_uploaded_file($_FILES['cosignee_selfie']['tmp_name'], $cosigneeSelfie)) {
                $error = "Error uploading cosignee selfie photo.";
            }
        }
    }

    // Collect cosignee info from the form
    $cosignee_email = trim($_POST['cosignee_email']);
    $cosignee_first_name = trim($_POST['cosignee_first_name']);
    $cosignee_last_name = trim($_POST['cosignee_last_name']);
    $cosignee_relationship = trim($_POST['cosignee_relationship']);

    // If no errors so far, update the user_verification record
    if (!$error) {
        $sql = "UPDATE user_verification 
                SET cosignee_email = :cosignee_email, 
                    cosignee_first_name = :cosignee_first_name, 
                    cosignee_last_name = :cosignee_last_name, 
                    cosignee_relationship = :cosignee_relationship, 
                    cosignee_id_photo = :cosignee_id_photo, 
                    cosignee_id_back_photo = :cosignee_id_back_photo, 
                    cosignee_selfie = :cosignee_selfie, 
                    verification_status = 'pending'
                WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $params = [
            ':cosignee_email' => $cosignee_email,
            ':cosignee_first_name' => $cosignee_first_name,
            ':cosignee_last_name' => $cosignee_last_name,
            ':cosignee_relationship' => $cosignee_relationship,
            ':cosignee_id_photo' => $cosigneeIdFront,
            ':cosignee_id_back_photo' => $cosigneeIdBack,
            ':cosignee_selfie' => $cosigneeSelfie,
            ':user_id' => $_SESSION['user_id']
        ];
        if ($stmt->execute($params)) {
            // Redirect to the next step (e.g., pending page)
            header("Location: pending.php");
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
<body>
<div class="container bg-body rounded-bottom-5 d-flex mb-5 py-3 shadow">
    <a href="browse.php">
        <img class="ms-5 my-4" src="images/rb logo text colored.png" alt="Logo" height="50px">
    </a>
    <div class="my-auto mx-auto d-flex gap-3">
    </div>
    <div class="d-flex me-5 align-items-center gap-3">
    </div>
</div>
    <main class="container-fluid">
        <div class="container-fluid">
            <div class="card mx-auto mb-5 border border-0" style="max-width:500px;">
                <div class="card-body d-flex flex-column flex-nowrap justify-content-center">
                    <div class="mt-4 text-center d-flex justify-content-center">
                        <h3 class="bg-success text-white rounded-circle pt-1" style="width: 40px; height: 40px">3</h3>
                    </div>
                    <h5 class="text-center mb-1 fw-bold">Verify your Account</h5>
                    <h6 class="text-center mx-4 mt-1 mb-4">
                        Rentbox requires a three-step verification process to ensure your account is secure.
                    </h6>
                    <span class="badge text-bg-success text-center mx-4 mt-1 mb-4 fs-6">Cosignee Information</span>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form action="addcosignee.php" method="POST" enctype="multipart/form-data">
                        <div class="form-floating mb-3" style="font-size: 14px;">
                            <input type="email" name="cosignee_email" class="form-control ps-4 rounded-5" id="floatingEmail" placeholder="Email" required>
                            <label for="floatingEmail" class="ps-4">Email</label>
                        </div>
                        <div class="d-flex gap-1">
                            <div class="form-floating mb-3" style="font-size: 14px;">
                                <input type="text" name="cosignee_first_name" class="form-control ps-4 rounded-5" id="floatingFirstName" placeholder="First Name" required>
                                <label for="floatingFirstName" class="ps-4">First Name</label>
                            </div>
                            <div class="form-floating mb-3" style="font-size: 14px;">
                                <input type="text" name="cosignee_last_name" class="form-control ps-4 rounded-5" id="floatingLastName" placeholder="Last Name" required>
                                <label for="floatingLastName" class="ps-4">Last Name</label>
                            </div>
                        </div>
                        <div class="form-floating mb-3" style="font-size: 14px;">
                            <input type="text" name="cosignee_relationship" class="form-control ps-4 rounded-5" id="floatingRelationship" placeholder="Affliation" required>
                            <label for="floatingRelationship" class="ps-4">Relationship to the Renter ex. Spouse, Mother..</label>
                        </div>
                        <span class="badge text-bg-success text-center mx-4 mt-4 mb-2 fs-6">Cosignee Verification</span>
                        <div class="input-group mb-3 mt-2">
    <small class="mb-3">Upload a photo of the **front** of your cosignee's valid ID
        <a href="">Valid ID list.</a></small>
    <label class="input-group-text rounded-start-5 btn btn-outline-success" for="cosigneeIdFront">Upload Front</label>
    <input type="file" name="cosignee_id_front" class="form-control rounded-end-5 btn btn-outline-success" id="cosigneeIdFront" required>
</div>

<div class="input-group mb-3 mt-4">
    <small class="mb-3">Upload a photo of the **back** of your cosignee's valid ID
        <a href="">Valid ID list.</a></small>
    <label class="input-group-text rounded-start-5 btn btn-outline-success" for="cosigneeIdBack">Upload Back</label>
    <input type="file" name="cosignee_id_back" class="form-control rounded-end-5 btn btn-outline-success" id="cosigneeIdBack" required>
</div>
                        <div class="input-group mb-3 mt-4">
                            <small class="mb-3">Upload a recent photo of your cosignee to verify ID. View 
                                <a href="">details.</a></small>
                            <label class="input-group-text rounded-start-5 btn btn-outline-success" for="cosigneeSelfie">Upload Recent</label>
                            <input type="file" name="cosignee_selfie" class="form-control rounded-end-5 btn btn-outline-success" id="cosigneeSelfie" required>
                        </div>
                        <button type="submit" class="btn btn-success rounded-5 mx-5 my-3 shadow">Save & Continue</button>
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