<?php
require_once 'db.php';

try {
    $commands = [
        "CREATE TABLE IF NOT EXISTS reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            status TEXT DEFAULT 'pending', -- 'pending', 'confirmed'
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        "CREATE TABLE IF NOT EXISTS reservation_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reservation_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER DEFAULT 1,
            FOREIGN KEY (reservation_id) REFERENCES reservations(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )"
    ];

    foreach ($commands as $command) {
        $pdo->exec($command);
    }
    echo "Tables de réservation créées avec succès.";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
