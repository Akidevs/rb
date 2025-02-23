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
            SELECT users.name, user_verification.verification_status 
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
        } else {
            $username = 'Guest';
        }
    } catch(PDOException $e) {
        error_log("Database Error in navbar: " . $e->getMessage());
        $username = 'Guest';
    }
} else {
    $username = 'Guest';
}

// Handle the "Become an Owner" button click
if (isset($_POST['become_owner'])) {
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

        // If verified, update the role to 'owner' and redirect to dashboard
        if ($verification && $verification['verification_status'] === 'verified') {
            $updateQuery = "
                UPDATE users 
                SET role = 'owner' 
                WHERE id = :user_id
            ";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute(['user_id' => $userId]);
            
            // Redirect to the owner dashboard
            header('Location: ../owner/dashboard.php');
            exit;
        } else {
            echo "<script>alert('Your verification is pending. Please complete the verification process to become an owner.');</script>";
        }
    } catch(PDOException $e) {
        error_log("Database Error in becoming owner: " . $e->getMessage());
        echo "<script>alert('An error occurred. Please try again later.');</script>";
    }
}
?>

<!-- Rest of your HTML code remains the same -->

<!-- HTML for navbar with username dynamically displayed -->
<div class="container bg-body rounded-bottom-5 d-flex mb-5 py-3 shadow">
    <a href="browse.php">
        <img class="ms-5 my-4" src="../images/rb logo text colored.png" alt="Logo" height="50px">
    </a>
    <div class="my-auto mx-auto d-flex gap-3">
        <a href="browse.php" class="fs-5 text-decoration-none fw-bold active">Browse</a>
        <a href="#" class="secondary fs-5 text-decoration-none fw-bold" id="toggleRoleButton" data-bs-toggle="modal" data-bs-target="#becomeOwnerModal">Become an Owner</a>
    </div>
    <div class="d-flex me-5 align-items-center gap-3">
        <button type="button" class="success btn btn-outline-success rounded-circle"><i class="bi bi-search fs-5"></i></button>
        <a href="../renter/cart.php">
            <button type="button" class="success btn btn-outline-success rounded-circle">
                <i class="bi bi-basket3 fs-5"></i>
            </button>
        </a>

        <!-- Dropdown for logged-in user -->
        <div class="dropdown">
            <button class="success btn btn-outline-success rounded-circle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end mb-0 rounded-bottom-3 shadow border border-0" aria-labelledby="userDropdown">
                <!-- Display username above the profile -->
                <li class="dropdown-item pe-5 text-muted">Hello, <?= htmlspecialchars($username) ?></li>
                <li><a class="dropdown-item pe-5" href="profile.php">Profile</a></li>
                <li><a class="dropdown-item pe-5" href="message.php">Messages</a></li>
                <li><a class="dropdown-item pe-5" href="rentals.php">Rentals</a></li>
                <hr class="dropdown-divider">
                <li><a class="dropdown-item pe-5" href="supports.php">Supports</a></li>
                <li><a class="dropdown-item pe-5" href="file_dispute.php">File Dispute</a></li>
                <hr class="dropdown-divider">
                <li><a class="dropdown-item text-danger fw-bold pe-5" href="../includes/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Modal for confirmation of switching to Owner mode -->
<div class="modal fade" id="becomeOwnerModal" tabindex="-1" aria-labelledby="becomeOwnerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="becomeOwnerModalLabel">Become an Owner</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to switch to Owner mode?</p>
        <p>If you are not verified, you won't be able to access Owner features.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="">
            <button type="submit" name="become_owner" class="btn btn-primary">Switch to Owner</button>
        </form>
      </div>
    </div>
  </div>
</div>