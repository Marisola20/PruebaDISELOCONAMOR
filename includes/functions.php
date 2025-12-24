<?php
// Funciones principales del sistema

// Obtener productos destacados
function getFeaturedProducts($conn, $limit = 8) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name, 
                   COALESCE(AVG(pr.rating), 0) as rating,
                   COUNT(pr.id) as review_count
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_reviews pr ON p.id = pr.product_id
            WHERE p.is_featured = 1 AND p.is_active = 1
            GROUP BY p.id
            ORDER BY p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo productos destacados: " . $e->getMessage());
        return [];
    }
}

// Obtener promociones activas
function getActivePromotions($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM promotions 
            WHERE is_active = 1 
            AND start_date <= CURDATE() 
            AND end_date >= CURDATE()
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo promociones: " . $e->getMessage());
        return [];
    }
}

// Obtener reseñas del foro
function getForumReviews($conn, $limit = 6) {
    try {
        $stmt = $conn->prepare("
            SELECT fr.*, u.username as user_name, u.avatar as user_avatar
            FROM forum_reviews fr
            JOIN users u ON fr.user_id = u.id
            ORDER BY fr.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo reseñas del foro: " . $e->getMessage());
        return [];
    }
}

// Obtener productos por categoría
function getProductsByCategory($conn, $category_id, $limit = 12, $offset = 0) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name,
                   COALESCE(AVG(pr.rating), 0) as rating,
                   COUNT(pr.id) as review_count
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_reviews pr ON p.id = pr.product_id
            WHERE p.category_id = ? AND p.is_active = 1
            GROUP BY p.id
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$category_id, $limit, $offset]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo productos por categoría: " . $e->getMessage());
        return [];
    }
}

// Obtener todas las categorías
function getAllCategories($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo categorías: " . $e->getMessage());
        return [];
    }
}

// Obtener producto por ID
function getProductById($conn, $product_id) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name,
                   COALESCE(AVG(pr.rating), 0) as rating,
                   COUNT(pr.id) as review_count
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_reviews pr ON p.id = pr.product_id
            WHERE p.id = ? AND p.is_active = 1
            GROUP BY p.id
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error obteniendo producto: " . $e->getMessage());
        return false;
    }
}

// Obtener reseñas de un producto
function getProductReviews($conn, $product_id, $limit = 10) {
    try {
        $stmt = $conn->prepare("
            SELECT pr.*, u.username as user_name, u.avatar as user_avatar
            FROM product_reviews pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.product_id = ?
            ORDER BY pr.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$product_id, $limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo reseñas del producto: " . $e->getMessage());
        return [];
    }
}

// Agregar producto al carrito
function addToCart($conn, $user_id, $product_id, $quantity = 1) {
    try {
        // Verificar si ya existe en el carrito
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Actualizar cantidad
            $new_quantity = $existing['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $existing['id']]);
        } else {
            // Agregar nuevo item
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }
        return true;
    } catch(PDOException $e) {
        error_log("Error agregando al carrito: " . $e->getMessage());
        return false;
    }
}

// Obtener carrito del usuario
function getUserCart($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT c.*, p.name, p.price, p.image, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo carrito: " . $e->getMessage());
        return [];
    }
}

// Calcular total del carrito
function getCartTotal($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT SUM(c.quantity * p.price) as total
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['total'] ?: 0;
    } catch(PDOException $e) {
        error_log("Error calculando total del carrito: " . $e->getMessage());
        return 0;
    }
}

// Crear pedido
function createOrder($conn, $user_id, $total_amount, $shipping_address, $notes = '') {
    try {
        $conn->beginTransaction();
        
        // Generar código de pedido único
        $order_code = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        // Crear pedido
        $stmt = $conn->prepare("
            INSERT INTO orders (order_code, user_id, total_amount, shipping_address, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$order_code, $user_id, $total_amount, $shipping_address, $notes]);
        $order_id = $conn->lastInsertId();
        
        // Obtener items del carrito
        $cart_items = getUserCart($conn, $user_id);
        
        // Crear items del pedido
        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']]);
        }
        
        // Limpiar carrito
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $conn->commit();
        return $order_code;
    } catch(PDOException $e) {
        $conn->rollBack();
        error_log("Error creando pedido: " . $e->getMessage());
        return false;
    }
}

// Obtener pedidos del usuario
function getUserOrders($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo pedidos: " . $e->getMessage());
        return [];
    }
}

