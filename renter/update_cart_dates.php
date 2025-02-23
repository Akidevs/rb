<?php
// update_cart_dates.php
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
if (!isset($_POST['cart_id']) || !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    echo json_encode(['success' => false, 'message' => 'Incomplete parameters.']);
    exit();
}

$cartId = intval($_POST['cart_id']);
$startDate = $_POST['start_date'];
$endDate = $_POST['end_date'];

// Log received dates
log_error("Received dates for cart ID $cartId: Start Date = $startDate, End Date = $endDate");

// Validate date formats
$dateFormat = 'Y-m-d';
$startDateObj = DateTime::createFromFormat($dateFormat, $startDate);
$endDateObj = DateTime::createFromFormat($dateFormat, $endDate);

// Check if dates are valid and match the format
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

// Check if cart item exists and belongs to the renter
$checkSql = "SELECT * FROM cart_items WHERE id = :cartId AND renter_id = :renterId";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bindParam(':cartId', $cartId, PDO::PARAM_INT);
$checkStmt->bindParam(':renterId', $renterId, PDO::PARAM_INT);
$checkStmt->execute();

$cartItem = $checkStmt->fetch();

if (!$cartItem) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
    log_error("Cart item not found for cart ID $cartId and renter ID $renterId.");
    exit();
}

// Update the cart item with new dates
$updateSql = "UPDATE cart_items SET start_date = :start_date, end_date = :end_date, updated_at = NOW() WHERE id = :cartId";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bindParam(':start_date', $startDate);
$updateStmt->bindParam(':end_date', $endDate);
$updateStmt->bindParam(':cartId', $cartId, PDO::PARAM_INT);

if ($updateStmt->execute()) {
    log_error("Successfully updated dates for cart ID $cartId.");
    echo json_encode(['success' => true, 'message' => 'Dates updated successfully.']);
} else {
    $errorInfo = $updateStmt->errorInfo();
    log_error("Failed to update dates for cart ID $cartId. Error: " . $errorInfo[2]);
    echo json_encode(['success' => false, 'message' => 'Failed to update dates.']);
}
?>