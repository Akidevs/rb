<?php
// chat_handler.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "PROJECT";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}

require_once 'includes/chat_functions.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$userId = $_SESSION['id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'fetch_messages':
        $productId = intval($_POST['product_id'] ?? 0);
        $csrfToken = $_POST['csrf_token'] ?? '';

        // Validate CSRF token
        if ($csrfToken !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            exit();
        }

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Product ID.']);
            exit();
        }

        // Fetch product to get owner ID
        $stmt = $conn->prepare("SELECT owner_id FROM products WHERE id = :product_id");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit();
        }

        $ownerId = intval($product['owner_id']);

        // Determine user role
        if ($userId !== $ownerId) {
            $renterId = $userId;
        } else {
            $stmt = $conn->prepare("SELECT renter_id FROM cart_items WHERE product_id = :product_id LIMIT 1");
            $stmt->execute([':product_id' => $productId]);
            $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);
            $renterId = $cartItem['renter_id'] ?? 0;

            if (!$renterId) {
                echo json_encode(['success' => false, 'message' => 'No renter found for this product.']);
                exit();
            }
        }

        $conversationId = getConversationId($conn, $productId, $renterId, $ownerId);
        $messages = getMessages($conn, $conversationId);

        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'send_message':
        $productId = intval($_POST['product_id'] ?? 0);
        $recipientId = intval($_POST['recipient_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $csrfToken = $_POST['csrf_token'] ?? '';

        // Validate inputs
        if ($csrfToken !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            exit();
        }
        if ($productId <= 0 || $recipientId <= 0 || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            exit();
        }

        $stmt = $conn->prepare("SELECT owner_id FROM products WHERE id = :product_id");
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit();
        }

        $ownerId = intval($product['owner_id']);
        $conversationId = getConversationId($conn, $productId, $userId === $ownerId ? $recipientId : $userId, $ownerId);

        if (!$conversationId) {
            echo json_encode(['success' => false, 'message' => 'Conversation could not be found or created.']);
            exit();
        }

        $success = sendMessage($conn, $conversationId, $userId, $message);

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Message sent.' : 'Failed to send message.'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}