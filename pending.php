<?php
session_start();
// If the user is not logged in, redirect them to signup or login.
if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Account Pending Verification - Rentbox</title>
  <link href="vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="vendor/font/bootstrap-icons.css">
  <style>
    .pending-message {
      margin-top: 100px;
    }
  </style>
  <script>
    // Auto redirect after 5 seconds to landing.php
    setTimeout(function(){
      window.location.href = "landing.php";
    }, 5000);
  </script>
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
  <main class="container pending-message text-center">
    <div class="alert alert-info">
      <h4>Thank you for completing your verification steps!</h4>
      <p>Your account is now pending verification by our admin.<br>
         You will receive notification once your account is approved.</p>
      <p>If you are not redirected automatically, click <a href="landing.php" class="alert-link">here</a>.</p>
    </div>
  </main>
  <footer class="mt-5 px-3">
    <div class="d-flex flex-column flex-sm-row justify-content-between py-2 border-top">
      <p>© 2024 Rentbox. All rights reserved.</p>
    </div>
  </footer>
  <script src="vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>