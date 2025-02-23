<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentBox - Manage Account</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> <!-- Add custom styles if needed -->
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>
    

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
           
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <a href="#">Home</a>
            </li>
            <li class="breadcrumb-item">
              <a href="#">Profile</a>
            </li>
            <li aria-current="page" class="breadcrumb-item active">
             Manage Account
            </li>
          </ol>
                <h2 class="mt-4">Hello, Xavier</h2>
                <p>
                    From your account dashboard, you can easily check & view your
                    <a href="view-transaction.php">Recent Transactions</a>, manage your <a href="pickup-address.php">Pick-Up Addresses</a> and edit
                    your <a href="password-owner.php">Password</a> and <a href="profile.php">Account Details</a>.
                </p>

                <!-- Account Info and Pick-Up Address Section -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Account Info</span>
                                <a href="#" class="text-decoration-none">Edit</a>
                            </div>
                            <div class="card-body">
                                <p><img src="Images/profile.jpg" alt="User" class="rounded-circle" style="width: 40px;">
                                <strong>Gustavo Xavier</strong></p>
                                <p>Baliwasan, Zamboanga City</p>
                                <p>Email: xaviergustavo@gmail.com</p>
                                <p>Phone: 09912345678</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Pick-Up Address</span>
                                <a href="pickup-address.php" class="text-decoration-none">Edit</a>
                            </div>
                            <div class="card-body">
                                <p><img src="images/profile.jpg" alt="User" class="rounded-circle" style="width: 40px;">
                                <strong>Gustavo Xavier</strong></p>
                                <p>Johnston Drive, Baliwasan Road, Zamboanga City, Mindanao, Zamboanga Del Sur 7000</p>
                                <p>Phone: 09912345678</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banks and Online Wallet -->
                <div class="mt-4">
                    <h4>Banks and Online Wallet</h4>
                    <div class="d-flex gap-3">
                        <div class="card" style="width: 18rem;">
                            <div class="card-body">
                                <p class="text-muted">**** **** **** 3814</p>
                                <h5>VISA</h5>
                                <p>Gustavo Xavier</p>
                            </div>
                        </div>
                        <div class="card bg-success text-white" style="width: 18rem;">
                            <div class="card-body">
                                <p>**** **** **** 1761</p>
                                <h5>Mastercard</h5>
                                <p>Gustavo Xavier</p>
                            </div>
                        </div>
                        <a href="#" class="btn btn-outline-primary align-self-center">Add Card</a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
