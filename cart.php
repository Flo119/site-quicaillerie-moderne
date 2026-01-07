<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Check if just confirmed
$confirmed = isset($_GET['confirmed']);

// Fetch Cart (Pending)
$stmt = $pdo->prepare("
    SELECT ri.id as item_id, ri.quantity, p.name, p.price, p.image_url 
    FROM reservation_items ri
    JOIN reservations r ON ri.reservation_id = r.id
    JOIN products p ON ri.product_id = p.id
    WHERE r.user_id = ? AND r.status = 'pending'
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Panier - Quincaillerie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <nav class="navbar navbar-glass mb-4">
        <div class="container">
            <a href="index.php" class="navbar-brand fw-bold text-primary"><i class="fas fa-arrow-left"></i> Continuer mes achats</a>
            <span class="navbar-text fw-bold">Votre Panier</span>
        </div>
    </nav>

    <div class="container">
        
        <?php if ($confirmed): ?>
            <div class="alert alert-success text-center mb-5 animate__animated animate__fadeIn">
                <div class="display-4 text-success mb-3"><i class="fas fa-check-circle"></i></div>
                <h4 class="fw-bold">Commande Validée !</h4>
                <p>Votre commande a été envoyée au vendeur. Vous recevrez une réponse bientôt.</p>
                <a href="index.php" class="btn btn-outline-success rounded-pill px-4">Retour à l'accueil</a>
            </div>
        <?php endif; ?>

        <?php if (!$confirmed || !empty($items)): ?> <!-- Show cart if items exist (or if separate pending cart exists) -->
        <div class="row">
            <div class="col-lg-8">
                <div class="glass-panel p-4 mb-4">
                    <h5 class="fw-bold mb-4">Articles (<?php echo count($items); ?>)</h5>
                    
                    <?php if (empty($items)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-shopping-basket fa-3x mb-3 opacity-25"></i>
                            <p>Votre panier est vide.</p>
                            <a href="index.php" class="btn btn-modern btn-sm">Découvrir nos produits</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <div class="d-flex align-items-center mb-4 border-bottom pb-4">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="rounded" width="80" height="80" style="object-fit:cover;">
                            <div class="ms-3 flex-grow-1">
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <div class="text-muted small"><?php echo number_format($item['price'], 0, ',', ' '); ?> XFA / unité</div>
                            </div>
                            
                            <form action="cart_actions.php" method="POST" class="d-flex align-items-center mx-3">
                                <input type="hidden" name="action" value="update_qty">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control form-control-sm text-center" style="width: 60px;" onchange="this.form.submit()">
                            </form>
                            
                            <div class="fw-bold text-end" style="min-width: 100px;">
                                <?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> XFA
                            </div>
                            
                            <a href="cart_actions.php?action=remove&item_id=<?php echo $item['item_id']; ?>" class="btn btn-sm text-danger ms-3">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="glass-panel p-4 text-center">
                    <h5 class="fw-bold mb-3">Résumé</h5>
                    <div class="d-flex justify-content-between mb-3 fs-5">
                        <span>Total</span>
                        <span class="fw-bold text-primary"><?php echo number_format($total, 0, ',', ' '); ?> XFA</span>
                    </div>
                    <?php if (!empty($items)): ?>
                        
                        <a href="cart_actions.php?action=validate_order" class="btn btn-primary btn-lg w-100 mb-3 shadow-lg">
                            <i class="fas fa-check-double"></i> Valider la Commande
                        </a>

                        <hr>
                        <p class="small text-muted mb-2">Autres options :</p>

                        <a href="invoice.php" class="btn btn-outline-secondary w-100 mb-2" target="_blank">
                             <i class="fas fa-file-pdf"></i> Voir la Facture
                        </a>
                        
                        <a href="https://wa.me/23700000000?text=Bonjour,%20je%20veux%20confirmer%20ma%20commande%20de%20<?php echo number_format($total, 0, ',', ' '); ?>%20XFA" target="_blank" class="btn btn-success w-100 text-white">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

</body>
</html>
