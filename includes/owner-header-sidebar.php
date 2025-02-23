<?php
// Ensure session is started only once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/db.php';

// Change 'user_id' to 'id' to match your login.php
if (isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];
    
    try {
        // Check user details
        $query = "
            SELECT users.name, users.role, user_verification.verification_status 
            FROM users
            LEFT JOIN user_verification ON users.id = user_verification.user_id
            WHERE users.id = :user_id
            LIMIT 1
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['verification_status'] === 'verified') {
            $username = $user['name'];
            $userRole = $user['role'];
        } else {
            $username = 'Guest';
            $userRole = 'renter';  // Default to renter if not verified
        }
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $username = 'Guest';
        $userRole = 'renter';
    }
} else {
    $username = 'Guest';
    $userRole = 'renter'; // Default if not logged in
}

// Handle the "Become a Renter" button click
if (isset($_POST['become_renter'])) {
    try {
        // Check if the user's verification status is 'verified'
        $query = "
            SELECT verification_status 
            FROM user_verification 
            WHERE user_id = :user_id
            LIMIT 1
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $verification = $stmt->fetch();

        // If verified, update the role to 'renter' and redirect to dashboard
        if ($verification && $verification['verification_status'] === 'verified') {
            $updateQuery = "
                UPDATE users 
                SET role = 'renter' 
                WHERE id = :user_id
            ";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute(['user_id' => $userId]);
            
            // Redirect to the renter dashboard
            header('Location: ../renter/browse.php');
            exit;
        } else {
            echo "<script>alert('Your verification is pending. Please complete the verification process to become a renter.');</script>";
        }
    } catch(PDOException $e) {
        error_log("Database Error in becoming renter: " . $e->getMessage());
        echo "<script>alert('An error occurred. Please try again later.');</script>";
    }
}
?>

<!-- HTML code remains the same with modifications below -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="includes/owner-style.css">
    <title>RentBox Dashboard</title>
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        .sidebar .active {
            background-color: #e0f7fa; /* Light blue background */
            color: #00695c; /* Dark green text */
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div id="overlay"></div>
    <div class="container-fluid">
        <div class="row">
            <!-- Header -->
            <div class="col-12 header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <!-- Sidebar Toggle Button for small screens -->
                    <button id="toggleSidebarBtn" class="btn btn-outline-secondary me-3 d-md-none" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <!-- Logo -->
                    <img src="/rb/owner/includes/logo.png" alt="RentBox Logo" class="logo" style="width: 50px; height: 50px; object-fit: contain;">
                    <h4 class="m-0">RentBox</h4>
                </div>
                <div class="d-flex align-items-center">
                    <!-- Messages Dropdown -->
                    <div class="dropdown me-3">
                        <a href="#" class="text-decoration-none d-flex align-items-center" data-bs-toggle="dropdown">
                            <i class="fas fa-comment fs-5 text-success"></i> <span class="ms-2">Messages</span>
                        </a>
                    </div>
                    <!-- Notifications Dropdown -->
                    <div class="dropdown me-3">
                        <a href="#" class="text-decoration-none d-flex align-items-center" data-bs-toggle="dropdown">
                            <i class="fas fa-bell fs-5"></i> <span class="ms-2">Notifications</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-3 shadow notification-drawer" style="width: 280px; height: 500px; overflow-y: auto;">
                            <div class="d-flex justify-content-between mb-3">
                                <a href="#" class="text-decoration-none fw-bold active-tab">All</a>
                                <a href="#" class="text-decoration-none ms-4 text-muted">Unread</a>
                            </div>
                            <li class="text-center mt-3"><a href="#" class="text-decoration-none text-primary fw-bold">View All Notifications</a></li>
                        </ul>
                    </div>
                    <!-- Profile Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="text-decoration-none d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="/rb/owner/includes/profile.jpg" alt="User Profile" class="profile-img">
                            <div class="d-flex flex-column align-items-start profile-details">
                                <span class="fw-bold"><?= htmlspecialchars($username) ?></span>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($userRole) ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a href="/rb/owner/manage-account-owner.php" class="dropdown-item">Profile</a></li>
                            <li><a href="#" class="dropdown-item">Settings</a></li>
                            <li><a href="#" class="dropdown-item text-danger">Log Out</a></li>
                        </ul>
                    </div>
                    <!-- Become a Renter Button -->
                    <button class="btn btn-primary ms-3" id="becomeRenterBtn">Become a Renter</button>
                </div>
            </div>
        </div>
        <div class="row">
            <!-- Sidebar -->
            <div id="sidebar" class="sidebar">
                <input type="text" class="form-control my-3" placeholder="Search">
                <a href="/rb/owner/dashboard.php" id="dashboardLink"><i class="fas fa-tachometer-alt me-2 text-success"></i> Dashboard</a>
                <a href="/rb/owner/gadget.php" id="gadgetLink"><i class="fas fa-tablet-alt me-2 text-success"></i> Gadgets</a>
                <a href="/rb/owner/rentals.php" id="rentalsLink"><i class="fas fa-sync-alt me-2 text-success"></i> Rentals</a>
                <a href="/rb/owner/messages.php" id="messagesLink"><i class="fas fa-comment-alt me-2 text-success"></i> Messages</a>
                <a href="/rb/owner/all-reports.php" id="reportsLink"><i class="fas fa-file-alt me-2 text-success"></i> All reports</a>
                <a href="/rb/owner/view-transaction.php" id="transactionsLink"><i class="fas fa-coins me-2 text-success"></i> Transactions </a>
                <a href="/rb/owner/gadgets_assessment.php" id="assessmentLink"><i class="fas fa-file-alt me-2 text-success"></i> Assess Gadgets</a>
                <a href="/rb/owner/logout.php" class="text-danger" id="logoutLink"><i class="fas fa-sign-out-alt me-2 text-success"></i> Log out</a>
            </div>
        </div>
    </div>

    <script>
        // Add active class dynamically
        const currentPath = window.location.pathname;
        const links = {
            "/rb/owner/dashboard.php": "dashboardLink",
            "/rb/owner/gadget.php": "gadgetLink",
            "/rb/owner/rentals.php": "rentalsLink",
            "/rb/owner/messages.php": "messagesLink",
            "/rb/owner/all-reports.php": "reportsLink",
            "/rb/owner/view-transaction.php": "transactionsLink",
            "/rb/owner/gadgets_assessment.php": "assessmentLink",
            "/rb/owner/logout.php": "logoutLink"
        };

        // Check for the current page and add the active class
        Object.keys(links).forEach((path) => {
            if (currentPath === path) {  // Ensure exact match
                document.getElementById(links[path]).classList.add("active");
            }
        });

        // Add the role change functionality for "Become a Renter" button
        document.getElementById('becomeRenterBtn').addEventListener('click', function(e) {
            e.preventDefault();

            // Send request to change role to 'renter'
            fetch('change_role.php', {
                method: 'POST',
                body: new URLSearchParams('role=renter')
            }).then(() => {
                window.location.href = '/rb/renter/browse.php'; // Redirect to renter browse page
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="includes/owner-js.js"></script>
</body>