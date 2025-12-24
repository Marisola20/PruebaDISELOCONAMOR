<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido']);
    exit();
}

$order_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("
        SELECT o.*, u.username, u.full_name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $order,
            'items' => $items
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

