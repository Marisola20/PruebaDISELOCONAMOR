<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Obtener estadísticas del dashboard
try {
    // Total de productos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products");
    $stmt->execute();
    $total_products = $stmt->fetch()['total'];

    // Total de usuarios
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
    $stmt->execute();
    $total_users = $stmt->fetch()['total'];

    // Total de pedidos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders");
    $stmt->execute();
    $total_orders = $stmt->fetch()['total'];

    // Total de ventas
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE status = 'paid'");
    $stmt->execute();
    $total_sales = $stmt->fetch()['total'] ?: 0;

    // Pedidos recientes
    $stmt = $conn->prepare("
        SELECT o.*, u.username, u.full_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();

    // Productos con bajo stock
    $stmt = $conn->prepare("SELECT * FROM products WHERE stock <= 5 AND is_active = 1 ORDER BY stock ASC LIMIT 5");
    $stmt->execute();
    $low_stock_products = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
    $total_products = $total_users = $total_orders = $total_sales = 0;
    $recent_orders = $low_stock_products = [];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - DCA Perú</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>DCA Perú</h2>
                <p>Administración</p>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="productos.php">
                            <i class="fas fa-box"></i>
                            Productos
                        </a>
                    </li>
                    <li>
                        <a href="categorias.php">
                            <i class="fas fa-tags"></i>
                            Categorías
                        </a>
                    </li>
                    <li>
                        <a href="promociones.php">
                            <i class="fas fa-percentage"></i>
                            Promociones
                        </a>
                    </li>
                    <li>
                        <a href="pedidos.php">
                            <i class="fas fa-shopping-cart"></i>
                            Pedidos
                        </a>
                    </li>
                    <li>
                        <a href="usuarios.php">
                            <i class="fas fa-users"></i>
                            Usuarios
                        </a>
                    </li>
                    <li>
                        <a href="reseñas.php">
                            <i class="fas fa-star"></i>
                            Reseñas
                        </a>
                    </li>
                    <li>
                        <a href="configuracion.php">
                            <i class="fas fa-cog"></i>
                            Configuración
                        </a>
                    </li>
                    <li>
                        <a href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            Ver Sitio
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="content-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                    <p>Bienvenido al panel de administración</p>
                </div>
                <div class="header-right">
                    <div class="admin-info">
                        <span>Hola, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <small><?php echo date('d/m/Y H:i'); ?></small>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($total_products); ?></h3>
                            <p>Total Productos</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($total_users); ?></h3>
                            <p>Usuarios Registrados</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($total_orders); ?></h3>
                            <p>Total Pedidos</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>S/ <?php echo number_format($total_sales, 2); ?></h3>
                            <p>Total Ventas</p>
                        </div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Recent Orders -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Pedidos Recientes</h3>
                            <a href="pedidos.php" class="btn-link">Ver Todos</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($recent_orders)): ?>
                                <p class="no-data">No hay pedidos recientes</p>
                            <?php else: ?>
                                <div class="orders-list">
                                    <?php foreach ($recent_orders as $order): ?>
                                        <div class="order-item">
                                            <div class="order-info">
                                                <h4>Pedido #<?php echo $order['order_code']; ?></h4>
                                                <p><?php echo htmlspecialchars($order['full_name']); ?></p>
                                                <small><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                            </div>
                                            <div class="order-status">
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                                <span class="order-amount">S/
                                                    <?php echo number_format($order['total_amount'], 2); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Low Stock Products -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Productos con Bajo Stock</h3>
                            <a href="productos.php" class="btn-link">Ver Todos</a>
                        </div>
                        <div class="card-content">
                            <?php if (empty($low_stock_products)): ?>
                                <p class="no-data">Todos los productos tienen stock suficiente</p>
                            <?php else: ?>
                                <div class="products-list">
                                    <?php foreach ($low_stock_products as $product): ?>
                                        <div class="product-item">
                                            <div class="product-image">
                                                <img src="../<?php echo $product['image'] ?: 'assets/images/placeholder.png'; ?>"
                                                    alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            </div>
                                            <div class="product-info">
                                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <p class="stock-warning">Stock: <?php echo $product['stock']; ?></p>
                                            </div>
                                            <a href="productos.php?edit=<?php echo $product['id']; ?>" class="btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Acciones Rápidas</h3>
                    <div class="actions-grid">
                        <a href="productos.php?action=add" class="action-card">
                            <i class="fas fa-plus"></i>
                            <span>Agregar Producto</span>
                        </a>
                        <a href="promociones.php?action=add" class="action-card">
                            <i class="fas fa-percentage"></i>
                            <span>Crear Promoción</span>
                        </a>
                        <a href="categorias.php?action=add" class="action-card">
                            <i class="fas fa-tag"></i>
                            <span>Nueva Categoría</span>
                        </a>
                        <a href="configuracion.php" class="action-card">
                            <i class="fas fa-cog"></i>
                            <span>Configuración</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>

</html>