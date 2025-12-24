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
            case 'update_status':
                $order_id = intval($_POST['order_id']);
                $new_status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$new_status, $order_id])) {
                    header('Location: pedidos.php?success=Estado del pedido actualizado');
                    exit();
                } else {
                    $error = 'Error al actualizar el estado';
                }
                break;
                
            case 'delete_order':
                $order_id = intval($_POST['order_id']);
                
                $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
                if ($stmt->execute([$order_id])) {
                    header('Location: pedidos.php?success=Pedido eliminado exitosamente');
                    exit();
                } else {
                    $error = 'Error al eliminar el pedido';
                }
                break;
        }
    }
}

// Obtener pedidos con paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(order_code LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT o.*, u.username, u.full_name, u.email, u.phone,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        $where_clause
        ORDER BY $sort_by $sort_order
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Contar total
$count_sql = "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Estadísticas
$total_orders_count = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$paid_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn();
$total_sales = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('paid', 'shipped', 'delivered')")->fetchColumn() ?: 0;

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
    <title>Gestión de Pedidos - Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <h1><i class="fas fa-shopping-cart"></i> Gestión de Pedidos</h1>
                    <p>Administra y realiza seguimiento de los pedidos</p>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($total_orders_count); ?></h3>
                            <p>Total Pedidos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($pending_orders); ?></h3>
                            <p>Pendientes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($paid_orders); ?></h3>
                            <p>Pagados</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3>S/ <?php echo number_format($total_sales, 2); ?></h3>
                            <p>Total Ventas</p>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Buscar por código, usuario..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <select name="status">
                                <option value="">Todos los estados</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Pagado</option>
                                <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Enviado</option>
                                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Entregado</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </form>
                </div>

                <!-- Mensajes -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Lista de Pedidos -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Pedidos (<?php echo number_format($total_orders); ?>)</h3>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Método Pago</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="8" class="no-data">No se encontraron pedidos</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: #667eea;"><?php echo htmlspecialchars($order['order_code']); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['full_name'] ?: $order['username']); ?></strong>
                                                <br><small><?php echo htmlspecialchars($order['email']); ?></small>
                                            </td>
                                            <td><?php echo $order['item_count']; ?> item(s)</td>
                                            <td><strong style="color: #2ecc71;">S/ <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php
                                                    $status_labels = [
                                                        'pending' => 'Pendiente',
                                                        'paid' => 'Pagado',
                                                        'shipped' => 'Enviado',
                                                        'delivered' => 'Entregado',
                                                        'cancelled' => 'Cancelado'
                                                    ];
                                                    echo $status_labels[$order['status']] ?? $order['status'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="text-transform: capitalize;"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                                <?php if ($order['payment_proof']): ?>
                                                    <br><small><a href="../<?php echo htmlspecialchars($order['payment_proof']); ?>" target="_blank">Ver comprobante</a></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-success" onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['order_code']; ?>', '<?php echo $order['status']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteOrder(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_code']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Ver Detalles del Pedido -->
    <div id="orderModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 id="modalTitle">Detalles del Pedido</h2>
                <span class="close" onclick="closeOrderModal()">&times;</span>
            </div>
            <div id="orderDetails" style="padding: 2rem;">
                <p>Cargando detalles...</p>
            </div>
        </div>
    </div>

    <!-- Modal Actualizar Estado -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Actualizar Estado del Pedido</h2>
                <span class="close" onclick="closeStatusModal()">&times;</span>
            </div>
            <form id="statusForm" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="statusOrderId">
                
                <div class="form-group">
                    <label for="status">Nuevo Estado *</label>
                    <select id="status" name="status" required>
                        <option value="pending">Pendiente</option>
                        <option value="paid">Pagado</option>
                        <option value="shipped">Enviado</option>
                        <option value="delivered">Entregado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        function viewOrder(orderId) {
            fetch(`get_order.php?id=${orderId}`)
                .then(response => response.json())
                .then(order => {
                    if (order.success) {
                        const data = order.data;
                        const items = order.items || [];
                        
                        let html = `
                            <div style="margin-bottom: 2rem;">
                                <h3>Pedido: <strong>${data.order_code}</strong></h3>
                                <p><strong>Cliente:</strong> ${data.full_name || data.username}</p>
                                <p><strong>Email:</strong> ${data.email}</p>
                                <p><strong>Teléfono:</strong> ${data.phone || 'N/A'}</p>
                                <p><strong>Fecha:</strong> ${new Date(data.created_at).toLocaleString('es-PE')}</p>
                                <p><strong>Estado:</strong> <span class="status-badge status-${data.status}">${data.status}</span></p>
                                <p><strong>Total:</strong> S/ ${parseFloat(data.total_amount).toFixed(2)}</p>
                            </div>
                            
                            <h4>Items del Pedido:</h4>
                            <table class="data-table" style="width: 100%; margin-top: 1rem;">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        items.forEach(item => {
                            html += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>S/ ${parseFloat(item.unit_price).toFixed(2)}</td>
                                    <td>S/ ${parseFloat(item.total_price).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                </tbody>
                            </table>
                            
                            ${data.shipping_address ? `<p style="margin-top: 1rem;"><strong>Dirección de envío:</strong><br>${data.shipping_address}</p>` : ''}
                            ${data.notes ? `<p><strong>Notas:</strong><br>${data.notes}</p>` : ''}
                        `;
                        
                        document.getElementById('orderDetails').innerHTML = html;
                        document.getElementById('modalTitle').textContent = `Pedido: ${data.order_code}`;
                        
                        const modal = document.getElementById('orderModal');
                        modal.classList.add('show');
                        modal.style.display = 'flex';
                    } else {
                        alert('Error al cargar el pedido: ' + order.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el pedido');
                });
        }

        function updateOrderStatus(orderId, orderCode, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('status').value = currentStatus;
            
            const modal = document.getElementById('statusModal');
            modal.classList.add('show');
            modal.style.display = 'flex';
        }

        function closeOrderModal() {
            const modal = document.getElementById('orderModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }

        function closeStatusModal() {
            const modal = document.getElementById('statusModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }

        function deleteOrder(orderId, orderCode) {
            if (confirm(`¿Estás seguro de que quieres eliminar el pedido "${orderCode}"?\n\n⚠️ Esta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_order">
                    <input type="hidden" name="order_id" value="${orderId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            const orderModal = document.getElementById('orderModal');
            const statusModal = document.getElementById('statusModal');
            
            if (event.target === orderModal || event.target.classList.contains('close')) {
                closeOrderModal();
            }
            if (event.target === statusModal || event.target.classList.contains('close')) {
                closeStatusModal();
            }
        };
    </script>
</body>
</html>

