<?php
require_once 'functions.php';
session_start();

// Check if the request method is POST to prevent CSRF via GET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        // Invalid CSRF token
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: ../login.php"); // Adjust the path as needed
        exit();
    }

    // Unset all session variables
    $_SESSION = array();

    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to the login page with a success message
    header("Location: ../login.php?message=Logged out successfully.");
    exit();
} else {
    // If accessed via GET or other methods, redirect to the login page
    header("Location: ../login.php");
    exit();
}
?>