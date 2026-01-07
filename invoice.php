<?php
require_once 'db.php';
// HTML Invoice Implementation
// This uses window.print() for PDF generation which is more robust without external dependencies.

// Then window.print() can save as PDF.

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Accès refusé");
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch Cart Items for Invoice
$stmt = $pdo->prepare("
    SELECT ri.quantity, p.name, p.price 
    FROM reservation_items ri
    JOIN reservations r ON ri.reservation_id = r.id
    JOIN products p ON ri.product_id = p.id
    WHERE r.user_id = ? AND r.status = 'pending'
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

if (empty($items)) {
    die("Aucun article à facturer.");
}

$location = $_GET['location'] ?? 'Douala';
$invoiceDate = "Fait à " . htmlspecialchars($location) . ", le " . date('d/m/Y');
$invoiceNumber = 'FAC-' . date('Ymd') . '-' . rand(100, 999);

// Site Owner Info
$ownerName = "Quincaillerie Moderne";
$ownerAddress = "123 Rue de la Construction, " . htmlspecialchars($location);
$ownerPhone = "+237 600 000 000";
$ownerEmail = "contact@quincaillerie-moderne.com";

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture <?php echo $invoiceNumber; ?></title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; max-width: 800px; margin: 0 auto; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 50px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .company-info h1 { color: #2563eb; margin: 0; font-size: 24px; }
        .company-info p { margin: 5px 0; font-size: 14px; color: #666; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { margin: 0; color: #333; }
        .client-info { margin-bottom: 40px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table th { text-align: left; background: #f8fafc; padding: 12px; border-bottom: 2px solid #e2e8f0; }
        .table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .total-section { text-align: right; font-size: 18px; }
        .total-amount { font-size: 24px; font-weight: bold; color: #2563eb; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        
        /* Make content editable for final tweaks */
        [contenteditable]:hover { outline: 1px dashed #ccc; cursor: text; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            [contenteditable]:hover { outline: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <div style="float: left; color: #666; font-size: 0.9em;">
            <i class="fas fa-info-circle"></i> Info : Vous pouvez cliquer sur les textes pour les modifier avant d'imprimer.
        </div>
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-print"></i> Imprimer / Sauvegarder en PDF
        </button>
    </div>

    <div class="header">
        <div class="company-info" contenteditable="true">
            <h1><?php echo $ownerName; ?></h1>
            <p><?php echo $ownerAddress; ?></p>
            <p>Tél: <?php echo $ownerPhone; ?></p>
            <p>Email: <?php echo $ownerEmail; ?></p>
        </div>
        <div class="invoice-details">
            <h2>FACTURE</h2>
            <p><strong>N° :</strong> <?php echo $invoiceNumber; ?></p>
            <p><strong>Date :</strong> <?php echo $invoiceDate; ?></p>
        </div>
    </div>

    <div class="client-info">
        <h3>Facturé à :</h3>
        <p contenteditable="true"><strong>Client :</strong> <?php echo htmlspecialchars($username); ?></p>
        <p contenteditable="true" style="color: #666;">(Adresse du client... Cliquez pour éditer)</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Désignation</th>
                <th style="text-align: center;">Quantité</th>
                <th style="text-align: right;">Prix Unitaire</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): 
                $lineTotal = $item['price'] * $item['quantity'];
                $total += $lineTotal;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                <td style="text-align: right;"><?php echo number_format($item['price'], 0, ',', ' '); ?> XFA</td>
                <td style="text-align: right; font-weight: bold;"><?php echo number_format($lineTotal, 0, ',', ' '); ?> XFA</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <p>Total à payer : <span class="total-amount"><?php echo number_format($total, 0, ',', ' '); ?> XFA</span></p>
    </div>

    <div class="footer">
        <p>Merci de votre confiance !</p>
        <p>Conditions de paiement : Paiement à la réception des marchandises.</p>
    </div>

</body>
</html>
