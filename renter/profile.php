<?php
session_start();
require_once '../db/db.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit();
}

// Initialize error and success messages
$error = null;
$success = null;

// Handle role switch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['switch_role'])) {
    try {
        // Get current user role
        $roleQuery = "SELECT role FROM users WHERE id = :user_id";
        $roleStmt = $conn->prepare($roleQuery);
        $roleStmt->execute(['user_id' => $_SESSION['id']]);
        $currentRole = $roleStmt->fetchColumn();

        // Switch role
        $newRole = ($currentRole === 'renter') ? 'owner' : 'renter';
        
        $updateQuery = "UPDATE users SET role = :new_role WHERE id = :user_id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([
            'new_role' => $newRole,
            'user_id' => $_SESSION['id']
        ]);

        $_SESSION['role'] = $newRole;
        $success = "Successfully switched to " . ucfirst($newRole) . " role!";
        
        // Redirect to appropriate dashboard
        if ($newRole === 'owner') {
            header('Location: ../owner/dashboard.php');
        } else {
            header('Location: ../renter/browse.php');
        }
        exit();
    } catch(Exception $e) {
        error_log("Error switching role: " . $e->getMessage());
        $error = "Failed to switch role. Please try again.";
    }
}

try {
    // Get basic user data
    $userQuery = "
        SELECT u.id, u.name, u.email, u.role, u.created_at, u.profile_picture
        FROM users u 
        WHERE u.id = :user_id
    ";
    
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute(['user_id' => $_SESSION['id']]);
    $user = $userStmt->fetch();

    if (!$user) {
        throw new Exception('User not found');
    }

    // Get verification data
    $verificationQuery = "
        SELECT *
        FROM user_verification
        WHERE user_id = :user_id
    ";
    
    $verificationStmt = $conn->prepare($verificationQuery);
    $verificationStmt->execute(['user_id' => $_SESSION['id']]);
    $verification = $verificationStmt->fetch();

    // Combine user and verification data
    $userData = array_merge(
        $user,
        $verification ? $verification : []
    );

    // Handle profile picture update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowedTypes)) {
            $uploadDir = '../uploads/profile_pictures/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $filename = time() . '_' . $file['name'];
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $updateQuery = "UPDATE users SET profile_picture = :picture WHERE id = :user_id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([
                    'picture' => 'uploads/profile_pictures/' . $filename,
                    'user_id' => $_SESSION['id']
                ]);
                
                $userData['profile_picture'] = 'uploads/profile_pictures/' . $filename;
                $success = "Profile picture updated successfully!";
            }
        }
    }
} catch(Exception $e) {
    error_log("Error in profile.php: " . $e->getMessage());
    $error = "An error occurred while loading your profile.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Rentbox</title>
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
        }
        .verification-badge {
            background-color: #198754;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }
        .info-card {
            transition: transform 0.2s;
        }
        .info-card:hover {
            transform: translateY(-5px);
        }
        .role-switch-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/navbarr.php'; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger m-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success m-3">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="profile-header text-center position-relative">
        <!-- Role Switch Button -->
        <form method="POST" class="role-switch-btn">
            <input type="hidden" name="switch_role" value="1">
        </form>

        <div class="position-relative d-inline-block mb-3">
            <img src="<?= isset($userData['profile_picture']) && $userData['profile_picture'] ? '../' . $userData['profile_picture'] : '../images/default-profile.png' ?>" 
                 alt="Profile Picture" 
                 class="profile-picture shadow">
            <label for="profile-upload" class="position-absolute bottom-0 end-0 bg-white rounded-circle p-2 shadow cursor-pointer">
                <i class="bi bi-camera text-success"></i>
                <input type="file" id="profile-upload" name="profile_picture" class="d-none" 
                       form="profile-picture-form" accept="image/*">
            </label>
        </div>
        <h2 class="mb-2"><?= htmlspecialchars($userData['name'] ?? 'User') ?></h2>
        <div class="d-flex justify-content-center gap-2">
            <?php if(isset($userData['verification_status']) && $userData['verification_status'] === 'verified'): ?>
                <span class="verification-badge">
                    <i class="bi bi-check-circle-fill me-1"></i>Verified Account
                </span>
            <?php endif; ?>
            <span class="verification-badge">
                <?= ucfirst(htmlspecialchars($userData['role'] ?? 'User')) ?> Account
            </span>
        </div>
    </div>

    <div class="container mb-5">
        <form id="profile-picture-form" method="POST" enctype="multipart/form-data"></form>

        <div class="row g-4">
            <!-- Personal Information -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm info-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-fill me-2"></i>Personal Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Email:</strong> <?= htmlspecialchars($userData['email'] ?? 'Not provided') ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Mobile:</strong> <?= htmlspecialchars($userData['mobile_number'] ?? 'Not provided') ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Current Role:</strong> <?= ucfirst(htmlspecialchars($userData['role'] ?? 'User')) ?>
                            </li>
                            <li class="list-group-item">
                                <strong>Member Since:</strong> 
                                <?= isset($userData['created_at']) ? date('F j, Y', strtotime($userData['created_at'])) : 'Not available' ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Verification Information -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm info-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-shield-check me-2"></i>Verification Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <strong>Status:</strong> 
                                <span class="badge <?= (isset($userData['verification_status']) && $userData['verification_status'] === 'verified') ? 'bg-success' : 'bg-warning' ?>">
                                    <?= ucfirst(htmlspecialchars($userData['verification_status'] ?? 'Pending')) ?>
                                </span>
                            </li>
                            <?php if(isset($userData['verification_status']) && $userData['verification_status'] === 'verified'): ?>
                                <li class="list-group-item">
                                    <strong>Co-signee Name:</strong> 
                                    <?= htmlspecialchars(
                                        ($userData['cosignee_first_name'] ?? '') . ' ' . 
                                        ($userData['cosignee_last_name'] ?? '')
                                    ) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Co-signee Email:</strong> 
                                    <?= htmlspecialchars($userData['cosignee_email'] ?? 'Not provided') ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Relationship:</strong> 
                                    <?= htmlspecialchars($userData['cosignee_relationship'] ?? 'Not provided') ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle profile picture upload
        document.getElementById('profile-upload').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('profile-picture-form').submit();
            }
        });
    </script>
</body>
</html>