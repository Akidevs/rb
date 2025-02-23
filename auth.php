<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function redirectToLogin() {
    header("Location: /rb/owner/login.php?error=Please+log+in+to+access+this+page.");
    exit();
}

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    // User is not authenticated or not an owner
    redirectToLogin();
}

$timeout_duration = 1800;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();    
    session_destroy();
    redirectToLogin();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>