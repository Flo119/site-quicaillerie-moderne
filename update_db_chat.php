<?php
require_once 'db.php';

try {
    // Add recipient_id column to messages table
    // SQLite doesn't support IF NOT EXISTS for ADD COLUMN in older versions, but it's safe to try or capture error
    // We'll wrap in try-catch
    try {
        $pdo->exec("ALTER TABLE messages ADD COLUMN recipient_id INTEGER DEFAULT NULL");
        echo "Colonne 'recipient_id' ajoutée avec succès.<br>";
    } catch (PDOException $e) {
        // Column likely exists
        echo "Note: La colonne 'recipient_id' existe peut-être déjà.<br>";
    }
    
    echo "Mise à jour table messages terminée.";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
