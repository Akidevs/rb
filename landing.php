
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
                <?php
// Check if the user is logged in
if (!isset($_SESSION['user_id'])):
?>
    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="login.php?redirect=start_renting">Start Renting</a> 
    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="login.php?redirect=become_owner">Become an Owner</a>
<?php else: ?>
    <!-- If logged in, show actual links -->
    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="renter/browse.php">Start Renting</a>
    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="landing.owner.php">Become an Owner</a>
<?php endif; ?>

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
            <div class="container mt-5">
                <div class="row p-4 pb-0 pe-lg-0 pt-lg-5 align-items-center rounded-5 border shadow-lg">
                    <div class="col-lg-6 p-3 p-lg-5 pt-lg-3">
                        <h1 class="display-3 fw-bold lh-1 text-body-emphasis pb-3">Your Gadgets,<br> Your Way</h1>
                        <p class="lead">
                        Join the Rentbox community. Earn extra income or access the latest gadgets – it's a win-win.
                        </p>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mb-4 mb-lg-3 mt-4">
                            <a type="button" class="btn btn-success btn-md px-4 me-md-2 rounded-4" href="signup.php">Start Renting</a>
                            <button type="button" class="btn btn-success btn-md px-4 me-md-2 rounded-4">List Your Gadget</button>
                        </div>
                    </div>
                    <div class="col-lg-4 p-0">
                        <img class="rounded-lg-3" src="images/landing.images/hero.png" alt="image" width="500">
                    </div>
                </div>
            </div>
            
            <div class="divider my-5 mx-0"></div>

            <div class="container-fluid bg-body">
                <div class="row px-4">
                    <div class="col-8 bg-body p-5">
                        <h3 class="fw-bold mb-3">Rent Gadgets</h3>
                        <p>Experience the latest technology without the long-term commitment. <br> Rent the gadgets you need, when you need them.</p>

                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-tags-fill fs-1 text-success me-2"></i>
                                    <h5 class="fw-bold">Know the price upfront</h5>
                                </div>
                                <p>Our transparent pricing means you know exactly how much you'll pay.</p>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-chat-heart-fill fs-1 text-success me-2"></i>
                                    <h5 class="fw-bold">Instant chat</h5>
                                </div>
                                <p>Instantly chat with Owners and stay in touch throughout the whole transaction</p>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-credit-card-2-front-fill fs-1 text-success me-2"></i>
                                    <h5 class="fw-bold">Payment propaymeection, guaranteed</h5>
                                </div>
                                <p>Your money is held safely until the rental period is completed.</p>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-headset fs-1 text-success me-2"></i>
                                    <h5 class="fw-bold">We’re here for you</h5>
                                </div>
                                <p>Rentbox Team is here for you, anything from answering any questions to resolving any issues anytime.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-4 shadow">
                        <img class="border object-fit-contain" src="" alt="cart image" style="width: 70%; height: 50%;">
                        <h5 class="fw-bold">You’re safe with us</h5>
                        <p class="m-0 p-0">Rentbox is designed to protect you throughout the rental process. With all discussions taking place on our platform, we secure your payments, and your information remains confidential at all times.</p>
                        <small class="m-0 p-0"><a href="" class="link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-10-hover">Learn more about security</a></small>   
                    </div>
                </div>
            </div>

            <div class="divider mt-5"></div>  

            <div class="gradient container-fluid pt-5">
                <h1 class="display-4 fw-bold text-center m-0 p-0" style="text-shadow: 2px 2px 4px #000000;">We’re connected to the <br>digital community</h1>
                <div class="row p-5">
                    <div class="col-4">
                        <div class="container">
                            <div class="rounded-5 d-flex align-items-end shadow-lg thumb" style="width:auto; height: 80vh; background-image: url('images/landing.images/story (1).png'); background-size: contain; background-position: center;">
                                <div class="container-fluid px-3 pt-5 pb-3 rounded-bottom-5 " style="background: linear-gradient(180deg, rgba(0,0,0,0.5046393557422969) 0%, rgba(0,0,0,0.927608543417367) 0%, rgba(0,0,0,0) 0%, rgba(0,0,0,0.6306897759103641) 36%, rgba(0,0,0,0.7679446778711485) 63%, rgba(0,0,0,0.8743872549019608) 100%);">
                                    <h6 class="">Francis Mercer is a certified owner influencer. Watch our collab!</h6>
                                    <div class="d-flex align-items-center">
                                        <img class="rounded-circle border me-2 object-fit-cover" src="" alt="pfp" style="width: 40px; height: 40px;">
                                        <small>University Digest</small>
                                    </div>
                                </div>
                            </div>f
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="container">
                            <div class="rounded-5 d-flex align-items-end shadow-lg  thumb" style="width:auto; height: 80vh; background-image: url('images/landing.images/story (2).png'); background-size: contain; background-position: center;">
                                <div class="container-fluid px-3 pt-5 pb-3 rounded-bottom-5" style="background: linear-gradient(180deg, rgba(0,0,0,0.5046393557422969) 0%, rgba(0,0,0,0.927608543417367) 0%, rgba(0,0,0,0) 0%, rgba(0,0,0,0.6306897759103641) 36%, rgba(0,0,0,0.7679446778711485) 63%, rgba(0,0,0,0.8743872549019608) 100%);">
                                    <h6 class="">We reached 1, 000 Users!</h6>
                                    <div class="d-flex align-items-center">
                                        <img class="rounded-circle border me-2 object-fit-cover" src="" alt="pfp" style="width: 40px; height: 40px;">
                                        <small>Zamboanga Today</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="container">
                            <div class="rounded-5 d-flex align-items-end shadow-lg  thumb" style="width:auto; height: 80vh; background-image: url('images/landing.images/story (3).png'); background-size: contain; background-position: center;">
                                <div class="container-fluid px-3 pt-5 pb-3 rounded-bottom-5" style="background: linear-gradient(180deg, rgba(0,0,0,0.5046393557422969) 0%, rgba(0,0,0,0.927608543417367) 0%, rgba(0,0,0,0) 0%, rgba(0,0,0,0.6306897759103641) 36%, rgba(0,0,0,0.7679446778711485) 63%, rgba(0,0,0,0.8743872549019608) 100%);">
                                    <h6 class="">Join our free webinar with our experienced professionals.</h6>
                                    <div class="d-flex align-items-center">
                                        <img class="rounded-circle border me-2 object-fit-cover" src="" alt="pfp" style="width: 40px; height: 40px;">
                                        <small>Rentbox</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="divider"></div> 
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

</script>
</html>