<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];

    if ($rating < 0 || $rating > 5) $rating = 5;

    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_id, $user_id, $rating, $comment]);

    // Notify Admin
    $notifMsg = "Nouvel avis 5 étoiles sur un produit !"; // Generic fallback
    // Fetch product name for better notification
    $pNameStmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $pNameStmt->execute([$product_id]);
    $pName = $pNameStmt->fetchColumn();
    
    $notifMsg = "Nouvel avis (" . $rating . "/5) de " . $_SESSION['username'] . " sur " . $pName;
    $stmt = $pdo->prepare("INSERT INTO notifications (type, message) VALUES ('review', ?)");
    $stmt->execute([$notifMsg]);

    // Redirect back to home with validation message
    header("Location: index.php?msg=Avis ajouté avec succès !");
    exit;
}
?>
