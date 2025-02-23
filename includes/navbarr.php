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
            SELECT users.name, users.role, users.profile_picture, user_verification.verification_status 
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
            
            // Check if profile picture exists
            if ($user['profile_picture'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/rb/' . $user['profile_picture'])) {
                $profilePic = '/rb/' . $user['profile_picture']; // Correct path to image
            } else {
                $profilePic = '/rb/owner/includes/user.png'; // Default profile picture
            }
        } else {
            $username = 'Guest';
            $userRole = 'renter';  // Default to renter if not verified
            $profilePic = '/rb/owner/includes/user.png'; // Default profile picture
        }
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $username = 'Guest';
        $userRole = 'renter';
        $profilePic = '/rb/owner/includes/user.png'; // Default profile picture in case of error
    }
} else {
    $username = 'Guest';
    $userRole = 'renter'; // Default if not logged in
    $profilePic = '/rb/owner/includes/user.png'; // Default profile picture if not logged in
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



        <!-- IF LOGGED IN  -->
        <div class="dropdown-center">
            <button type="button" class="success btn btn-outline-success rounded-circle m-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                <img src="<?= htmlspecialchars($profilePic) ?>" class="object-fit-fill border rounded-circle" alt="pfp" style="width:50px; height: 50px;">
            </button>
            <ul class="dropdown-menu rounded-4">
                <li>
                    <p class="dropdown-item-text fw-bold m-0"><?= htmlspecialchars($username) ?></p>
                </li>
                <hr class="m-0 p-0">
                <li class="my-1"><a class="dropdown-item" href="profile.php"><i class="bi bi-gear-fill me-2"></i>Profile</a></li>
                <li class="my-1"><a class="dropdown-item" href="message.php"><i class="bi bi-envelope-fill me-2"></i>Messages</a></li>
                <li class="my-1"><a class="dropdown-item" href="rentals.php"><i class="bi bi-box2-heart-fill me-2"></i>Rentals</a></li>
                <hr class="m-0 p-0">
                <li class="my-1"><a class="dropdown-item" href="supports.php"><i class="bi bi-headset me-2"></i>Supports</a></li>
                <li class="my-1"><a class="dropdown-item" href="file_dispute.php"><i class="bi bi-file-earmark-x-fill me-2"></i>File Dispute</a></li>
                <hr class="m-0 p-0">
                <li class="my-1"><a class="dropdown-item" href="../includes/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
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