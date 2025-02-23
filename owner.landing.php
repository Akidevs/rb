<?php

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Rentbox</title>
        <link rel="icon" type="image/png" href="images\rb logo white.png">
        <link href="vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="vendor/font/bootstrap-icons.css">
        <link rel="stylesheet" href="other.css">
        <style>
        * {
        font-family: 'Poppins', sans-serif;
        }
        .btn-light{
            color: #1F4529;
        }
        </style>
    </head>
    <body class="">
        <nav class="navbar navbar-expand-lg bg-body sticky-top pt-3 pb-3 shadow-sm" aria-label="navbar" id="">
            <div class="container-fluid d-flex justify-content-between">
                <a href="browse.php">
                    <img class="ms-5 my-auto mt-1" src="images\rb logo text colored.png" alt="Logo" height="50px">
                </a>
                <!-- links -->
                <div class="me-5 p-1 d-flex align-items-center">
                    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="renter/browse.php">Start Renting</a> 
                    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" style="--bs-icon-link-transform: translate3d(0, -.125rem, 0);" href="landing.owner.php" id="becomeOwnerLink">Become an Owner</a> 

                    <?php if (!isset($_SESSION['id'])): ?>
                    <div>
                        <a type="button" class="gradient btn rounded-4 px-4 py-2 shadow-sm" href="signup.php">Sign Up</a>
                    </div>
                    <?php else: ?>
                    <div>
                        <a href="login.php" class="gradient btn rounded-4 px-4 py-2 shadow-sm" href="login.php">Log In</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <main class="container-fluid">
            <div class="gradient d-flex flex-column align-items-center p-5">
                <h1 class="display1 fw-bold">Start earning from your gadgets now.</h1>
                <h5 class="text-center mt-3">Join the Rentbox community. We connect gadget owners <br> with tech enthusiasts. Earn extra income or access the latest gadgets<br> – it's a win-win.</h5>
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light rounded-5 mt-3 shadow-sm px-3 py-2">Get Started</button>
                    <button type="button" class="btn btn-light rounded-5 mt-3 shadow-sm px-3 py-2">How it Works
                        <i class="bi bi-play-fill"></i>
                    </button>
                </div>
            </div>
            <div class="p-5 d-flex flex-column align-items-center">
                <h1 class="display1 fw-bold text-center mb-3">How It Works</h1> 
                <div class="border border-success" style="width: 70%; height: 50%;">
                    video
                </div>       
            </div>
        </main>
        
        <footer class="container-fluid">
            <div class="row m-5">
                <div class="col">
                    <img class="mb-2" src="images/rb logo text colored.png" alt="" height="40px;">
                    <p class="mb-2">Rentbox is a company registered in the Philippines with Company Reg. No. CS2024123456</>
                    
                    <div class="me-5 mb-3">
                        <div class="d-flex align-items-center justify-content-between me-5 gap-4 bg-dark px-2 rounded">
                            <i class="bi bi-facebook text-white fs-4"></i>
                            <i class="bi bi-youtube text-white fs-4"></i>
                            <i class="bi bi-tiktok text-white fs-4"></i>
                            <i class="bi bi-instagram text-white fs-4"></i>
                            <i class="bi bi-linkedin text-white fs-4"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-success">bank</span>         
                        <span class="badge text-bg-success">bank</span>         
                        <span class="badge text-bg-success">bank</span>         
                        <span class="badge text-bg-success">bank</span>         
                        <span class="badge text-bg-success">bank</span>         
                    </div>
                </div>

                <div class="col d-flex flex-column a">
                    <h5 class="fw-bold text-success mb-3">For Owners</h5>
                    <a href="" class="text-decoration-none text-secondary mb-2">Become an Owner</a>
                    <a href="" class="text-decoration-none text-secondary">Owner Dashboard</a>
                </div>
                
                <div class="col d-flex flex-column a">
                    <h5 class="fw-bold text-success mb-3">For Renters</h5>
                    <a href="" class="text-decoration-none text-secondary mb-2">Create an Account</a>
                    <a href="" class="text-decoration-none text-secondary mb-2">Browse for Gadgets</a>
                    <a href="" class="text-decoration-none text-secondary mb-2">Start Renting</a>
                </div>   

                <div class="col d-flex flex-column a">
                    <h5 class="fw-bold text-success mb-3">Abount Rentbox</h5>
                    <a href="" class="text-decoration-none text-secondary mb-2">About Rentbox</a>
                    <a href="" class="text-decoration-none text-secondary mb-2">Help Center</a>
                    <a href="" class="text-decoration-none text-secondary mb-2">Terms and Conditions</a>
                    <a href="" class="text-decoration-none text-secondary mb-2">Privacy Policy</a>
                    <a href="" class="text-decoration-none text-secondary mb-2">Contact Us</a>
                </div>
            </div>
        </footer>

                     
    </body>
<script src="vendor\bootstrap-5.3.3\dist\js\bootstrap.bundle.min.js"></script>
<script>
if (window.location.pathname.includes('owner.landing.php')) {
        // Get the link element
        var becomeOwnerLink = document.getElementById('becomeOwnerLink');
        // Add the Bootstrap 'disabled' class
        becomeOwnerLink.classList.add('disabled');
        // Optionally, you can also prevent the link from being clickable
        becomeOwnerLink.setAttribute('href', '#');
        becomeOwnerLink.style.pointerEvents = 'none'; // Disable pointer events
    }
</script>
</html>