<?php
// includes/send_message.php
session_start();
require_once '../db/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure user is logged in
    if (!isset($_SESSION['id'])) {
        $_SESSION['error'] = "You must be logged in to send a message.";
        header('Location: ../renter/login.php');
        exit();
    }

    $sender_id = $_SESSION['id'];
    $receiver_id = intval($_POST['receiver_id']);
    $content = trim($_POST['content']);

    if (empty($content)) {
        $_SESSION['error'] = "Message content cannot be empty.";
        header('Location: ../renter/messages.php');
        exit();
    }

    // Sanitize content to avoid XSS attacks when displaying later
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

    try {
        // Create or get the existing conversation between sender and receiver
        $stmt = $conn->prepare("SELECT id FROM conversations 
                               WHERE (renter_id = :sender_id AND owner_id = :receiver_id) 
                                  OR (renter_id = :receiver_id AND owner_id = :sender_id) 
                               LIMIT 1");
        $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
        $stmt->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // If no conversation exists, create a new one
        $conversation_id = $stmt->fetchColumn();
        if (!$conversation_id) {
            $insertStmt = $conn->prepare("INSERT INTO conversations (renter_id, owner_id) 
                                         VALUES (:sender_id, :receiver_id)");
            $insertStmt->execute([
                ':sender_id' => $sender_id,
                ':receiver_id' => $receiver_id
            ]);
            $conversation_id = $conn->lastInsertId();
        }

        // Insert the message into the messages table
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message) 
                               VALUES (:conversation_id, :sender_id, :content)");
        $stmt->bindParam(':conversation_id', $conversation_id, PDO::PARAM_INT);
        $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Message sent successfully.";
        } else {
            $_SESSION['error'] = "Failed to send message.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    }

    header('Location: ../renter/messages.php');
    exit();
}
?>