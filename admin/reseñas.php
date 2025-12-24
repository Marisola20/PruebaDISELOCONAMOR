<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_review':
                $review_id = intval($_POST['review_id']);
                $review_type = $_POST['review_type'];
                
                $table = $review_type == 'forum' ? 'forum_reviews' : 'product_reviews';
                $stmt = $conn->prepare("UPDATE $table SET is_approved = 1 WHERE id = ?");
                if ($stmt->execute([$review_id])) {
                    header('Location: reseñas.php?success=Reseña aprobada');
                    exit();
                } else {
                    $error = 'Error al aprobar la reseña';
                }
                break;
                
            case 'reject_review':
                $review_id = intval($_POST['review_id']);
                $review_type = $_POST['review_type'];
                
                $table = $review_type == 'forum' ? 'forum_reviews' : 'product_reviews';
                $stmt = $conn->prepare("UPDATE $table SET is_approved = 0 WHERE id = ?");
                if ($stmt->execute([$review_id])) {
                    header('Location: reseñas.php?success=Reseña rechazada');
                    exit();
                } else {
                    $error = 'Error al rechazar la reseña';
                }
                break;
                
            case 'delete_review':
                $review_id = intval($_POST['review_id']);
                $review_type = $_POST['review_type'];
                
                $table = $review_type == 'forum' ? 'forum_reviews' : 'product_reviews';
                $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
                if ($stmt->execute([$review_id])) {
                    // Si es reseña de producto, actualizar rating
                    if ($review_type == 'product') {
                        $product_id = intval($_POST['product_id']);
                        $stmt = $conn->prepare("
                            UPDATE products p 
                            SET rating = (
                                SELECT COALESCE(AVG(rating), 0) 
                                FROM product_reviews 
                                WHERE product_id = ? AND is_approved = 1
                            ),
                            review_count = (
                                SELECT COUNT(*) 
                                FROM product_reviews 
                                WHERE product_id = ? AND is_approved = 1
                            )
                            WHERE id = ?
                        ");
                        $stmt->execute([$product_id, $product_id, $product_id]);
                    }
                    header('Location: reseñas.php?success=Reseña eliminada');
                    exit();
                } else {
                    $error = 'Error al eliminar la reseña';
                }
                break;
        }
    }
}

// Obtener tipo de reseñas a mostrar
$type = $_GET['type'] ?? 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$reviews = [];
$total_reviews = 0;
$total_pages = 1;

// Obtener reseñas según el tipo
if ($type == 'forum' || $type == 'all') {
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.username LIKE ? OR fr.comment LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "fr.is_approved = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT fr.*, u.username, u.full_name, u.avatar
            FROM forum_reviews fr
            JOIN users u ON fr.user_id = u.id
            $where_clause
            ORDER BY fr.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $forum_reviews = $stmt->fetchAll();
    
    foreach ($forum_reviews as $review) {
        $review['review_type'] = 'forum';
        $reviews[] = $review;
    }
    
    $count_sql = "SELECT COUNT(*) FROM forum_reviews fr JOIN users u ON fr.user_id = u.id $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_reviews = $count_stmt->fetchColumn();
}

if ($type == 'product' || $type == 'all') {
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.username LIKE ? OR pr.comment LIKE ? OR p.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "pr.is_approved = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT pr.*, u.username, u.full_name, u.avatar, p.name as product_name, p.id as product_id
            FROM product_reviews pr
            JOIN users u ON pr.user_id = u.id
            JOIN products p ON pr.product_id = p.id
            $where_clause
            ORDER BY pr.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $product_reviews = $stmt->fetchAll();
    
    foreach ($product_reviews as $review) {
        $review['review_type'] = 'product';
        $reviews[] = $review;
    }
    
    $count_sql = "SELECT COUNT(*) FROM product_reviews pr JOIN users u ON pr.user_id = u.id JOIN products p ON pr.product_id = p.id $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_reviews += $count_stmt->fetchColumn();
}

$total_pages = ceil($total_reviews / $limit);

