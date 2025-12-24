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
    echo json_encode(['success' => false, 'message' => 'ID de usuario no válido']);
    exit();
}

$user_id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT id, username, email, full_name, phone, address, is_active, is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

