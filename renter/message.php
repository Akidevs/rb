<?php
// renter/messages.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../includes/chat_functions.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}

$userId = $_SESSION['id'];

// Fetch all conversations for the renter
$conversations = getUserConversations($conn, $userId);

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate CSRF token
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Renter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .chat-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .chat-card {
            width: 90%;
            max-width: 1200px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .chat-list {
            max-height: 600px;
            overflow-y: auto;
        }
        .chat-item {
            cursor: pointer;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            transition: background-color 0.2s ease-in-out;
        }
        .chat-item:hover, .chat-active {
            background-color: #e9ecef;
        }
        .message-container {
            height: 500px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 8px;
        }
        .message {
            margin-bottom: 10px;
        }
        .message.sent {
            text-align: right;
        }
        .message.received {
            text-align: left;
        }
        .message .text {
            display: inline-block;
            padding: 10px;
            border-radius: 15px;
            max-width: 70%;
            word-wrap: break-word;
        }
        .message.sent .text {
            background-color: #0d6efd;
            color: #fff;
        }
        .message.received .text {
            background-color: #e9ecef;
            color: #000;
        }
        .message-input {
            border-radius: 25px;
            padding: 10px 15px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="chat-container">
        <div class="card chat-card">
            <div class="row g-0">
                <!-- Conversations List -->
                <div class="col-md-4 border-end">
                    <div class="card-header text-center bg-primary text-white">
                        Chats
                    </div>
                    <ul class="list-group list-group-flush chat-list">
                        <?php if (!empty($conversations)): ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <li class="list-group-item chat-item" 
                                    data-conversation-id="<?= htmlspecialchars($conversation['id']); ?>" 
                                    data-product-id="<?= htmlspecialchars($conversation['product_id']); ?>" 
                                    data-recipient-id="<?= htmlspecialchars($conversation['other_user_id']); ?>">
                                    <strong><?= htmlspecialchars($conversation['other_user_name']); ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($conversation['product_name']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center">No conversations found.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <!-- Messages Section -->
                <div class="col-md-8">
                    <div class="card-header text-center bg-primary text-white">
                        Messages
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div id="renterChatBox" class="message-container flex-grow-1 mb-3"></div>
                        <form id="renterChatForm" class="d-flex align-items-center" method="POST" action="../chat_handler.php">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="recipient_id" id="renterRecipientId" value="">
                            <input type="hidden" name="product_id" id="renterProductId" value="">
                            <input type="hidden" name="action" value="send_message">
                            <input type="text" class="form-control message-input me-2" id="renterMessageInput" name="message" placeholder="Type your message..." required>
                            <button class="btn btn-primary" type="submit">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatItems = document.querySelectorAll('.chat-item');
            const chatBox = document.getElementById('renterChatBox');
            const chatForm = document.getElementById('renterChatForm');
            const messageInput = document.getElementById('renterMessageInput');
            const recipientIdInput = document.getElementById('renterRecipientId');
            const productIdInput = document.getElementById('renterProductId');
            let currentConversationId = null;

            // Load messages for a conversation
            function loadMessages(conversationId) {
                const formData = new FormData();
                formData.append('action', 'fetch_messages');
                formData.append('product_id', productIdInput.value);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token']; ?>');

                fetch('../chat_handler.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        chatBox.innerHTML = '';
                        if (data.success) {
                            data.messages.forEach(msg => {
                                const msgElem = document.createElement('div');
                                msgElem.classList.add('message', msg.sender_id == <?= $userId; ?> ? 'sent' : 'received');
                                msgElem.innerHTML = `<div class="text">${msg.message}<br><small>${msg.created_at}</small></div>`;
                                chatBox.appendChild(msgElem);
                            });
                            chatBox.scrollTop = chatBox.scrollHeight;
                        } else {
                            chatBox.innerHTML = '<p class="text-danger">Failed to load messages.</p>';
                        }
                    })
                    .catch(() => chatBox.innerHTML = '<p class="text-danger">Error loading messages.</p>');
            }

            // Handle chat selection
            chatItems.forEach(item => {
                item.addEventListener('click', function() {
                    chatItems.forEach(i => i.classList.remove('chat-active'));
                    this.classList.add('chat-active');
                    currentConversationId = this.getAttribute('data-conversation-id');
                    productIdInput.value = this.getAttribute('data-product-id');
                    recipientIdInput.value = this.getAttribute('data-recipient-id');
                    loadMessages(currentConversationId);
                });
            });

            // Handle message sending
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const message = messageInput.value.trim();

                if (!message || !currentConversationId) {
                    alert("Please select a conversation and type a message.");
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('product_id', productIdInput.value);
                formData.append('recipient_id', recipientIdInput.value);
                formData.append('message', message);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token']; ?>');

                fetch('../chat_handler.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messageInput.value = '';
                            loadMessages(currentConversationId);
                        } else {
                            alert(data.message || 'Failed to send message.');
                        }
                    })
                    .catch(err => console.error('Error:', err));
            });
        });
    </script>
</body>
</html>