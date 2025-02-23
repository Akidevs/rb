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
                    // Query to fetch distinct categories
                    $sql = "SELECT DISTINCT category FROM products";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        // Output each category as a checkbox
                        while ($row = $result->fetch_assoc()) {
                            $category = $row['category'];
                            echo '<input type="checkbox" class="btn-check" id="btn-check-' . $category . '" autocomplete="off">';
                            echo '<label class="btn btn-outline-secondary" for="btn-check-' . $category . '">' . $category . '</label>';
                        }
                    } else {
                        echo "No categories found.";
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

<?php
// Close the connection
$conn->close();
?>