// Obtener detalles de un pedido
function getOrderDetails($conn, $order_code) {
    try {
        $stmt = $conn->prepare("
            SELECT o.*, u.username, u.full_name, u.phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.order_code = ?
        ");
        $stmt->execute([$order_code]);
        $order = $stmt->fetch();
        
        if ($order) {
            $stmt = $conn->prepare("
                SELECT * FROM order_items WHERE order_id = ?
            ");
            $stmt->execute([$order['id']]);
            $order['items'] = $stmt->fetchAll();
        }
        
        return $order;
    } catch(PDOException $e) {
        error_log("Error obteniendo detalles del pedido: " . $e->getMessage());
        return false;
    }
}

// Agregar a favoritos
function addToFavorites($conn, $user_id, $product_id) {
    try {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO favorites (user_id, product_id) 
            VALUES (?, ?)
        ");
        $stmt->execute([$user_id, $product_id]);
        return true;
    } catch(PDOException $e) {
        error_log("Error agregando a favoritos: " . $e->getMessage());
        return false;
    }
}

// Remover de favoritos
function removeFromFavorites($conn, $user_id, $product_id) {
    try {
        $stmt = $conn->prepare("
            DELETE FROM favorites 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$user_id, $product_id]);
        return true;
    } catch(PDOException $e) {
        error_log("Error removiendo de favoritos: " . $e->getMessage());
        return false;
    }
}

// Obtener favoritos del usuario
function getUserFavorites($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, c.name as category_name
            FROM favorites f
            JOIN products p ON f.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE f.user_id = ? AND p.is_active = 1
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error obteniendo favoritos: " . $e->getMessage());
        return [];
    }
}

// Verificar si un producto está en favoritos
function isProductInFavorites($conn, $user_id, $product_id) {
    try {
        $stmt = $conn->prepare("
            SELECT id FROM favorites 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$user_id, $product_id]);
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Error verificando favoritos: " . $e->getMessage());
        return false;
    }
}

// Agregar reseña del foro
function addForumReview($conn, $user_id, $rating, $comment) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO forum_reviews (user_id, rating, comment)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $rating, $comment]);
        return true;
    } catch(PDOException $e) {
        error_log("Error agregando reseña del foro: " . $e->getMessage());
        return false;
    }
}

// Agregar reseña de producto
function addProductReview($conn, $user_id, $product_id, $rating, $comment) {
    try {
        $conn->beginTransaction();
        
        // Agregar reseña
        $stmt = $conn->prepare("
            INSERT INTO product_reviews (user_id, product_id, rating, comment)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $product_id, $rating, $comment]);
        
        // Actualizar rating promedio del producto
        $stmt = $conn->prepare("
            UPDATE products p 
            SET rating = (
                SELECT AVG(rating) 
                FROM product_reviews 
                WHERE product_id = ?
            ),
            review_count = (
                SELECT COUNT(*) 
                FROM product_reviews 
                WHERE product_id = ?
            )
            WHERE id = ?
        ");
        $stmt->execute([$product_id, $product_id, $product_id]);
        
        $conn->commit();
        return true;
    } catch(PDOException $e) {
        $conn->rollBack();
        error_log("Error agregando reseña de producto: " . $e->getMessage());
        return false;
    }
}

// Obtener configuración del sitio
function getSiteConfig($conn, $key = null) {
    try {
        if ($key) {
            $stmt = $conn->prepare("SELECT config_value FROM site_config WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            return $result ? $result['config_value'] : null;
        } else {
            $stmt = $conn->prepare("SELECT * FROM site_config");
            $stmt->execute();
            $configs = $stmt->fetchAll();
            $result = [];
            foreach ($configs as $config) {
                $result[$config['config_key']] = $config['config_value'];
            }
            return $result;
        }
    } catch(PDOException $e) {
        error_log("Error obteniendo configuración: " . $e->getMessage());
        return null;
    }
}

// Actualizar configuración del sitio
function updateSiteConfig($conn, $key, $value) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO site_config (config_key, config_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE config_value = ?
        ");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch(PDOException $e) {
        error_log("Error actualizando configuración: " . $e->getMessage());
        return false;
    }
}

// Función de búsqueda de productos
function searchProducts($conn, $query, $category_id = null, $limit = 12, $offset = 0) {
    try {
        $sql = "
            SELECT p.*, c.name as category_name,
                   COALESCE(AVG(pr.rating), 0) as rating,
                   COUNT(pr.id) as review_count
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_reviews pr ON p.id = pr.product_id
            WHERE p.is_active = 1 
            AND (p.name LIKE ? OR p.description LIKE ?)
        ";
        $params = ["%$query%", "%$query%"];
        
        if ($category_id) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error buscando productos: " . $e->getMessage());
        return [];
    }
}

// Función para generar código de pedido único
function generateOrderCode() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para sanitizar input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
