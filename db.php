<?php
// db.php
try {
    // Create (connect to) SQLite database in file
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    // Set errormode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create Tables if not exists
    $commands = [
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'client', -- 'client' or 'admin'
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            image_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )"
    ];

    foreach ($commands as $command) {
        $pdo->exec($command);
    }
    
    // Seed admin user if not exists (password: admin123)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, email, password, role) VALUES ('Admin', 'admin@quincaillerie.com', '$pass', 'admin')");
    }

    // Seed some products if empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $products = [
            ['Marteau de Charpentier', 'Marteau robuste avec manche ergonomique.', 5000, 'https://images.unsplash.com/photo-1586864387967-d02ef85d93e8?auto=format&fit=crop&w=400&q=80'],
            ['Perceuse Sans Fil', 'Perceuse 18V avec kit de mèches inclus.', 45000, 'https://images.unsplash.com/photo-1504148455328-c376907d081c?auto=format&fit=crop&w=400&q=80'],
            ['Tournevis Multi-embouts', 'Set de tournevis de précision.', 3500, 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?auto=format&fit=crop&w=400&q=80'],
            ['Ciment 50kg', 'Sac de ciment haute résistance.', 6000, 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=400&q=80'],
            ['Peinture Blanche 20L', 'Peinture acrylique intérieur/extérieur.', 25000, 'https://images.unsplash.com/photo-1562259949-e8e7689d7828?auto=format&fit=crop&w=400&q=80'],
            ['Brouette Métallique', 'Brouette renforcée pour travaux lourds.', 20000, 'https://plus.unsplash.com/premium_photo-1663045618451-b0db04b39794?auto=format&fit=crop&w=400&q=80']
        ];
        
        $insert = $pdo->prepare("INSERT INTO products (name, description, price, image_url) VALUES (?, ?, ?, ?)");
        foreach ($products as $p) {
            $insert->execute($p);
        }
    }

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