// Estadísticas
$total_forum_reviews = $conn->query("SELECT COUNT(*) FROM forum_reviews")->fetchColumn();
$total_product_reviews = $conn->query("SELECT COUNT(*) FROM product_reviews")->fetchColumn();
$pending_reviews = $conn->query("SELECT COUNT(*) FROM (
    SELECT id FROM forum_reviews WHERE is_approved = 0
    UNION ALL
    SELECT id FROM product_reviews WHERE is_approved = 0
) AS pending")->fetchColumn();

// Mostrar mensajes
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reseñas - Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <h1><i class="fas fa-star"></i> Gestión de Reseñas</h1>
                    <p>Modera las reseñas del foro y productos</p>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($total_forum_reviews); ?></h3>
                            <p>Reseñas del Foro</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($total_product_reviews); ?></h3>
                            <p>Reseñas de Productos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($pending_reviews); ?></h3>
                            <p>Pendientes de Aprobar</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs y Filtros -->
                <div class="filters-section">
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="?type=all&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="btn <?php echo $type == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                                Todas
                            </a>
                            <a href="?type=forum&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="btn <?php echo $type == 'forum' ? 'btn-primary' : 'btn-secondary'; ?>">
                                Foro
                            </a>
                            <a href="?type=product&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="btn <?php echo $type == 'product' ? 'btn-primary' : 'btn-secondary'; ?>">
                                Productos
                            </a>
                        </div>
                        
                        <form method="GET" style="display: flex; gap: 1rem; flex: 1; min-width: 300px;">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                            <input type="text" name="search" placeholder="Buscar reseñas..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1;">
                            <select name="status">
                                <option value="">Todas</option>
                                <option value="1" <?php echo $status_filter == '1' ? 'selected' : ''; ?>>Aprobadas</option>
                                <option value="0" <?php echo $status_filter == '0' ? 'selected' : ''; ?>>Pendientes</option>
                            </select>
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Lista de Reseñas -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Reseñas (<?php echo number_format($total_reviews); ?>)</h3>
                    </div>

                    <div class="table-container">
                        <?php if (empty($reviews)): ?>
                            <div class="no-data">No se encontraron reseñas</div>
                        <?php else: ?>
                            <div style="padding: 1.5rem;">
                                <?php foreach ($reviews as $review): ?>
                                    <div style="border-bottom: 1px solid #e1e5e9; padding: 1.5rem 0; display: flex; gap: 1.5rem;">
                                        <div style="flex-shrink: 0;">
                                            <div style="width: 50px; height: 50px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem;">
                                                <?php echo strtoupper(substr($review['username'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($review['full_name'] ?: $review['username']); ?></strong>
                                                    <?php if ($review['review_type'] == 'product'): ?>
                                                        <br><small style="color: #666;">Producto: <?php echo htmlspecialchars($review['product_name']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="text-align: right;">
                                                    <div style="color: #ffd700; margin-bottom: 0.3rem;">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <small style="color: #666;"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></small>
                                                </div>
                                            </div>
                                            <p style="color: #333; margin: 0.5rem 0;"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                                <?php if ($review['is_approved'] == 0): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="approve_review">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                        <input type="hidden" name="review_type" value="<?php echo $review['review_type']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Aprobar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="reject_review">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                        <input type="hidden" name="review_type" value="<?php echo $review['review_type']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-times"></i> Rechazar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta reseña?');">
                                                    <input type="hidden" name="action" value="delete_review">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <input type="hidden" name="review_type" value="<?php echo $review['review_type']; ?>">
                                                    <?php if ($review['review_type'] == 'product'): ?>
                                                        <input type="hidden" name="product_id" value="<?php echo $review['product_id']; ?>">
                                                    <?php endif; ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                                
                                                <span class="featured <?php echo $review['is_approved'] ? 'yes' : 'no'; ?>" style="margin-left: auto;">
                                                    <?php echo $review['is_approved'] ? 'Aprobada' : 'Pendiente'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Paginación -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?type=<?php echo $type; ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?type=<?php echo $type; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?type=<?php echo $type; ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>

