<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Address Editor</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Include Header -->
    <?php include 'header.php'; ?>

<div class="container-fluid">
<div class="row"> 

  <?php include 'sidebar.php'; ?>
  <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <a href="#">Home</a>
            </li>
            <li class="breadcrumb-item">
              <a href="#">Profile</a>
            </li>
            <li aria-current="page" class="breadcrumb-item active">
             Pick-up Address            </li>
          </ol>
        </nav>
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <h3 class="mt-4">Pickup Address</h3>

                <!-- Form for Adding/Editing Address -->
                <div class="card my-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add/Edit Pickup Address</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="addressName" class="form-label">Address Name</label>
                                <input type="text" name="address_name" id="addressName" class="form-control" placeholder="e.g., Home, Office" required>
                            </div>
                            <div class="mb-3">
                                <label for="street" class="form-label">Street</label>
                                <input type="text" name="street" id="street" class="form-control" placeholder="Street name and number" required>
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" name="city" id="city" class="form-control" placeholder="City" required>
                            </div>
                            <div class="mb-3">
                                <label for="postalCode" class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" id="postalCode" class="form-control" placeholder="Postal Code" required>
                            </div>
                            <div class="mb-3">
                                <label for="contactNumber" class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" id="contactNumber" class="form-control" placeholder="Contact Number" required>
                            </div>
                            <button type="submit" name="save_address" class="btn btn-primary">Save Address</button>
                        </form>
                    </div>
                </div>

                <!-- List of Saved Addresses -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Saved Pickup Addresses</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th scope="col">Address Name</th>
                                    <th scope="col">Details</th>
                                    <th scope="col">Contact</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Placeholder: Replace with database query
                                $pickup_addresses = [
                                    [
                                        "id" => 1,
                                        "name" => "Home",
                                        "details" => "123 Main St, Springfield, 12345",
                                        "contact" => "09123456789",
                                    ],
                                    [
                                        "id" => 2,
                                        "name" => "Office",
                                        "details" => "456 Elm St, Metropolis, 54321",
                                        "contact" => "09876543210",
                                    ],
                                ];

                                if (!empty($pickup_addresses)) {
                                    foreach ($pickup_addresses as $address) {
                                        echo "<tr>
                                            <td>{$address['name']}</td>
                                            <td>{$address['details']}</td>
                                            <td>{$address['contact']}</td>
                                            <td>
                                                <a href='edit-address.php?id={$address['id']}' class='btn btn-sm btn-warning'>Edit</a>
                                                <a href='delete-address.php?id={$address['id']}' class='btn btn-sm btn-danger'>Delete</a>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>No addresses saved yet.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
