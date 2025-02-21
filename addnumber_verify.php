<?php
session_start();
require_once 'db/db.php'; // Ensure this file creates a PDO instance in $conn

if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp_entered = trim($_POST['otp_entered']);
    
    // Prepare the SELECT statement using a named parameter.
    $sql = "SELECT otp, otp_expiry FROM user_verification WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    
    // Fetch the OTP and expiry time from the database.
    $row = $stmt->fetch();
    $stored_otp = $row ? $row['otp'] : null;
    $otp_expiry = $row ? $row['otp_expiry'] : null;
    
    // Check if OTP record exists
    if ($stored_otp === null) {
        $error = "No OTP record found.";
    } elseif ($otp_expiry === null || strtotime($otp_expiry) < time()) {
        // If otp_expiry is null, or it has expired
        $error = "OTP has expired. Please request a new one.";
    } elseif ($otp_entered == $stored_otp) {
        // Mark OTP as verified
        $sqlUpdate = "UPDATE user_verification SET otp_verified = 1 WHERE user_id = :user_id";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if ($stmtUpdate->execute([':user_id' => $_SESSION['user_id']])) {
            // Redirect to the next step, for example, adding an ID
            header("Location: addid.php");
            exit;
        } else {
            $error = "Error updating OTP verification.";
        }
    } else {
        $error = "Invalid OTP. Please try again.";
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
            <div class="card mx-auto mb-5 border border-0" style="width:500px;">
                <div class="card-body p-5">
                    <h5 class="text-center my-3 fw-bold">Enter OTP</h5>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form action="addnumber_verify.php" method="POST">
                        <div class="form-floating d-flex flex-column justify-content-center gap-3" style="font-size: 14px;">
                            <input type="text" name="otp_entered" class="form-control ps-4 rounded-5" id="floatingInput" placeholder="Enter OTP" required>
                            <label for="floatingInput" class="ps-4">Enter the OTP sent to your number</label>
                            <div class="d-flex flex-column justify-content-center mx-5">
                                <button type="submit" class="btn btn-success rounded-5 shadow mx-5">Verify OTP</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-primary rounded-4" href="addid.php">Continue</a>
                </div>
            </div>
        </div>
    </main>
    <script src="vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


