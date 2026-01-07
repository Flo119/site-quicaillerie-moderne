<?php
require_once 'db.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = trim($_POST['message']);
    if ($msg) { // Prevent empty strings
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
        $stmt->execute([$user_id, $msg]);
    }
    // PRG pattern to prevent resubmission
    header("Location: chat.php");
    exit;
}

// Fetch messages (Simple version: everyone sees their own messages to admin, and we simulate admin replies or see all)
// For a simple chat, let's say the user sees a public channel or a private one.
// Let's implement a "Community Chat" where everyone sees messages, or a "Support Chat".
// Given the requirements "se faire sur l'application", let's make it a general chat room for logged users.
$stmt = $pdo->query("SELECT m.*, u.username, u.role FROM messages m JOIN users u ON m.user_id = u.id ORDER BY m.created_at ASC");
$messages = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Quincaillerie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .chat-container {
            height: 70vh;
            overflow-y: auto;
            background: rgba(255,255,255,0.5);
            border-radius: 15px;
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 20px;
            margin-bottom: 10px;
            position: relative;
        }
        .msg-mine {
            background: var(--primary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        .msg-other {
            background: white;
            border: 1px solid #e2e8f0;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        .msg-admin {
            background: var(--accent-color);
            color: white;
            border: none;
        }
        .sender-name {
            font-size: 0.75rem;
            margin-bottom: 2px;
            font-weight: bold;
            opacity: 0.8;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-glass mb-4">
        <div class="container">
            <a href="index.php" class="navbar-brand fw-bold text-primary"><i class="fas fa-arrow-left"></i> Retour</a>
            <span class="navbar-text fw-bold">Chat Communautaire</span>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-panel p-4">
                    
                    <div class="chat-container mb-3 p-3" id="chatBox">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted mt-5">
                                <i class="fas fa-comments fa-3x mb-3 opacity-25"></i>
                                <p>Aucun message. Commencez la discussion !</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): 
                                $isMine = ($msg['user_id'] == $user_id);
                                $isAdmin = ($msg['role'] === 'admin');
                                $class = $isMine ? 'msg-mine' : ($isAdmin ? 'msg-other msg-admin' : 'msg-other');
                            ?>
                                <div class="message-bubble <?php echo $class; ?> shadow-sm animate__animated animate__fadeIn">
                                    <?php if (!$isMine): ?>
                                        <div class="sender-name">
                                            <?php echo htmlspecialchars($msg['username']); ?>
                                            <?php if ($isAdmin) echo ' <span class="badge bg-warning text-dark" style="font-size:0.6em">ADMIN</span>'; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    
                                    <div class="text-end" style="font-size: 0.65rem; opacity: 0.7; margin-top: 5px;">
                                        <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="d-flex gap-2">
                        <input type="text" name="message" class="form-control rounded-pill border-0 bg-light shadow-sm" placeholder="Écrivez votre message..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary rounded-circle shadow-sm" style="width: 50px; height: 50px;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto scroll to bottom
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</body>
</html>
