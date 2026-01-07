<?php
require_once 'db.php';
session_start();

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$editMode = false;
$productToEdit = ['name' => '', 'price' => '', 'description' => '', 'image_url' => ''];

// Helper function for file upload
function handleFileUpload($file) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/';
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        if (!in_array($file['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) return null;
        if (move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $targetPath)) return $targetPath;
    }
    return null;
}

// Handle Actions (Products)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    
    try {
        if ($action === 'add') {
             $imgPath = handleFileUpload($_FILES['image_file']) ?? 'https://via.placeholder.com/400';
             $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url) VALUES (?, ?, ?, ?)");
             $stmt->execute([$name, $desc, $price, $imgPath]);
             $message = 'Produit ajouté !';
        } elseif ($action === 'update') {
            $id = $_POST['id'];
            $newImgPath = handleFileUpload($_FILES['image_file']);
            if ($newImgPath) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$name, $desc, $price, $newImgPath, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?");
                $stmt->execute([$name, $desc, $price, $id]);
            }
            header("Location: admin.php?msg=Modifié"); exit;
        }
    } catch (Exception $e) { $message = $e->getMessage(); }
}

// Handle Deletions
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: admin.php?msg=Supprimé"); exit;
}
if (isset($_GET['delete_notif'])) {
    $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$_GET['delete_notif']]);
    header("Location: admin.php"); exit;
}
if (isset($_GET['delete_review'])) {
    $id = (int)$_GET['delete_review'];
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            header("Location: admin.php?msg=Avis supprimé avec succès");
        } else {
            header("Location: admin.php?msg=Erreur: Avis introuvable");
        }
    } else {
        header("Location: admin.php"); 
    }
    exit;
}

// Notifications Logic
if (isset($_GET['clear_notifs'])) {
    $pdo->exec("DELETE FROM notifications"); // User asked to delete, so let's clear all
    header("Location: admin.php"); exit;
}
$notifs = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll();
$notifCount = count($notifs);

// Fetch Data
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$reviews = $pdo->query("
    SELECT r.id as review_id, r.rating, r.comment, r.created_at, p.name as product_name, u.username 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC
")->fetchAll();


// Handle Edit Mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $productToEdit = $stmt->fetch();
}
if (isset($_GET['msg'])) $message = htmlspecialchars($_GET['msg']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <nav class="navbar navbar-glass mb-4" style="z-index: 1050; position: relative;">
        <div class="container">
            <span class="navbar-brand fw-bold text-primary"><i class="fas fa-cogs"></i> Admin Panel</span>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Notifications Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light position-relative" data-bs-toggle="dropdown">
                        <i class="fas fa-bell <?php echo $notifCount > 0 ? 'text-primary' : 'text-muted'; ?>"></i>
                        <?php if ($notifCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $notifCount; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow p-2 border-0" style="width: 320px; max-height: 400px; overflow-y:auto;">
                        <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                            Notifications
                            <?php if ($notifCount > 0): ?>
                                <a href="?clear_notifs=1" class="text-danger text-decoration-none small" style="font-size:0.8em;">Tout effacer</a>
                            <?php endif; ?>
                        </h6></li>
                        <?php if (empty($notifs)): ?>
                            <li><span class="dropdown-item text-muted small py-3 text-center">Aucune notification</span></li>
                        <?php else: ?>
                            <?php foreach ($notifs as $n): ?>
                                <li>
                                    <div class="dropdown-item small text-wrap border-bottom p-2 d-flex justify-content-between align-items-start">
                                        <div>
                                            <?php echo htmlspecialchars($n['message']); ?>
                                            <div class="text-muted" style="font-size: 0.7em;"><?php echo date('d/m H:i', strtotime($n['created_at'])); ?></div>
                                        </div>
                                        <a href="?delete_notif=<?php echo $n['id']; ?>" class="text-secondary ms-2"><i class="fas fa-times"></i></a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <a href="index.php" class="btn btn-sm btn-outline-secondary">Retour au site</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show"><?php echo $message; ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row">
            <!-- Sidebar: Add Product -->
            <div class="col-lg-4 mb-4">
                <div class="glass-panel p-4 sticky-top text-dark" style="top: 20px; z-index: 1;">
                    <h5 class="fw-bold mb-3"><?php echo $editMode ? 'Modifier' : 'Ajouter'; ?> un Produit</h5>
                    <form method="POST" action="admin.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'add'; ?>">
                        <?php if ($editMode): ?><input type="hidden" name="id" value="<?php echo $productToEdit['id']; ?>"><?php endif; ?>

                        <div class="mb-2">
                            <label class="form-label small mb-1">Nom</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($productToEdit['name']); ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Prix (XFA)</label>
                            <input type="number" name="price" class="form-control" value="<?php echo htmlspecialchars($productToEdit['price']); ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($productToEdit['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small mb-1">Image</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo $editMode ? 'Mettre à jour' : 'Ajouter'; ?></button>
                        <?php if ($editMode): ?><a href="admin.php" class="btn btn-sm btn-link text-center w-100 mt-2">Annuler</a><?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-8">
                
                <!-- Product List -->
                <div class="glass-panel p-4 mb-5">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Gestion des Stocks</h5>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table align-middle">
                            <thead class="sticky-top bg-light"><tr><th>Img</th><th>Produit</th><th>Act.</th></tr></thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                <tr class="<?php echo ($editMode && $p['id'] == $productToEdit['id']) ? 'table-warning' : ''; ?>">
                                    <td><img src="<?php echo htmlspecialchars($p['image_url']); ?>" width="40" height="40" class="rounded object-fit-cover"></td>
                                    <td>
                                        <div class="fw-bold small"><?php echo htmlspecialchars($p['name']); ?></div>
                                        <small class="text-muted"><?php echo number_format($p['price'], 0, ',', ' '); ?> XFA</small>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-light text-primary py-0 px-1"><i class="fas fa-pencil-alt"></i></a>
                                        <a href="?delete=<?php echo $p['id']; ?>" onclick="return confirm('Sur ?')" class="btn btn-sm btn-light text-danger py-0 px-1"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Reviews Management -->
                <div class="glass-panel p-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-warning"><i class="fas fa-star"></i> Derniers Avis Clients</h5>
                    <?php if (empty($reviews)): ?>
                        <p class="text-muted text-center py-3">Aucun avis pour le moment.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($reviews as $r): ?>
                                <div class="list-group-item bg-transparent border-bottom">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 text-primary fw-bold"><?php echo htmlspecialchars($r['product_name']); ?></h6>
                                        <small class="text-muted"><?php echo date('d/m', strtotime($r['created_at'])); ?></small>
                                    </div>
                                    <div class="mb-1">
                                        <span class="text-warning small me-2">
                                            <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                        </span>
                                        <small class="fw-bold"><?php echo htmlspecialchars($r['username']); ?></small>
                                    </div>
                                    <p class="mb-1 text-dark bg-white p-2 rounded shadow-sm fst-italic">"<?php echo htmlspecialchars($r['comment']); ?>"</p>
                                    <div class="text-end">
                                        <a href="?delete_review=<?php echo $r['review_id']; ?>" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.75rem;" onclick="return confirm('Supprimer cet avis ?')">Supprimer</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
