<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Un client';
$action = $_REQUEST['action'] ?? '';

// Get or Create Pending Reservation
$stmt = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$reservationId = $stmt->fetchColumn();

if (!$reservationId && $action !== 'add') {
    header("Location: cart.php");
    exit;
} else if (!$reservationId && $action === 'add') {
    $stmt = $pdo->prepare("INSERT INTO reservations (user_id, status) VALUES (?, 'pending')");
    $stmt->execute([$userId]);
    $reservationId = $pdo->lastInsertId();
}

if ($action === 'add') {
    $productId = $_REQUEST['id'];
    // Check if item exists
    $stmt = $pdo->prepare("SELECT id, quantity FROM reservation_items WHERE reservation_id = ? AND product_id = ?");
    $stmt->execute([$reservationId, $productId]);
    $item = $stmt->fetch();

    if ($item) {
        $stmt = $pdo->prepare("UPDATE reservation_items SET quantity = quantity + 1 WHERE id = ?");
        $stmt->execute([$item['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO reservation_items (reservation_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$reservationId, $productId]);
    }
    header("Location: cart.php");
    exit;

} elseif ($action === 'update_qty') {
    $itemId = $_POST['item_id'];
    $qty = (int)$_POST['quantity'];
    
    if ($qty > 0) {
        $stmt = $pdo->prepare("UPDATE reservation_items SET quantity = ? WHERE id = ? AND reservation_id = ?");
        $stmt->execute([$qty, $itemId, $reservationId]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM reservation_items WHERE id = ? AND reservation_id = ?");
        $stmt->execute([$itemId, $reservationId]);
    }
    header("Location: cart.php");
    exit;

} elseif ($action === 'remove') {
    $itemId = $_GET['item_id'];
    $stmt = $pdo->prepare("DELETE FROM reservation_items WHERE id = ? AND reservation_id = ?");
    $stmt->execute([$itemId, $reservationId]);
    header("Location: cart.php");
    exit;

} elseif ($action === 'validate_order') {
    // 1. Fetch Items to build summary
    $stmt = $pdo->prepare("
        SELECT p.name, ri.quantity 
        FROM reservation_items ri
        JOIN products p ON ri.product_id = p.id
        WHERE ri.reservation_id = ?
    ");
    $stmt->execute([$reservationId]);
    $items = $stmt->fetchAll();

    $summaryParts = [];
    foreach ($items as $item) {
        $summaryParts[] = $item['quantity'] . 'x ' . $item['name'];
    }
    $orderSummary = implode(', ', $summaryParts);

    // 2. Change status to confirmed
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
    $stmt->execute([$reservationId]);
    
    // 3. Create Detailed Notification for Admin
    $notifMsg = "Commande de " . $username . " : " . $orderSummary;
    $stmt = $pdo->prepare("INSERT INTO notifications (type, message) VALUES ('order', ?)");
    $stmt->execute([$notifMsg]);

    // 4. Redirect with success
    header("Location: cart.php?confirmed=1");
    exit;
}

header("Location: cart.php");
?>
