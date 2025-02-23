<?php
session_start();
require_once 'db/db.php'; // Ensure this file creates a PDO instance in $conn

if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mobile_number = trim($_POST['mobile_number']);
    $otp = rand(100000, 999999); // Generate a random 6-digit OTP
    
    // Set OTP expiry time (e.g., 10 minutes from now)
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));  // 10 minutes expiration

    // Prepare the UPDATE statement using PDO named parameters.
    $sql = "UPDATE user_verification SET mobile_number = :mobile_number, otp = :otp, otp_expiry = :otp_expiry WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $params = [
        ':mobile_number' => $mobile_number,
        ':otp'           => $otp,
        ':otp_expiry'    => $otp_expiry, // Set OTP expiry
        ':user_id'       => $_SESSION['user_id']
    ];

    if ($stmt->execute($params)) {
        // Instead of sending OTP via Telesign, just store it in session for later use
        $_SESSION['generated_otp'] = $otp;  // Store OTP in session for verification
        header("Location: addnumber_verify.php"); // Redirect to OTP verification page
        exit;
    } else {
        $error = "Error updating mobile number.";
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
            <div class="card mx-auto mb-5 border border-0" style="width:500px;">
                <div class="card-body d-flex flex-column flex-nowrap justify-content-center">
                    <div class="mt-4 text-center d-flex justify-content-center">
                        <h3 class="bg-success text-white rounded-circle pt-1" style="width: 40px; height: 40px">1</h3>
                    </div>
                    <h5 class="text-center mt-4 fw-bold">Verify your Account</h5>
                    <h6 class="text-center mx-4 mb-4">Rentbox requires a three-step verification process to ensure your account is secure.</h6>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form action="addnumber.php" method="POST">
                        <div class="input-group mb-3 mx-3">
                            <span class="input-group-text rounded-start-5">+63</span>
                            <div class="form-floating" style="font-size: 14px;">
                                <input type="text" name="mobile_number" class="form-control ps-4 rounded-end-5" id="floatingInput" placeholder="Enter your mobile number" required>
                                <label for="floatingInput" class="ps-4">Enter your mobile number</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success rounded-5 mx-5 mt-2 mb-3 shadow">Send OTP</button>
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
