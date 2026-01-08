<?php
require_once 'db.php';
session_start();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    
    // 1. Check raw input
    $msg .= "Email reçu: [" . $email . "] (longueur: " . strlen($email) . ")<br>";
    $msg .= "Email trimé: [" . trim($email) . "]<br>";
    
    // 2. Check DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();
    
    if ($user) {
        $msg .= "Utilisateur trouvé: ID=" . $user['id'] . ", Hash=" . substr($user['password'], 0, 10) . "...<br>";
        
        // 3. Verify Password
        if (password_verify($pass, $user['password'])) {
            $msg .= "<strong style='color:green'>SUCCÈS ! Mot de passe correct.</strong><br>";
            // Force login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $msg .= "Session créée. <a href='index.php'>Aller à l'accueil</a>";
        } else {
            $msg .= "<strong style='color:red'>ÉCHEC : Mot de passe incorrect.</strong><br>";
        }
    } else {
        $msg .= "<strong style='color:red'>ÉCHEC : Email introuvable dans la base.</strong><br>";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Test Connexion</title></head>
<body style="padding: 50px; font-family: sans-serif;">
    <h3>Outil de Diagnostic Connexion</h3>
    <form method="POST">
        Email: <input type="text" name="email" value="admin@quincaillerie.com" style="width: 300px;"><br><br>
        Pass: <input type="text" name="password" value="admin123"><br><br>
        <button type="submit">Tester la connexion</button>
    </form>
    <hr>
    <div style="background: #f0f0f0; padding: 20px;">
        <?php echo $msg; ?>
    </div>
</body>
</html>
