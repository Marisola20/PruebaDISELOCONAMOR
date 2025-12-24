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
            case 'add_category':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Verificar si la categoría ya existe
                $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->rowCount() > 0) {
                    $error = 'Ya existe una categoría con ese nombre';
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories (name, description, is_active, created_at) VALUES (?, ?, ?, NOW())");
                    if ($stmt->execute([$name, $description, $is_active])) {
                        $success = 'Categoría agregada exitosamente';
                    } else {
                        $error = 'Error al agregar la categoría';
                    }
                }
                break;
                
            case 'update_category':
                $category_id = intval($_POST['category_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Verificar si el nombre ya existe en otra categoría
                $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $stmt->execute([$name, $category_id]);
                if ($stmt->rowCount() > 0) {
                    $error = 'Ya existe una categoría con ese nombre';
                } else {
                    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$name, $description, $is_active, $category_id])) {
                        $success = 'Categoría actualizada exitosamente';
                    } else {
                        $error = 'Error al actualizar la categoría';
                    }
                }
                break;
                
            case 'delete_category':
                $category_id = intval($_POST['category_id']);
                
                // Verificar si hay productos en esta categoría
                $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $product_count = $stmt->fetchColumn();
                
                if ($product_count > 0) {
                    $error = "No se puede eliminar la categoría porque tiene $product_count productos asociados";
                } else {
                    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$category_id])) {
                        $success = 'Categoría eliminada exitosamente';
                    } else {
                        $error = 'Error al eliminar la categoría';
                    }
                }
                break;
                
            case 'bulk_action':
                $category_ids = $_POST['category_ids'] ?? [];
                $bulk_action = $_POST['bulk_action'];
                
                if (!empty($category_ids)) {
                    $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
                    
                    switch ($bulk_action) {
                        case 'delete':
                            // Verificar que no haya productos en las categorías
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id IN ($placeholders)");
                            $stmt->execute($category_ids);
                            $product_count = $stmt->fetchColumn();
                            
                            if ($product_count > 0) {
                                $error = "No se pueden eliminar las categorías porque tienen productos asociados";
                            } else {
                                $stmt = $conn->prepare("DELETE FROM categories WHERE id IN ($placeholders)");
                                if ($stmt->execute($category_ids)) {
                                    $success = count($category_ids) . ' categorías eliminadas';
                                }
                            }
                            break;
                        case 'activate':
                            $stmt = $conn->prepare("UPDATE categories SET is_active = 1 WHERE id IN ($placeholders)");
                            $stmt->execute($category_ids);
                            $success = count($category_ids) . ' categorías activadas';
                            break;
                        case 'deactivate':
                            $stmt = $conn->prepare("UPDATE categories SET is_active = 0 WHERE id IN ($placeholders)");
                            $stmt->execute($category_ids);
                            $success = count($category_ids) . ' categorías desactivadas';
                            break;
                    }
                }
                break;
        }
    }
}

