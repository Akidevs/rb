<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RentBox - Manage Account</title>
  <!-- Link to Bootstrap 5.3.2 CSS -->
  <link crossorigin="anonymous" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" rel="stylesheet"/>
  <!-- Font Awesome for Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
   
  </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container-fluid">
<div class="row"> 

  <?php include 'sidebar.php'; ?>
      <!-- Main Content -->
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
             Change Password
            </li>
          </ol>
        </nav>
        <h2>Change Password</h2>
        <form>
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <input class="form-control" id="currentPassword" placeholder="Current Password" type="password"/>
          </div>
          <div class="form-group">
            <label for="newPassword">New Password</label>
            <input class="form-control" id="newPassword" placeholder="New Password" type="password"/>
          </div>
          <div class="form-group">
            <label for="confirmNewPassword">Confirm New Password</label>
            <input class="form-control" id="confirmNewPassword" placeholder="Confirm New Password" type="password"/>
          </div>
          <button class="btn btn-primary" type="submit">Save Changes</button>
        </form>
      </main>
    </div>
  </div>
  </div>  
</div>

  <!-- Bootstrap JS and Popper -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.2/js/bootstrap.min.js"></script>
</body>
</html>
