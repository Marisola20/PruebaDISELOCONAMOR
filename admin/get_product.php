<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que sea admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Verificar que se proporcione un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit();
}

$product_id = intval($_GET['id']);

try {
    // Obtener datos del producto
    $stmt = $conn->prepare("SELECT id, name, description, price, category_id, stock, image, is_featured FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo json_encode([
            'success' => true,
            'data' => $product
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el producto: ' . $e->getMessage()
    ]);
}
?>
