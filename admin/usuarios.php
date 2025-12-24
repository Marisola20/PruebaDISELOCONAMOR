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
            case 'update_user':
                $target_user_id = intval($_POST['user_id']);
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $is_admin = isset($_POST['is_admin']) ? 1 : 0;
                
                // No permitir desactivar al usuario actual si es admin
                if ($target_user_id == $user_id && $is_admin == 0) {
                    $error = 'No puedes quitar tus propios permisos de administrador';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, is_active = ?, is_admin = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$full_name, $email, $phone, $address, $is_active, $is_admin, $target_user_id])) {
                        header('Location: usuarios.php?success=Usuario actualizado exitosamente');
                        exit();
                    } else {
                        $error = 'Error al actualizar el usuario';
                    }
                }
                break;
                
            case 'delete_user':
                $target_user_id = intval($_POST['user_id']);
                
                // No permitir eliminarse a sí mismo
                if ($target_user_id == $user_id) {
                    $error = 'No puedes eliminar tu propia cuenta';
                } else {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    if ($stmt->execute([$target_user_id])) {
                        header('Location: usuarios.php?success=Usuario eliminado exitosamente');
                        exit();
                    } else {
                        $error = 'Error al eliminar el usuario';
                    }
                }
                break;
        }
    }
}

// Obtener usuarios con paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter !== '') {
    $where_conditions[] = "is_admin = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $where_conditions[] = "is_active = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
        (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status IN ('paid', 'shipped', 'delivered')) as total_spent
        FROM users u
        $where_clause
        ORDER BY $sort_by $sort_order
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Contar total
$count_sql = "SELECT COUNT(*) FROM users $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Estadísticas
$total_users_count = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$admin_count = $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
$active_users_count = $conn->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();

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
    <title>Gestión de Usuarios - Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <h1><i class="fas fa-users"></i> Gestión de Usuarios</h1>
                    <p>Administra los usuarios y clientes del sistema</p>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($total_users_count); ?></h3>
                            <p>Total Usuarios</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($admin_count); ?></h3>
                            <p>Administradores</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($active_users_count); ?></h3>
                            <p>Usuarios Activos</p>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Buscar usuarios..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <select name="role">
                                <option value="">Todos los roles</option>
                                <option value="1" <?php echo $role_filter == '1' ? 'selected' : ''; ?>>Administradores</option>
                                <option value="0" <?php echo $role_filter == '0' ? 'selected' : ''; ?>>Clientes</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select name="status">
                                <option value="">Todos</option>
                                <option value="1" <?php echo $status_filter == '1' ? 'selected' : ''; ?>>Activos</option>
                                <option value="0" <?php echo $status_filter == '0' ? 'selected' : ''; ?>>Inactivos</option>
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

                <!-- Lista de Usuarios -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Usuarios (<?php echo number_format($total_users); ?>)</h3>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Pedidos</th>
                                    <th>Total Gastado</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" class="no-data">No se encontraron usuarios</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                <?php if ($user['id'] == $user_id): ?>
                                                    <span style="color: #667eea; font-size: 0.8rem;">(Tú)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                                            <td><?php echo $user['order_count'] ?? 0; ?></td>
                                            <td>S/ <?php echo number_format($user['total_spent'] ?: 0, 2); ?></td>
                                            <td>
                                                <span class="featured <?php echo $user['is_admin'] ? 'yes' : 'no'; ?>">
                                                    <?php echo $user['is_admin'] ? 'Administrador' : 'Cliente'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="featured <?php echo $user['is_active'] ? 'yes' : 'no'; ?>">
                                                    <?php echo $user['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($user['id'] != $user_id): ?>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>" class="page-link">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Editar Usuario -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Usuario</h2>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <form id="userForm" method="POST">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="userId">
                
                <div class="form-group full-width">
                    <label for="full_name">Nombre Completo *</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="phone">Teléfono</label>
                    <input type="text" id="phone" name="phone">
                </div>
                
                <div class="form-group full-width">
                    <label for="address">Dirección</label>
                    <textarea id="address" name="address" rows="2"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_active" name="is_active">
                            <span class="checkmark"></span>
                            Usuario activo
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_admin" name="is_admin">
                            <span class="checkmark"></span>
                            Es administrador
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        function editUser(userId) {
            fetch(`get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(user => {
                    if (user.success) {
                        const data = user.data;
                        document.getElementById('userId').value = data.id;
                        document.getElementById('full_name').value = data.full_name;
                        document.getElementById('email').value = data.email;
                        document.getElementById('phone').value = data.phone || '';
                        document.getElementById('address').value = data.address || '';
                        document.getElementById('is_active').checked = data.is_active == 1;
                        document.getElementById('is_admin').checked = data.is_admin == 1;
                        
                        const modal = document.getElementById('userModal');
                        modal.classList.add('show');
                        modal.style.display = 'flex';
                    } else {
                        alert('Error al cargar el usuario: ' + user.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el usuario');
                });
        }

        function closeUserModal() {
            const modal = document.getElementById('userModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }

        function deleteUser(userId, username) {
            if (confirm(`¿Estás seguro de que quieres eliminar el usuario "${username}"?\n\n⚠️ Esta acción eliminará todos sus pedidos y datos asociados.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal || event.target.classList.contains('close')) {
                closeUserModal();
            }
        };
    </script>
</body>
</html>

