<?php
// admin/includes/auth.php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    // Not logged in, redirect to login page
    header('Location: /login.php');
    exit();
}

// Check if the user has the admin role
if ($_SESSION['role'] !== 'admin') {
    // Not an admin, show a 403 Forbidden error
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. You do not have permission to view this page.';
    exit();
}
?>