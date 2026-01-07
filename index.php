<?php
require_once 'db.php';
session_start();

$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Calculate average ratings
$reviewsStmt = $pdo->query("SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as count FROM reviews GROUP BY product_id");
$ratings = [];
while ($row = $reviewsStmt->fetch()) {
    $ratings[$row['product_id']] = $row;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quincaillerie Moderne</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 5px;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffd700;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top navbar-glass" style="z-index: 1000;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-hammer text-primary"></i> Quincaillerie<span class="text-info">Plus</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="#">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="#produits">Produits</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link fw-bold text-primary" href="cart.php"><i class="fas fa-shopping-cart"></i> Panier</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle fa-lg"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin.php">Administration</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-2">
                            <a href="login.php" class="btn btn-modern btn-sm">Boutique / Connexion</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section d-flex align-items-center">
        <div class="floating-shapes">
            <div style="top: 15%; left: 5%; width: 100px; height: 100px; background: rgba(59, 130, 246, 0.1); border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;"></div>
            <div style="bottom: 10%; right: 5%; width: 150px; height: 150px; background: rgba(14, 165, 233, 0.1); border-radius: 63% 37% 54% 46% / 55% 48% 52% 45%; animation-delay: -5s;"></div>
            <div style="top: 40%; right: 40%; width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; animation-delay: -2s;"></div>
            <div style="top: 80%; left: 20%; width: 80px; height: 80px; border: 2px solid rgba(59, 130, 246, 0.1); background: transparent; animation-duration: 20s;"></div>
        </div>
        <div class="container text-center z-1 position-relative">
            <h1 class="display-3 hero-title mb-4">Le Matériel de Construction<br>Réinventé</h1>
            <p class="lead text-muted mb-5" style="max-width: 600px; margin: 0 auto;">
                Qualité supérieure, prix compétitifs et service client moderne. 
                Trouvez tout ce dont vous avez besoin pour vos chantiers.
            </p>
            <a href="#produits" class="btn btn-modern btn-lg shadow-lg">Explorer le catalogue <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
    </header>

    <!-- Products Section -->
    <section id="produits" class="py-5">
        <div class="container">
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                    <i class="fas fa-star text-warning"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="fw-bold fs-1">Nos Produits Phares</h2>
                    <div style="width: 50px; height: 4px; background: var(--accent-color); margin: 10px auto; border-radius: 2px;"></div>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card glass-panel product-card">
                        <div class="price-tag"><?php echo number_format($product['price'], 0, ',', ' '); ?> XFA</div>
                        <div class="product-img-wrapper">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title fw-bold m-0"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <?php if (isset($ratings[$product['id']])): ?>
                                    <div class="text-warning small fw-bold">
                                        <i class="fas fa-star"></i> <?php echo number_format($ratings[$product['id']]['avg_rating'], 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="card-text text-muted small mt-2"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <a href="cart_actions.php?action=add&id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                    <i class="fas fa-shopping-basket me-1"></i> Réserver
                                </a>
                                <!-- Heart Button triggers Modal -->
                                <button class="btn btn-light btn-sm rounded-circle text-danger shadow-sm" 
                                        onclick="openReviewModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')"
                                        title="Donner un avis">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-panel border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Noter <span id="modalProductName" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="submit_review.php" method="POST">
                        <input type="hidden" name="product_id" id="modalProductId">
                        
                        <div class="mb-3 text-center">
                            <label class="form-label text-muted small">Votre note</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5 étoiles"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 étoiles"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 étoiles"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 étoiles"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 étoile"><i class="fas fa-star"></i></label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Votre avis (Optionnel)</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Ce produit est..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-modern w-100">Envoyer mon avis</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Widget -->
    <div class="chat-widget dropup">
        <div class="chat-btn" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-comments"></i>
        </div>
        <ul class="dropdown-menu mb-3 border-0 shadow-lg p-3" style="width: 300px; border-radius: 15px;">
            <li><h6 class="dropdown-header">Discutez avec nous</h6></li>
            <!-- Lien WhatsApp: Remplacez le numéro 237xxxx par le vôtre -->
            <li><a class="dropdown-item rounded p-2 mb-1 d-flex align-items-center" href="https://wa.me/237680693205?text=Bonjour%20que%20que%20recherchez%20vous%20?%" target="_blank"><i class="fab fa-whatsapp text-success fa-2x me-3"></i> WhatsApp</a></li>
            
            <!-- Lien Telegram: Remplacez 'username' par votre nom d'utilisateur -->
            <li><a class="dropdown-item rounded p-2 mb-1 d-flex align-items-center" href="https://t.me/votre_username" target="_blank"><i class="fab fa-telegram text-primary fa-2x me-3"></i> Telegram</a></li>
            
            <!-- Lien Messenger -->
            <li><a class="dropdown-item rounded p-2 mb-1 d-flex align-items-center" href="https://m.me/votre_page_id" target="_blank"><i class="fab fa-facebook-messenger text-primary fa-2x me-3"></i> Messenger</a></li>
            
            <li><hr class="dropdown-divider"></li>
            <!-- Chat Interne -->
            <li><a class="dropdown-item rounded p-2 mb-1 d-flex align-items-center bg-light" href="chat.php"><i class="fas fa-comment-dots text-dark fa-2x me-3"></i> Chat Site Web</a></li>
        </ul>
    </div>

    <footer class="bg-white py-4 mt-5 border-top">
        <div class="container text-center text-muted">
            <p>&copy; <?php echo date('Y'); ?> Quincaillerie Moderne. Développé avec passion.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        function openReviewModal(id, name) {
            document.getElementById('modalProductId').value = id;
            document.getElementById('modalProductName').innerText = name;
            var myModal = new bootstrap.Modal(document.getElementById('reviewModal'));
            myModal.show();
        }
    </script>
</body>
</html>
