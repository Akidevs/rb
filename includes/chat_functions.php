<?php
// includes/chat_functions.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "PROJECT";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die('Error: Database connection failed.');
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Get or create conversation ID
 */
function getConversationId(PDO $conn, $productId, $renterId, $ownerId) {
    error_log("Getting conversation ID: Product $productId, Renter $renterId, Owner $ownerId");

    try {
        $stmt = $conn->prepare("SELECT id FROM conversations 
            WHERE product_id = :product_id AND renter_id = :renter_id AND owner_id = :owner_id LIMIT 1");
        $stmt->execute([
            ':product_id' => $productId,
            ':renter_id' => $renterId,
            ':owner_id' => $ownerId,
        ]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conversation) {
            return $conversation['id'];
        }

        $stmt = $conn->prepare("INSERT INTO conversations (product_id, renter_id, owner_id) 
            VALUES (:product_id, :renter_id, :owner_id)");
        $stmt->execute([
            ':product_id' => $productId,
            ':renter_id' => $renterId,
            ':owner_id' => $ownerId,
        ]);

        return (int)$conn->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error fetching/creating conversation: " . $e->getMessage());
        return 0;
    }
}

/**
 * Send message in a conversation
 */
function sendMessage(PDO $conn, $conversationId, $senderId, $message) {
    try {
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message) 
            VALUES (:conversation_id, :sender_id, :message)");
        return $stmt->execute([
            ':conversation_id' => $conversationId,
            ':sender_id' => $senderId,
            ':message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        ]);
    } catch (PDOException $e) {
        error_log("Error sending message: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieve messages for a conversation
 */
function getMessages(PDO $conn, $conversationId, $limit = 20, $offset = 0) {
    try {
        $stmt = $conn->prepare("SELECT m.*, u.name AS sender_name 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE m.conversation_id = :conversation_id 
            ORDER BY m.created_at ASC 
            LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching messages: " . $e->getMessage());
        return [];
    }
}

/**
 * Retrieve all conversations for a user
 */
function getUserConversations(PDO $conn, $userId) {
    try {
        $stmt = $conn->prepare("SELECT c.*, p.name AS product_name, 
                CASE 
                    WHEN c.renter_id = :user_id THEN o.name 
                    ELSE r.name 
                END AS other_user_name,
                CASE
                    WHEN c.renter_id = :user_id THEN o.id
                    ELSE r.id
                END AS other_user_id
            FROM conversations c
            JOIN products p ON c.product_id = p.id
            JOIN users o ON c.owner_id = o.id
            JOIN users r ON c.renter_id = r.id
            WHERE c.renter_id = :user_id OR c.owner_id = :user_id
            ORDER BY c.created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching conversations: " . $e->getMessage());
        return [];
    }
}
?>