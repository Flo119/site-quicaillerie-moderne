<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'] ?? 'client';
$admin_id = 1; // Assuming Admin is ID 1 (based on db.php seeding)

// Find true Admin ID just in case
$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$real_admin_id = $stmt->fetchColumn();
if ($real_admin_id) $admin_id = $real_admin_id;

// -- CONTEXT MANAGEMENT --
// If Admin, they can view a specific conversation
$view_user_id = isset($_GET['reply_to']) ? (int)$_GET['reply_to'] : null;

// -- HANDLE MESSAGE SENDING --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = trim($_POST['message']);
    
    if ($msg) {
        if ($current_role === 'client') {
            // Client always sends to Admin
            $stmt = $pdo->prepare("INSERT INTO messages (user_id, recipient_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$current_user_id, $admin_id, $msg]);
            
            // Notification for Admin
            $notifMsg = "Nouveau message de " . $_SESSION['username'];
            $pdo->prepare("INSERT INTO notifications (type, message) VALUES ('message', ?)")->execute([$notifMsg]);
            
        } elseif ($current_role === 'admin' && $view_user_id) {
            // Admin replies to specific user
            $stmt = $pdo->prepare("INSERT INTO messages (user_id, recipient_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$current_user_id, $view_user_id, $msg]);
            
        }
    }
    // Redirect to avoid resubmit
    if ($current_role === 'admin' && $view_user_id) {
        header("Location: chat.php?reply_to=" . $view_user_id);
    } else {
        header("Location: chat.php");
    }
    exit;
}

// -- FETCH MESSAGES & CONVERSATIONS --
$conversations = [];
$messages = [];

if ($current_role === 'admin') {
    // 1. Get List of Users who have chatted
    $stmt = $pdo->query("
        SELECT DISTINCT u.id, u.username, 
        (SELECT message FROM messages WHERE (user_id=u.id OR recipient_id=u.id) ORDER BY created_at DESC LIMIT 1) as last_msg,
        (SELECT created_at FROM messages WHERE (user_id=u.id OR recipient_id=u.id) ORDER BY created_at DESC LIMIT 1) as last_date
        FROM users u
        JOIN messages m ON u.id = m.user_id OR u.id = m.recipient_id
        WHERE u.role != 'admin'
        ORDER BY last_date DESC
    ");
    $conversations = $stmt->fetchAll();
    
    // 2. If viewing a conversation, fetch messages
    if ($view_user_id) {
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.role 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE (m.user_id = ? AND m.recipient_id = ?) 
               OR (m.user_id = ? AND m.recipient_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$view_user_id, $current_user_id, $current_user_id, $view_user_id]);
        $messages = $stmt->fetchAll();
        
        // Fetch User Name for Header
        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->execute([$view_user_id]);
        $chat_partner_name = $uStmt->fetchColumn();
    }

} else {
    // Client View: Only their own conversation with Admin
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.role 
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        WHERE (m.user_id = ? AND m.recipient_id = ?) 
           OR (m.user_id = ? AND m.recipient_id = ?)
        ORDER BY m.created_at ASC
    ");
    // Messages I sent to Admin OR Admin sent to Me
    $stmt->execute([$current_user_id, $admin_id, $admin_id, $current_user_id]);
    $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Chat Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .chat-layout { height: 80vh; }
        .user-list { overflow-y: auto; height: 100%; border-right: 1px solid rgba(0,0,0,0.1); }
        .chat-area { height: 100%; display: flex; flex-direction: column; }
        .messages-box { flex-grow: 1; overflow-y: auto; padding: 20px; background: rgba(255,255,255,0.4); border-radius: 15px; margin-bottom: 20px; }
        
        .message-bubble { max-width: 75%; padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; font-size: 0.95rem; }
        .msg-sent { background: var(--primary-color); color: white; margin-left: auto; border-bottom-right-radius: 5px; }
        .msg-received { background: white; border: 1px solid #e2e8f0; margin-right: auto; border-bottom-left-radius: 5px; }
        
        .user-item { cursor: pointer; transition: all 0.2s; }
        .user-item:hover, .user-item.active { background: rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body>

    <nav class="navbar navbar-glass mb-4">
        <div class="container">
            <a href="index.php" class="navbar-brand fw-bold text-primary"><i class="fas fa-arrow-left"></i> Retour</a>
            <span class="navbar-text fw-bold">
                <?php echo $current_role === 'admin' ? 'Espace Support Client' : 'Service Client'; ?>
            </span>
        </div>
    </nav>

    <div class="container">
        <div class="glass-panel p-0 overflow-hidden chat-layout d-flex">
            
            <?php if ($current_role === 'admin'): ?>
            <!-- ADMIN SIDEBAR: User List -->
            <div class="col-md-4 user-list bg-white bg-opacity-50">
                <div class="p-3 border-bottom fw-bold text-muted small uppercase">DISCUSSIONS</div>
                <?php if (empty($conversations)): ?>
                    <p class="p-3 text-muted small">Aucune conversation active.</p>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="?reply_to=<?php echo $conv['id']; ?>" class="d-block text-decoration-none text-dark">
                            <div class="user-item p-3 border-bottom <?php echo ($view_user_id == $conv['id']) ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold"><?php echo htmlspecialchars($conv['username']); ?></span>
                                    <span class="small text-muted"><?php echo date('d/m H:i', strtotime($conv['last_date'])); ?></span>
                                </div>
                                <div class="small text-muted text-truncate"><?php echo htmlspecialchars($conv['last_msg']); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- CHAT AREA -->
            <div class="<?php echo $current_role === 'admin' ? 'col-md-8' : 'col-md-12'; ?> chat-area p-3">
                <?php if ($current_role === 'admin' && !$view_user_id): ?>
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                        <i class="fas fa-inbox fa-4x mb-3"></i>
                        <p>Sélectionnez un client pour répondre.</p>
                    </div>
                <?php else: ?>
                    
                    <?php if ($current_role === 'admin'): ?>
                        <div class="mb-3 pb-2 border-bottom">
                            Discussion avec <span class="fw-bold text-primary"><?php echo $chat_partner_name; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="messages-box" id="msgBox">
                        <?php if (empty($messages)): ?>
                            <div class="text-center mt-5 text-muted small">
                                <p>C'est le début de votre conversation.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $m): 
                                $isMe = ($m['user_id'] == $current_user_id);
                            ?>
                                <div class="message-bubble <?php echo $isMe ? 'msg-sent' : 'msg-received'; ?>">
                                    <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                                    <div class="text-end" style="font-size: 0.7em; opacity: 0.7; margin-top: 4px;">
                                        <?php echo date('H:i', strtotime($m['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="mt-auto d-flex gap-2">
                        <input type="text" name="message" class="form-control rounded-pill border-0 bg-light shadow-sm py-3 px-4" placeholder="Votre message..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary rounded-circle shadow-sm" style="width: 50px; height: 50px;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>

                <?php endif; ?>
            </div>
            
        </div>
    </div>

    <script>
        // Scroll to bottom
        const box = document.getElementById('msgBox');
        if(box) box.scrollTop = box.scrollHeight;
    </script>
</body>
</html>
