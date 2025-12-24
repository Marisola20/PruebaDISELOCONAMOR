<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'diseloco_DCAperu');
define('DB_USER', 'diseloco_MD');
define('DB_PASS', 'Y-PckJ9%2v3.bMSV');

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Crear tablas si no existen
createTables($conn);

function createTables($conn)
{
    // Tabla de usuarios
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        avatar VARCHAR(255),
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    // Tabla de categorías
    $sql_categories = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Tabla de productos
    $sql_products = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        old_price DECIMAL(10,2) DEFAULT 0,
        stock INT DEFAULT 0,
        category_id INT,
        image VARCHAR(255),
        images TEXT,
        rating DECIMAL(3,2) DEFAULT 0,
        review_count INT DEFAULT 0,
        is_featured BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )";

    // Tabla de promociones
    $sql_promotions = "CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        old_price DECIMAL(10,2) NOT NULL,
        new_price DECIMAL(10,2) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Tabla de carrito
    $sql_cart = "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";

    // Tabla de favoritos
    $sql_favorites = "CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_favorite (user_id, product_id)
    )";

    // Tabla de pedidos
    $sql_orders = "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_code VARCHAR(20) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        payment_method VARCHAR(50) DEFAULT 'yape',
        payment_proof VARCHAR(255),
        shipping_address TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    // Tabla de detalles de pedido
    $sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(200) NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";

    // Tabla de reseñas del foro
    $sql_forum_reviews = "CREATE TABLE IF NOT EXISTS forum_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    // Tabla de reseñas de productos
    $sql_product_reviews = "CREATE TABLE IF NOT EXISTS product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";

    // Tabla de configuración
    $sql_config = "CREATE TABLE IF NOT EXISTS site_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        config_key VARCHAR(100) UNIQUE NOT NULL,
        config_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    try {
        $conn->exec($sql_users);
        $conn->exec($sql_categories);
        $conn->exec($sql_products);
        $conn->exec($sql_promotions);
        $conn->exec($sql_cart);
        $conn->exec($sql_favorites);
        $conn->exec($sql_orders);
        $conn->exec($sql_order_items);
        $conn->exec($sql_forum_reviews);
        $conn->exec($sql_product_reviews);
        $conn->exec($sql_config);

        // Insertar configuración por defecto
        $default_config = [
            'whatsapp_number' => '51999999999',
            'yape_qr' => 'assets/images/yape-qr.png',
            'site_name' => 'DCA Perú',
            'site_description' => 'Tu tienda online de confianza'
        ];

        foreach ($default_config as $key => $value) {
            $stmt = $conn->prepare("INSERT IGNORE INTO site_config (config_key, config_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }

        // Crear usuario admin por defecto si no existe
        $admin_check = $conn->prepare("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
        $admin_check->execute();

        if ($admin_check->rowCount() == 0) {
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, is_admin) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute(['admin', 'admin@dcaperu.com', $admin_password, 'Administrador']);
        }

    } catch (PDOException $e) {
        error_log("Error creando tablas: " . $e->getMessage());
    }
}
?>