<?php
//sidebar.php
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

.pagination {
    position: relative;
    z-index: 10; /* Ensure it's on top of other elements */
}

</style>

<!-- sidebar.php -->
<div class="col-md-3 pt-3 bg-body">
    <div class="p-3">
        <p class="fs-5 fw-bold mb-2">Categories</p>
        <div>
            <button class="btn btn-outline-success fs-6 fw-bold mb-2 ms-2 border-0" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#collapse1" 
                    aria-expanded="false" aria-controls="collapse1">
                <i class="bi bi-gift me-1"></i>All Gadgets
            </button>
            <div class="collapse ps-3" id="collapse1">
                <div class="d-flex align-items-start flex-column gap-1">
                    <?php
                    $categories = [
                        'Mobile Phones', 'Laptops', 'Tablets', 'Cameras',
                        'Accessories', 'Gaming Consoles', 'Audio Devices', 'Drones'
                    ];
                    foreach ($categories as $category) {
                        echo '<input type="checkbox" class="btn-check" 
                               id="btn-check-'.$category.'" autocomplete="off" 
                               onclick="filterCategory(\''.$category.'\')">';
                        echo '<label class="btn btn-outline-secondary mb-1" 
                               for="btn-check-'.$category.'">'.$category.'</label>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <button class="btn btn-outline-success fs-6 fw-bold mb-2 ms-2 border-0" 
                data-sort="newest" 
                onclick="updateSort('newest')">
            <i class="bi bi-bag me-1"></i>Newly Posted
        </button>
        <button class="btn btn-outline-success fs-6 fw-bold mb-2 ms-2 border-0" 
                data-sort="top_rated" 
                onclick="updateSort('top_rated')">
            <i class="bi bi-stars me-1"></i>Top Ratings
        </button>
    </div>
</div>

<script>
// Global variables
let currentPage = 1;
let currentSort = 'newest';
let selectedCategories = [];
let isLoading = false;

// DOM Elements
const elements = {
    productList: null,
    dynamicProducts: null,
    pagination: null,
    pageInfo: null
};

// Initialize DOM elements
function initializeElements() {
    elements.productList = document.getElementById('product-list');
    elements.dynamicProducts = document.getElementById('dynamic-products');
    elements.pagination = document.querySelector('.pagination');
    elements.pageInfo = document.getElementById('page-info');
    
    if (!elements.productList) {
        console.error('Product list element not found');
        return false;
    }
    return true;
}

// Loading indicator
function showLoading() {
    if (!elements.productList) return;
    elements.productList.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;
}

// Main products update function
async function updateProducts() {
    if (isLoading || !initializeElements()) return;
    
    isLoading = true;
    showLoading();

    try {
        // Only include categories if there are any selected
        const queryParams = new URLSearchParams({
            page: currentPage,
            sort: currentSort
        });
        
        if (selectedCategories.length > 0) {
            queryParams.append('categories', selectedCategories.join(','));
        }

        const response = await fetch(`fetch_products.php?${queryParams.toString()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }

        updateProductList(data.products);
        updatePagination(data.pagination);
        
    } catch (error) {
        console.error('Fetch error:', error);
        if (elements.productList) {
            elements.productList.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error loading products: ${error.message}
                </div>`;
        }
    } finally {
        isLoading = false;
    }
}

// Update product list display
function updateProductList(products) {
    if (!elements.productList) return;

    const container = document.createElement('div');
    container.className = 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3';
    container.id = 'dynamic-products';

    if (!products || products.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-box2-heart fs-1 text-muted"></i>
                <p class="mt-3">No products found</p>
            </div>`;
        } else {
        products.forEach(product => {
            container.insertAdjacentHTML('beforeend', `
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
                            <form action="add_to_cart.php" method="POST">
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
                </div>`);
        });
    }

    elements.productList.innerHTML = '';
    elements.productList.appendChild(container);
}
// Update pagination display
function updatePagination(paginationData) {
    if (!elements.pagination || !elements.pageInfo) return;

    const { total_pages: totalPages, current_page: currentPage } = paginationData;
    
    // Don't show pagination if there's only one page
    if (totalPages <= 1) {
        elements.pagination.innerHTML = '';
        elements.pageInfo.textContent = '';
        return;
    }

    // Previous button
    let paginationHtml = `
        <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); changePage(${currentPage - 1})">
                <i class="bi bi-caret-left-fill"></i>
            </a>
        </li>`;

    // Show page numbers with ellipsis for large number of pages
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    // Adjust start page if we're near the end
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // First page
    if (startPage > 1) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="event.preventDefault(); changePage(1)">1</a>
            </li>`;
        if (startPage > 2) {
            paginationHtml += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>`;
        }
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); changePage(${i})">${i}</a>
            </li>`;
    }

    // Last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHtml += `
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>`;
        }
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="event.preventDefault(); changePage(${totalPages})">${totalPages}</a>
            </li>`;
    }

    // Next button
    paginationHtml += `
        <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); changePage(${currentPage + 1})">
                <i class="bi bi-caret-right-fill"></i>
            </a>
        </li>`;

    elements.pagination.innerHTML = paginationHtml;
    elements.pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
}

function changePage(newPage) {
    if (isLoading) return;
    currentPage = newPage;
    updateProducts();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
// Page change function
async function updateProducts() {
    if (isLoading || !initializeElements()) return;
    
    isLoading = true;
    showLoading();

    try {
        // Construct query parameters for fetching products
        const queryParams = new URLSearchParams({
            page: currentPage,
            sort: currentSort
        });
        
        if (selectedCategories.length > 0) {
            queryParams.append('categories', selectedCategories.join(','));
        }

        const response = await fetch(`fetch_products.php?${queryParams.toString()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }

        updateProductList(data.products);
        updatePagination(data.pagination);
        
    } catch (error) {
        console.error('Fetch error:', error);
        if (elements.productList) {
            elements.productList.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error loading products: ${error.message}
                </div>`;
        }
    } finally {
        isLoading = false;
    }
}


// Sorting function
function updateSort(sortType) {
    currentSort = sortType;
    currentPage = 1;
    updateProducts();
    updateSortButtons(sortType);
}

// Update sort button states
function updateSortButtons(activeSort) {
    document.querySelectorAll('[data-sort]').forEach(button => {
        button.classList.toggle('active', button.dataset.sort === activeSort);
    });
}

// Category filter function
function filterCategory(category) {
    const index = selectedCategories.indexOf(category);
    if (index > -1) {
        selectedCategories.splice(index, 1);
    } else {
        selectedCategories.push(category);
    }
    currentPage = 1;
    updateProducts();
    updateCategoryButtons();
}

// Update category button states
function updateCategoryButtons() {
    document.querySelectorAll('.btn-check + label').forEach(label => {
        const category = label.htmlFor.replace('btn-check-', '');
        label.classList.toggle('active', selectedCategories.includes(category));
    });
}

// Page change function
function changePage(newPage) {
    if (newPage < 1) return;
    currentPage = newPage;
    updateProducts();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    if (initializeElements()) {
        updateProducts();
        updateSortButtons('newest');
        updateCategoryButtons();
    }
});
</script>