<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar with Hover Effect</title>
    <!-- Add Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom styles for sidebar links */
        .sidebar-link {
            padding: 10px; /* Adds padding for better clickable area */
            border-radius: 5px; /* Optional: Adds rounded corners for hover effect */
            color: black; /* Ensures text is black by default */
            text-decoration: none; /* Removes underline from links */
            display: flex; /* Ensures the content is properly aligned */
            align-items: center; /* Aligns text and icon vertically */
            transition: background-color 0.3s, color 0.3s; /* Smooth transition */
        }

        /* Hover effect for sidebar links */
        .sidebar-link:hover {
            background-color: orange; /* Background color becomes orange on hover */
            color: white; /* Text color becomes white on hover */
        }

        /* Active state for sidebar links */
       

        /* Optional: To make the icon and text align properly */
        .nav-link img {
            vertical-align: middle; /* Aligns the icon vertically with the text */
            margin-right: 5px; /* Adds space between the icon and the text */
        }

        /* Profile section styling */
        .profile-section {
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-section img {
            border-radius: 50%;
            width: 60px;
            height: 60px;
        }
        
        .profile-section h5 {
            margin-top: 10px;
            font-size: 1.2rem;
        }
        
        .profile-section p {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Your Sidebar HTML Code goes here -->
    <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar mt-4">
        <div class="position-sticky">
            <!-- Profile Section -->
            <div class="profile-section">
                <img src="images/profile.jpg" alt="User" class="rounded-circle">
                <h5 class="mt-2">Gustavo Xavier</h5>
                <p class="text-muted mb-0">Owner</p>
            </div>

            <!-- Sidebar Links -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link sidebar-link active" href="manage-account-owner.php"> <!-- 'active' class is added here -->
                        <i class="bi bi-person"></i>
                        <img src="images/data.png" alt="My Profile" style="width: 15px; height: 15px;">
                        Manage Account
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link" href="profile.php">
                        <img src="images/user.png" alt="My Profile" style="width: 15px; height: 15px;">
                        My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link" href="view-transaction.php">
                        <img src="images/transaction.png" alt="My Profile" style="width: 15px; height: 15px;">
                        View Transactions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link" href="password-owner.php">
                        <img src="images/password.png" alt="My Profile" style="width: 15px; height: 15px;">
                        Change Password
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link" href="my-rent.php">
                    <img src="images/rent.png" alt="My Profile" style="width: 15px; height: 15px;">
                        My Rent
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Add Bootstrap JS (Optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
