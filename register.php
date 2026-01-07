<?php
require_once 'db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $email && $password) {
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hash])) {
                 header("Location: login.php");
                 exit;
            } else {
                $error = "Erreur lors de l'inscription.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Quincaillerie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
             height: 100vh;
             display: flex;
             align-items: center;
             justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="glass-panel p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Créer un compte</h2>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 text-center small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Nom d'utilisateur</label>
                            <input type="text" name="username" class="form-control rounded-pill bg-light border-0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Email</label>
                            <input type="email" name="email" class="form-control rounded-pill bg-light border-0" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small text-uppercase fw-bold text-muted">Mot de passe</label>
                            <input type="password" name="password" class="form-control rounded-pill bg-light border-0" required>
                        </div>
                        <button type="submit" class="btn btn-modern w-100 mb-3">S'inscrire</button>
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none small text-primary fw-bold">J'ai déjà un compte</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
