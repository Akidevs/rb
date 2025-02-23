<?php
// owner/messages.php
session_start();
require_once '../db/db.php';
require_once '../includes/chat_functions.php';

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if owner is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    header('Location: login.php');
    exit();
}

// Ensure CSRF token is set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$ownerId = $_SESSION['id'];

// Fetch all conversations for the owner
$conversations = getUserConversations($conn, $ownerId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-list { max-height: 600px; overflow-y: auto; }
        .chat-item { cursor: pointer; padding: 10px; border-bottom: 1px solid #ddd; }
        .chat-item:hover, .chat-active { background-color: #e9ecef; }
        .message-container { height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background-color: #f1f1f1; }
        .message { margin-bottom: 10px; }
        .message.sent { text-align: right; }
        .message.received { text-align: left; }
        .message .text { display: inline-block; padding: 10px; border-radius: 15px; max-width: 70%; }
        .message.sent .text { background-color: #0d6efd; color: #fff; }
        .message.received .text { background-color: #fff; color: #000; }
    </style>
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4">Messages</h2>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">Conversations</div>
                            <ul class="list-group list-group-flush chat-list">
                                <?php if (!empty($conversations)): ?>
                                    <?php foreach ($conversations as $conversation): ?>
                                        <li class="list-group-item chat-item" 
    data-conversation-id="<?= htmlspecialchars($conversation['id'] ?? ''); ?>" 
    data-product-id="<?= htmlspecialchars($conversation['product_id'] ?? ''); ?>" 
    data-recipient-id="<?= htmlspecialchars($conversation['other_user_id'] ?? ''); ?>">
    <strong><?= htmlspecialchars($conversation['other_user_name'] ?? 'Unknown'); ?></strong><br>
    <small class="text-muted"><?= htmlspecialchars($conversation['product_name'] ?? 'No Product'); ?></small>
</li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center">No conversations found.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">Select a conversation</div>
                            <div class="card-body">
                                <div id="ownerChatBox" class="message-container mb-3"></div>
                                <form id="ownerChatForm">
                                    <div class="input-group">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="recipient_id" id="ownerRecipientId" value="">
                                        <input type="hidden" name="product_id" id="ownerProductId" value="">
                                        <input type="hidden" name="action" value="send_message">
                                        <input type="text" class="form-control" id="ownerMessageInput" name="message" placeholder="Type your message..." required>
                                        <button class="btn btn-success" type="submit">Send</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatItems = document.querySelectorAll('.chat-item');
            const chatBox = document.getElementById('ownerChatBox');
            const chatForm = document.getElementById('ownerChatForm');
            const messageInput = document.getElementById('ownerMessageInput');
            const recipientIdInput = document.getElementById('ownerRecipientId');
            const productIdInput = document.getElementById('ownerProductId');
            let currentConversationId = null;

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
                                msgElem.classList.add('message', msg.sender_id == <?= $ownerId; ?> ? 'sent' : 'received');
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

            chatItems.forEach(item => {
    item.addEventListener('click', function() {
        // Remove active class from all chat items and add it to the selected one
        chatItems.forEach(i => i.classList.remove('chat-active'));
        this.classList.add('chat-active');

        // Set the current conversation ID
        currentConversationId = this.getAttribute('data-conversation-id');

        // Update hidden inputs for recipient_id and product_id
        productIdInput.value = this.getAttribute('data-product-id');
        recipientIdInput.value = this.getAttribute('data-recipient-id');

        // Debugging: Ensure the values are populated
        console.log('Conversation selected:', {
            conversation_id: currentConversationId,
            product_id: productIdInput.value,
            recipient_id: recipientIdInput.value,
        });

        // Load the messages for the selected conversation
        loadMessages(currentConversationId);
    });
});

            chatForm.addEventListener('submit', function (e) {
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

    console.log('Form Data:', {
        product_id: productIdInput.value,
        recipient_id: recipientIdInput.value,
        message: message,
        csrf_token: '<?= $_SESSION['csrf_token']; ?>',
    });

    fetch('../chat_handler.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
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