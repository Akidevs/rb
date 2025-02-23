<?php
// update_rental_dates.php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../db/db.php';

// Function to log errors
function log_error($message) {
    $logFile = '../logs/error_log.txt';
    $currentDate = date('Y-m-d H:i:s');
    $formattedMessage = "[$currentDate] $message\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

// Check if user is logged in and is a renter
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'renter') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$renterId = $_SESSION['id'];

// Check if required POST parameters are set
if (!isset($_POST['cart_id']) || !isset($_POST['start_date']) || !isset($_POST['end_date']) || !isset($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Incomplete parameters.']);
    exit();
}

$cartId = intval($_POST['cart_id']);
$startDate = $_POST['start_date'];
$endDate = $_POST['end_date'];
$csrfToken = $_POST['csrf_token'];

// CSRF Protection
if ($csrfToken !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    log_error("Invalid CSRF token for cart ID: $cartId by user ID: $renterId");
    exit();
}

// Validate date formats
$dateFormat = 'Y-m-d';
$startDateObj = DateTime::createFromFormat($dateFormat, $startDate);
$endDateObj = DateTime::createFromFormat($dateFormat, $endDate);

if (!$startDateObj || !$endDateObj || $startDateObj->format($dateFormat) !== $startDate || $endDateObj->format($dateFormat) !== $endDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    log_error("Invalid date format for cart ID $cartId. Start Date: $startDate, End Date: $endDate");
    exit();
}

// Ensure start_date <= end_date
if ($startDateObj > $endDateObj) {
    echo json_encode(['success' => false, 'message' => 'End date cannot be before start date.']);
    log_error("End date before start date for cart ID $cartId. Start Date: $startDate, End Date: $endDate");
    exit();
}

// Check if dates are not in the past
$today = new DateTime();
$today->setTime(0, 0, 0); // Normalize to midnight
$startDateObj->setTime(0, 0, 0);
$endDateObj->setTime(0, 0, 0);
if ($startDateObj < $today || $endDateObj < $today) {
    echo json_encode(['success' => false, 'message' => 'Dates cannot be in the past.']);
    log_error("Dates in the past for cart ID $cartId. Start Date: $startDate, End Date: $endDate");
    exit();
}

// Fetch the cart item to get rental_period and rental_price
$sql = "SELECT c.*, p.rental_period, p.rental_price 
        FROM cart_items c
        INNER JOIN products p ON c.product_id = p.id
        WHERE c.id = :cartId AND c.renter_id = :renterId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':cartId', $cartId, PDO::PARAM_INT);
$stmt->bindParam(':renterId', $renterId, PDO::PARAM_INT);
$stmt->execute();
$cartItem = $stmt->fetch();

if (!$cartItem) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
    log_error("Cart item not found for cart ID $cartId and user ID $renterId.");
    exit();
}

// Calculate rental periods and total_cost
$rental_period = strtolower($cartItem['rental_period']);
$periods = 1;
switch ($rental_period) {
    case 'day':
        $periods = $startDateObj->diff($endDateObj)->days + 1;
        break;
    case 'week':
        $periods = ceil(($startDateObj->diff($endDateObj)->days + 1) / 7);
        break;
    case 'month':
        $periods = ceil(($startDateObj->diff($endDateObj)->days + 1) / 30);
        break;
    default:
        $periods = 1;
}
$total_cost = $cartItem['rental_price'] * $periods;

// Update the cart item with new dates and total_cost
$updateSql = "UPDATE cart_items 
              SET start_date = :start_date, end_date = :end_date, updated_at = NOW() 
              WHERE id = :cartId AND renter_id = :renterId";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bindParam(':start_date', $startDate);
$updateStmt->bindParam(':end_date', $endDate);
$updateStmt->bindParam(':cartId', $cartId, PDO::PARAM_INT);
$updateStmt->bindParam(':renterId', $renterId, PDO::PARAM_INT);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Dates updated successfully.', 'new_total_cost' => $total_cost]);
    log_error("Successfully updated dates for cart ID $cartId. New Total Cost: $total_cost");
} else {
    $errorInfo = $updateStmt->errorInfo();
    echo json_encode(['success' => false, 'message' => 'Failed to update dates.']);
    log_error("Failed to update dates for cart ID $cartId. Error: " . $errorInfo[2]);
}
?>