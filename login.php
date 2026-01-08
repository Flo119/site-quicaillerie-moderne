<?php
require_once 'db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Quincaillerie</title>
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
                        <h2 class="fw-bold text-primary">Bienvenue</h2>
                        <p class="text-muted small">Connectez-vous à votre espace</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 text-center small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Email</label>
                            <input type="email" name="email" class="form-control rounded-pill bg-light border-0" required placeholder="nom@exemple.com">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small text-uppercase fw-bold text-muted">Mot de passe</label>
                            <input type="password" name="password" class="form-control rounded-pill bg-light border-0" required placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn btn-modern w-100 mb-3">Se Connecter</button>
                        <div class="text-center">
                            <a href="#" class="text-decoration-none small text-muted">Mot de passe oublié ?</a>
                            <br>
                            <a href="register.php" class="text-decoration-none small text-primary fw-bold">Créer un compte</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