// Obtener categorías con paginación
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
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== '') {
    $where_conditions[] = "is_active = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
        FROM categories c 
        $where_clause 
        ORDER BY $sort_by $sort_order 
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Contar total de categorías para paginación
$count_sql = "SELECT COUNT(*) FROM categories c $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_categories = $count_stmt->fetchColumn();
$total_pages = ceil($total_categories / $limit);

// Obtener estadísticas
$total_categories_count = $conn->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$active_categories_count = $conn->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
$categories_with_products = $conn->query("SELECT COUNT(DISTINCT category_id) FROM products WHERE category_id IS NOT NULL")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <h1><i class="fas fa-tags"></i> Gestión de Categorías</h1>
                    <p>Administra las categorías de productos de tu tienda</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="showAddCategoryModal()">
                        <i class="fas fa-plus"></i> Nueva Categoría
                    </button>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($total_categories_count); ?></h3>
                            <p>Total Categorías</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($active_categories_count); ?></h3>
                            <p>Categorías Activas</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($categories_with_products); ?></h3>
                            <p>Con Productos</p>
                        </div>
                    </div>
                </div>

                <!-- Filtros y Búsqueda -->
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Buscar categorías..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <select name="status">
                                <option value="">Todos los estados</option>
                                <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Activas</option>
                                <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactivas</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select name="sort">
                                <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?>>Fecha de Creación</option>
                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Nombre</option>
                                <option value="product_count" <?php echo $sort_by == 'product_count' ? 'selected' : ''; ?>>Cantidad de Productos</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select name="order">
                                <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descendente</option>
                                <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascendente</option>
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

                <!-- Lista de Categorías -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Categorías (<?php echo number_format($total_categories); ?>)</h3>
                        <div class="bulk-actions">
                            <select id="bulkAction">
                                <option value="">Acciones en lote</option>
                                <option value="delete">Eliminar seleccionadas</option>
                                <option value="activate">Activar seleccionadas</option>
                                <option value="deactivate">Desactivar seleccionadas</option>
                            </select>
                            <button class="btn btn-danger" onclick="executeBulkAction()">Aplicar</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Productos</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="7" class="no-data">No se encontraron categorías</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="category_ids[]" value="<?php echo $category['id']; ?>" class="category-checkbox">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars(substr($category['description'], 0, 100)) . (strlen($category['description']) > 100 ? '...' : ''); ?>
                                            </td>
                                            <td>
                                                <span class="product-count">
                                                    <?php echo $category['product_count']; ?> productos
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status <?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $category['is_active'] ? 'Activa' : 'Inactiva'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['description']); ?>', <?php echo $category['is_active']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" <?php echo $category['product_count'] > 0 ? 'disabled' : ''; ?>>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>" class="page-link">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Agregar/Editar Categoría -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Categoría</h2>
                <span class="close" onclick="closeCategoryModal()">&times;</span>
            </div>
            <form id="categoryForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add_category">
                <input type="hidden" name="category_id" id="categoryId">
                
                <div class="form-group">
                    <label for="name">Nombre de la Categoría *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_active" name="is_active" checked>
                        <span class="checkmark"></span>
                        Categoría activa
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        // Funciones específicas para categorías
        function showAddCategoryModal() {
            document.getElementById('modalTitle').textContent = 'Nueva Categoría';
            document.getElementById('formAction').value = 'add_category';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            document.getElementById('is_active').checked = true;
            document.getElementById('categoryModal').style.display = 'block';
        }

        function editCategory(categoryId, name, description, isActive) {
            document.getElementById('modalTitle').textContent = 'Editar Categoría';
            document.getElementById('formAction').value = 'update_category';
            document.getElementById('categoryId').value = categoryId;
            document.getElementById('name').value = name;
            document.getElementById('description').value = description;
            document.getElementById('is_active').checked = isActive == 1;
            document.getElementById('categoryModal').style.display = 'block';
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        function deleteCategory(categoryId, categoryName) {
            if (confirmAction(`¿Estás seguro de que quieres eliminar la categoría "${categoryName}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_category">
                    <input type="hidden" name="category_id" value="${categoryId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.category-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.category-checkbox:checked');
            const bulkActionBtn = document.querySelector('.bulk-actions .btn');
            bulkActionBtn.disabled = checkboxes.length === 0;
        }

        function executeBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const checkboxes = document.querySelectorAll('.category-checkbox:checked');
            
            if (!action) {
                alert('Selecciona una acción');
                return;
            }
            
            if (checkboxes.length === 0) {
                alert('Selecciona al menos una categoría');
                return;
            }
            
            if (confirmAction(`¿Estás seguro de que quieres ${action} ${checkboxes.length} categorías?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_action">
                    <input type="hidden" name="bulk_action" value="${action}">
                    ${Array.from(checkboxes).map(cb => `<input type="hidden" name="category_ids[]" value="${cb.value}">`).join('')}
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar bulk actions cuando cambien los checkboxes
            document.querySelectorAll('.category-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActions);
            });
        });

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target === modal) {
                closeCategoryModal();
            }
        };
    </script>
</body>
</html>
