<?php
require_once 'db.php';

try {
    $commands = [
        "CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            rating INTEGER DEFAULT 5,
            comment TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )"
    ];

    foreach ($commands as $command) {
        $pdo->exec($command);
    }
    echo "Table reviews créée.";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
