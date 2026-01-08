<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'] ?? 'client';

// -- CONTEXT MANAGEMENT (Admin Only) --
$view_user_id = isset($_GET['reply_to']) ? (int)$_GET['reply_to'] : null;

// Fetch conversations list for Admin sidebar
$conversations = [];
if ($current_role === 'admin') {
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
    
    // Get partner name if viewing one
    $chat_partner_name = '';
    if ($view_user_id) {
        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->execute([$view_user_id]);
        $chat_partner_name = $uStmt->fetchColumn();
    }
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
            <!-- ADMIN SIDEBAR -->
            <div class="col-md-4 user-list bg-white bg-opacity-50">
                <div class="p-3 border-bottom fw-bold text-muted small uppercase">DISCUSSIONS</div>
                <?php if (empty($conversations)): ?>
                    <p class="p-3 text-muted small">Aucune conversation active. <a href="chat.php">Rafraîchir</a></p>
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
                        <!-- Messages loaded via JS -->
                        <div class="text-center mt-5 text-muted small loading-indicator">
                            <i class="fas fa-spinner fa-spin"></i> Chargement...
                        </div>
                    </div>

                    <form id="chatForm" class="mt-auto d-flex gap-2">
                        <input type="hidden" name="recipient_id" value="<?php echo $view_user_id; ?>">
                        <input type="text" id="msgInput" name="message" class="form-control rounded-pill border-0 bg-light shadow-sm py-3 px-4" placeholder="Votre message..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary rounded-circle shadow-sm" style="width: 50px; height: 50px;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>

                <?php endif; ?>
            </div>
            
        </div>
    </div>

    <!-- AJAX LOGIC -->
    <script>
        const chatBox = document.getElementById('msgBox');
        const chatForm = document.getElementById('chatForm');
        const msgInput = document.getElementById('msgInput');
        const currentRole = "<?php echo $current_role; ?>";
        const replyTo = "<?php echo $view_user_id; ?>";
        let shouldScroll = true;

        if (chatForm) {
            // 1. SEND MESSAGE
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        msgInput.value = '';
                        loadMessages(); // Reload immediately
                        shouldScroll = true;
                    }
                });
            });

            // 2. LOAD MESSAGES
            function loadMessages() {
                let url = 'chat_api.php';
                if (currentRole === 'admin' && replyTo) {
                    url += '?reply_to=' + replyTo;
                }

                fetch(url)
                .then(response => response.json())
                .then(messages => {
                    let html = '';
                    if (messages.length === 0) {
                        html = '<div class="text-center mt-5 text-muted small"><p>Début de la conversation.</p></div>';
                    } else {
                        messages.forEach(m => {
                            const type = m.is_me ? 'msg-sent' : 'msg-received';
                            let controls = '';
                            if (m.is_me) {
                                controls = `
                                    <div class="mt-1 text-end opacity-50 small-controls">
                                        <a href="#" onclick="editMessage(${m.id}, '${m.message.replace(/'/g, "\\'")}', event)" class="text-white me-2" title="Modifier"><i class="fas fa-pen fa-xs"></i></a>
                                        <a href="#" onclick="deleteMessage(${m.id}, event)" class="text-white" title="Supprimer"><i class="fas fa-trash fa-xs"></i></a>
                                    </div>
                                `;
                            }
                            html += `
                                <div class="message-bubble ${type}">
                                    ${m.message}
                                    ${controls}
                                    <div class="text-end" style="font-size: 0.7em; opacity: 0.7; margin-top: 4px;">
                                        ${m.time}
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    // Only update if changes detected (naive check length) or force refresh
                    if(chatBox.innerHTML !== html) {
                        const wasAtBottom = chatBox.scrollHeight - chatBox.scrollTop === chatBox.clientHeight;
                        chatBox.innerHTML = html;
                        if(shouldScroll || wasAtBottom) {
                            chatBox.scrollTop = chatBox.scrollHeight;
                        }
                    }
                });
            }

            // DELETE
            window.deleteMessage = function(id, e) {
                e.preventDefault();
                if(!confirm('Supprimer ce message ?')) return;
                
                let fd = new FormData();
                fd.append('action', 'delete');
                fd.append('msg_id', id);
                
                fetch('chat_api.php', { method: 'POST', body: fd })
                .then(() => loadMessages());
            };

            // EDIT
            window.editMessage = function(id, text, e) {
                e.preventDefault();
                // Strip HTML br for validation
                let cleanText = text.replace(/<br\s*\/?>/gi, "");
                let newText = prompt("Modifier le message :", cleanText);
                if (newText !== null && newText !== cleanText) {
                    let fd = new FormData();
                    fd.append('action', 'edit');
                    fd.append('msg_id', id);
                    fd.append('message', newText);
                    
                    fetch('chat_api.php', { method: 'POST', body: fd })
                    .then(() => loadMessages());
                }
            };

            // Disable auto-scroll if user moves up
            chatBox.addEventListener('scroll', () => {
                const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop === chatBox.clientHeight;
                // Simple logic: if user scrolls up significantly, stop auto scrolling
            });

            // Poll every 3 seconds
            loadMessages(); // First load
            setInterval(loadMessages, 3000);
        }
    </script>
</body>
</html>
