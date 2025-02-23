<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/db.php';

// Function to log errors
function log_error($message) {
    $logFile = '../logs/error_log.txt';
    $currentDate = date('Y-m-d H:i:s');
    $formattedMessage = "[$currentDate] $message\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}

$userId = $_SESSION['id'];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $billing_name = htmlspecialchars(trim($_POST['billing_name']));
    $billing_email = filter_var(trim($_POST['billing_email']), FILTER_VALIDATE_EMAIL);
    $billing_phone = htmlspecialchars(trim($_POST['billing_phone']));
    $billing_address = htmlspecialchars(trim($_POST['billing_address']));
    $billing_country = htmlspecialchars(trim($_POST['billing_country']));
    $billing_city = htmlspecialchars(trim($_POST['billing_city']));
    $billing_zip = htmlspecialchars(trim($_POST['billing_zip']));
    $payment_method = htmlspecialchars(trim($_POST['payment_method']));

    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        log_error("Invalid CSRF token for user ID: $userId");
        header('Location: checkout.php');
        exit();
    }

    // Validate required fields
    if (
        !$billing_email || 
        empty($billing_name) || 
        empty($billing_phone) || 
        empty($billing_address) || 
        empty($billing_country) || 
        empty($billing_city) || 
        empty($billing_zip) || 
        empty($payment_method)
    ) {
        $_SESSION['error_message'] = "Please fill in all required fields correctly.";
        log_error("Validation failed for user ID: $userId");
        header('Location: checkout.php');
        exit();
    }

    // Determine if it's a direct checkout
    $isDirectCheckout = isset($_POST['direct_checkout']) && $_POST['direct_checkout'] == '1';

    // Begin transaction
    try {
        $conn->beginTransaction();

        if ($isDirectCheckout) {
            // Direct checkout: Insert a single rental
            $productId = intval($_POST['product_id']);
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];

            // Fetch product details
            $sql = "SELECT * FROM products WHERE id = :productId";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("Product not found.");
            }

            // Calculate rental periods based on rental_period
            $rentalPeriod = strtolower($product['rental_period']);
            $startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
            $endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);
            $interval = $startDateObj->diff($endDateObj);
            $days = $interval->days + 1; // Inclusive of start date

            switch ($rentalPeriod) {
                case 'day':
                    $periods = $days;
                    break;
                case 'week':
                    $periods = ceil($days / 7);
                    break;
                case 'month':
                    $periods = ceil($days / 30);
                    break;
                default:
                    $periods = 1;
            }

            $total_cost = $product['rental_price'] * $periods;

            // Insert into rentals
            $insertSql = "INSERT INTO rentals 
                (product_id, renter_id, owner_id, start_date, end_date, rental_price, total_cost, status, payment_method, created_at, updated_at)
                VALUES
                (:product_id, :renter_id, :owner_id, :start_date, :end_date, :rental_price, :total_cost, 'pending_confirmation', :payment_method, NOW(), NOW())";

            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $insertStmt->bindParam(':renter_id', $userId, PDO::PARAM_INT);
            $insertStmt->bindParam(':owner_id', $product['owner_id'], PDO::PARAM_INT);
            $insertStmt->bindParam(':start_date', $startDate);
            $insertStmt->bindParam(':end_date', $endDate);
            $insertStmt->bindParam(':rental_price', $product['rental_price']);
            $insertStmt->bindParam(':total_cost', $total_cost);
            $insertStmt->bindParam(':payment_method', $payment_method);

            if (!$insertStmt->execute()) {
                $errorInfo = $insertStmt->errorInfo();
                throw new Exception("Failed to insert rental: " . $errorInfo[2]);
            }

            // Get the last inserted rental ID
            $rentalId = $conn->lastInsertId();

            // Insert into billing_details
            $billingSql = "INSERT INTO billing_details 
                (rental_id, name, email, phone, address, country, city, zip, created_at, updated_at)
                VALUES
                (:rental_id, :name, :email, :phone, :address, :country, :city, :zip, NOW(), NOW())";

            $billingStmt = $conn->prepare($billingSql);
            $billingStmt->bindParam(':rental_id', $rentalId, PDO::PARAM_INT);
            $billingStmt->bindParam(':name', $billing_name);
            $billingStmt->bindParam(':email', $billing_email);
            $billingStmt->bindParam(':phone', $billing_phone);
            $billingStmt->bindParam(':address', $billing_address);
            $billingStmt->bindParam(':country', $billing_country);
            $billingStmt->bindParam(':city', $billing_city);
            $billingStmt->bindParam(':zip', $billing_zip);

            if (!$billingStmt->execute()) {
                $errorInfo = $billingStmt->errorInfo();
                throw new Exception("Failed to insert billing details: " . $errorInfo[2]);
            }
        } else {
            // Cart-based checkout: Insert multiple rentals
            // Fetch cart items for the user along with rental_period
            $sql = "SELECT c.*, p.name, p.image, p.rental_price, p.category, p.description, p.owner_id, p.rental_period
                    FROM cart_items c
                    INNER JOIN products p ON c.product_id = p.id
                    WHERE c.renter_id = :userId";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception("Failed to fetch cart items.");
            }
            $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($cartItems)) {
                throw new Exception("Your cart is empty.");
            }

            foreach ($cartItems as $item) {
                // Validate dates
                if (empty($item['start_date']) || empty($item['end_date'])) {
                    throw new Exception("Start date or end date not set for product ID: " . $item['product_id']);
                }

                // Calculate rental periods based on rental_period
                $rentalPeriod = strtolower($item['rental_period']);
                $startDateObj = DateTime::createFromFormat('Y-m-d', $item['start_date']);
                $endDateObj = DateTime::createFromFormat('Y-m-d', $item['end_date']);
                $interval = $startDateObj->diff($endDateObj);
                $days = $interval->days + 1; // Inclusive of start date

                switch ($rentalPeriod) {
                    case 'day':
                        $periods = $days;
                        break;
                    case 'week':
                        $periods = ceil($days / 7);
                        break;
                    case 'month':
                        $periods = ceil($days / 30);
                        break;
                    default:
                        $periods = 1;
                }

                $total_cost = $item['rental_price'] * $periods;

                // Insert into rentals
                $insertSql = "INSERT INTO rentals 
                    (product_id, renter_id, owner_id, start_date, end_date, rental_price, total_cost, status, payment_method, created_at, updated_at)
                    VALUES
                    (:product_id, :renter_id, :owner_id, :start_date, :end_date, :rental_price, :total_cost, 'pending_confirmation', :payment_method, NOW(), NOW())";

                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
                $insertStmt->bindParam(':renter_id', $userId, PDO::PARAM_INT);
                $insertStmt->bindParam(':owner_id', $item['owner_id'], PDO::PARAM_INT);
                $insertStmt->bindParam(':start_date', $item['start_date']);
                $insertStmt->bindParam(':end_date', $item['end_date']);
                $insertStmt->bindParam(':rental_price', $item['rental_price']);
                $insertStmt->bindParam(':total_cost', $total_cost);
                $insertStmt->bindParam(':payment_method', $payment_method);

                if (!$insertStmt->execute()) {
                    $errorInfo = $insertStmt->errorInfo();
                    throw new Exception("Failed to insert rental: " . $errorInfo[2]);
                }

                // Get the last inserted rental ID
                $rentalId = $conn->lastInsertId();

                // Insert into billing_details
                $billingSql = "INSERT INTO billing_details 
                    (rental_id, name, email, phone, address, country, city, zip, created_at, updated_at)
                    VALUES
                    (:rental_id, :name, :email, :phone, :address, :country, :city, :zip, NOW(), NOW())";

                $billingStmt = $conn->prepare($billingSql);
                $billingStmt->bindParam(':rental_id', $rentalId, PDO::PARAM_INT);
                $billingStmt->bindParam(':name', $billing_name);
                $billingStmt->bindParam(':email', $billing_email);
                $billingStmt->bindParam(':phone', $billing_phone);
                $billingStmt->bindParam(':address', $billing_address);
                $billingStmt->bindParam(':country', $billing_country);
                $billingStmt->bindParam(':city', $billing_city);
                $billingStmt->bindParam(':zip', $billing_zip);

                if (!$billingStmt->execute()) {
                    $errorInfo = $billingStmt->errorInfo();
                    throw new Exception("Failed to insert billing details: " . $errorInfo[2]);
                }
            }

            // Clear the cart items
            $clearCartSql = "DELETE FROM cart_items WHERE renter_id = :userId";
            $clearCartStmt = $conn->prepare($clearCartSql);
            $clearCartStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            if (!$clearCartStmt->execute()) {
                throw new Exception("Failed to clear cart.");
            }
        }

        // Commit transaction
        $conn->commit();

        $_SESSION['success_message'] = "Checkout successful! Awaiting owner approval.";
        header('Location: checkout_success.php');
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $_SESSION['error_message'] = "Checkout failed: " . $e->getMessage();
        log_error("Checkout failed for user ID: $userId - " . $e->getMessage());
        header('Location: checkout.php');
        exit();
    }
} else {
    // If accessed without POST request
    header('Location: checkout.php');
    exit();
}
?>