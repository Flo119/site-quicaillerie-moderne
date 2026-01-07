<?php
require_once 'db.php';

try {
    $commands = [
        "CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT DEFAULT 'order', -- 'order', 'message'
            message TEXT NOT NULL,
            is_read TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($commands as $command) {
        $pdo->exec($command);
    }
    echo "Table notifications créée.";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
