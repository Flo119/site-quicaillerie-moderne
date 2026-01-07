<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'] ?? 'client';
$admin_id = 1;

// Get Admin ID dynamically
$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$real_admin_id = $stmt->fetchColumn();
if ($real_admin_id) $admin_id = $real_admin_id;

// --- HANDLE POST (SEND MESSAGE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    $recipient = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
    
    // Logic to determine recipient
    if ($current_role === 'client') {
        $recipient = $admin_id;
    }
    
    if ($msg && $recipient) {
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, recipient_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$current_user_id, $recipient, $msg]);
        
        // Notify if client sending to admin
        if ($current_role === 'client') {
            $notifMsg = "Nouveau message de " . $_SESSION['username'];
            $pdo->prepare("INSERT INTO notifications (type, message) VALUES ('message', ?)")->execute([$notifMsg]);
        }
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Empty message or invalid recipient']);
    }
    exit;
}

// --- HANDLE GET (FETCH MESSAGES) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $view_user_id = isset($_GET['reply_to']) ? (int)$_GET['reply_to'] : null;
    
    $messages = [];
    
    if ($current_role === 'admin') {
        if ($view_user_id) {
            // Admin viewing specific conversation
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
        }
    } else {
        // Client viewing their own conversation
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.role 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE (m.user_id = ? AND m.recipient_id = ?) 
               OR (m.user_id = ? AND m.recipient_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$current_user_id, $real_admin_id, $real_admin_id, $current_user_id]);
        $messages = $stmt->fetchAll();
    }
    
    // Format for JSON
    $formatted = [];
    foreach($messages as $m) {
        $formatted[] = [
            'id' => $m['id'],
            'message' => nl2br(htmlspecialchars($m['message'])),
            'time' => date('H:i', strtotime($m['created_at'])),
            'is_me' => ($m['user_id'] == $current_user_id),
            'username' => htmlspecialchars($m['username'])
        ];
    }
    
    echo json_encode($formatted);
    exit;
}
?>
