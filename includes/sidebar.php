<?php
// Database connection
$servername = "localhost";
$username = "root"; // Update with your database username
$password = ""; // Update with your database password
$dbname = "PROJECT"; // Use your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<style>
.btn-outline-secondary.active {
    background-color: #6c757d !important;
    color: white !important;
    border-color: #6c757d !important;
}
</style>

<!-- sidebar.php -->
<div class="col-md-3 pt-3 bg-body">
    <div class="p-3">
        <p class="fs-5 fw-bold mb-2">Categories</p>
        <div>
            <button class="btn btn-outline-success fs-6 fw-bold mb-2 ms-2 border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
            <i class="bi bi-gift me-1"></i>All Gadgets</button>
            <!-- filter -->
            <div class="collapse ps-3" id="collapse1">
                <div class="d-flex align-items-start flex-column gap-1">
                    <?php
                    // List of all categories, even those without products
                    $categories = [
                        'Mobile Phones',
                        'Laptops',
                        'Tablets',
                        'Cameras',
                        'Accessories',
                        'Gaming Consoles',
                        'Audio Devices',
                        'Drones'
                    ];

                    // Loop through all categories and display checkboxes
                    foreach ($categories as $category) {
                        echo '<input type="radio" name="categoryFilter" class="btn-check" id="btn-check-' . $category . '" autocomplete="off" onclick="filterCategory(\'' . $category . '\')">';
                        echo '<label class="btn btn-outline-secondary" for="btn-check-' . $category . '">' . $category . '</label>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <input type="checkbox" class="btn-check" id="btn-check-7" autocomplete="off">
        <label class="btn btn-outline-success fs-6 fw-bold mb-2 ms-2 border-0" for="btn-check-7"><i class="bi bi-bag me-1"></i>Newly Posted</label>

        <input type="checkbox" class="btn-check" id="btn-check-8" autocomplete="off">
        <label class="btn btn-outline-success fs-6 fw-bold mb-2 ms-2 border-0" for="btn-check-8"><i class="bi bi-stars me-1"></i>Top Ratings</label>

        <input type="checkbox" class="btn-check" id="btn-check-9" autocomplete="off">
        <label class="btn btn-outline-success fs-6 fw-bold mb-2 ms-2 border-0" for="btn-check-9"><i class="bi bi-percent me-1"></i>On Discount</label>
        <br>
        <input type="checkbox" class="btn-check" id="btn-check-10" autocomplete="off">
        <label class="btn btn-outline-success fs-6 fw-bold mb-2 ms-2 border-0" for="btn-check-10"><i class="bi bi-plus me-1"></i>Others</label>
    </div>
</div>

<script>
// Track active category and abort controller
let activeCategory = null;
let abortController = null;
const categoryButtons = {};

function filterCategory(category) {
    // Cancel previous request if any
    if (abortController) {
        abortController.abort();
    }

    // Update active state
    if (activeCategory) {
        categoryButtons[activeCategory].classList.remove('active');
    }
    activeCategory = category;
    categoryButtons[category].classList.add('active');

    // Create new abort controller
    abortController = new AbortController();
    
    // Store current category for validation
    const currentRequestCategory = category;

    // Loading state
    const productList = document.getElementById('product-list');
    productList.innerHTML = `
        <div class="mb-3 mt-0 container rounded-start-3 bg-body-secondary">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" id="dynamic-products">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    fetch(`fetch_products.php?category=${encodeURIComponent(category)}`, {
        signal: abortController.signal
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        // Only update if still the active category
        if (activeCategory !== currentRequestCategory) return;

        const dynamicProducts = document.getElementById('dynamic-products');
        dynamicProducts.innerHTML = '';

        if (data.error) {
            productList.innerHTML = `
                <div class="alert alert-danger m-3">${data.error}</div>
            `;
            return;
        }

        if (data.length > 0) {
            data.forEach(product => {
                const productHtml = `
                    <div class="col">
                        <div class="border rounded-3 p-3 bg-body hover-effect">
                            <a href="item.php?id=${product.id}" class="text-decoration-none text-dark">
                                <img src="../img/uploads/${product.image}" 
                                     alt="${product.name}" 
                                     class="img-thumbnail shadow-sm product-image">
                                <p class="fs-5 mt-2 ms-2 mb-0 fw-bold">${product.name}</p>
                            </a>
                            <div class="d-flex justify-content-between align-items-baseline">
                                <small class="ms-1 mb-0 text-secondary">
                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                    ${product.average_rating} (${product.rating_count})
                                </small>
                                <p class="fs-5 ms-auto mb-0">₱${product.rental_price}<small class="text-secondary">/day</small></p>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <form class="add-to-cart-form">
                                    <input type="hidden" name="product_id" value="${product.id}">
                                    <button type="submit" class="btn btn-outline-dark btn-sm rounded-5 shadow-sm">
                                        Add to Cart
                                    </button>
                                </form>
                                <a href="item.php?id=${product.id}" class="btn btn-success btn-sm rounded-5 shadow">
                                    Rent Now
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                dynamicProducts.insertAdjacentHTML('beforeend', productHtml);
            });
        } else {
            productList.innerHTML = `
                <div class="w-100 text-center py-5">
                    <i class="bi bi-box2-heart fs-1 text-muted"></i>
                    <p class="mt-3">No products found in ${category} category</p>
                </div>
            `;
        }
    })
    .catch(error => {
        if (error.name === 'AbortError') {
            console.log('Request aborted');
            return;
        }
        productList.innerHTML = `
            <div class="alert alert-danger m-3">Error loading products: ${error.message}</div>
        `;
        console.error('Fetch error:', error);
    });
}

// Initialize category buttons
document.addEventListener('DOMContentLoaded', () => {
    <?php
    foreach ($categories as $category) {
        echo "categoryButtons['{$category}'] = document.querySelector('#btn-check-{$category} + label');\n";
    }
    ?>
});

// AJAX form submission
document.addEventListener('submit', async (e) => {
    if (e.target.closest('.add-to-cart-form')) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                const toast = bootstrap.Toast.getOrCreateInstance(document.getElementById('cartToast'));
                toast.show();
            } else {
                alert('Error adding to cart: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to add to cart');
        }
    }

    document.addEventListener('submit', async (e) => {
    if (e.target.closest('.add-to-cart-form')) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        try {
            const response = await fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                // Show a success message using a toast (you can customize this to your preference)
                const toast = bootstrap.Toast.getOrCreateInstance(document.getElementById('cartToast'));
                toast.show();
            } else {
                // Show an error message if the product is already in the cart or any other error
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to add to cart');
        }
    }
});
});
</script>