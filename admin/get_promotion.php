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
    echo json_encode(['success' => false, 'message' => 'ID de promoción no válido']);
    exit();
}

$promotion_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([$promotion_id]);
    $promotion = $stmt->fetch();
    
    if ($promotion) {
        echo json_encode(['success' => true, 'data' => $promotion]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Promoción no encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

