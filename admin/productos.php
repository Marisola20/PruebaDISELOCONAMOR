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
            case 'add_product':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                $stock = intval($_POST['stock']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                
                // Procesar imagen
                $image_path = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../assets/images/products/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        // Verificar tamaño máximo (5MB)
                        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                            $error = 'La imagen es demasiado grande. Máximo 5MB permitido.';
                        } else {
                            $image_name = uniqid() . '.' . $file_extension;
                            $image_path = 'assets/images/products/' . $image_name;
                            
                            if (move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image_path)) {
                                // Imagen subida exitosamente
                            } else {
                                $error = 'Error al subir la imagen';
                            }
                        }
                    } else {
                        $error = 'Formato de imagen no válido. Solo se permiten: ' . implode(', ', $allowed_extensions);
                    }
                }
                
                // Validaciones adicionales
                if (empty($name)) {
                    $error = 'El nombre del producto es obligatorio';
                } elseif (strlen($name) < 3) {
                    $error = 'El nombre del producto debe tener al menos 3 caracteres';
                } elseif ($price <= 0) {
                    $error = 'El precio debe ser mayor a 0';
                } elseif ($stock < 0) {
                    $error = 'El stock no puede ser negativo';
                } elseif ($category_id <= 0) {
                    $error = 'Debe seleccionar una categoría';
                }
                
                if (empty($error)) {
                    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, stock, image, is_featured, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    if ($stmt->execute([$name, $description, $price, $category_id, $stock, $image_path, $is_featured])) {
                        header('Location: productos.php?success=Producto agregado exitosamente');
                        exit();
                    } else {
                        $error = 'Error al agregar el producto';
                    }
                }
                break;
                
            case 'update_product':
                $product_id = intval($_POST['product_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                $stock = intval($_POST['stock']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                
                // Obtener imagen actual
                $current_image = '';
                $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $current_product = $stmt->fetch();
                if ($current_product) {
                    $current_image = $current_product['image'];
                }
                
                // Procesar nueva imagen si se subió
                $image_path = $current_image; // Mantener imagen actual por defecto
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../assets/images/products/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $image_name = uniqid() . '.' . $file_extension;
                        $image_path = 'assets/images/products/' . $image_name;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image_path)) {
                            // Imagen subida exitosamente
                            // Eliminar imagen anterior si existe y es diferente
                            if ($current_image && $current_image !== $image_path && file_exists('../' . $current_image)) {
                                unlink('../' . $current_image);
                            }
                        } else {
                            $error = 'Error al subir la imagen';
                        }
                    } else {
                        $error = 'Formato de imagen no válido';
                    }
                }
                
                // Validaciones adicionales
                if (empty($name)) {
                    $error = 'El nombre del producto es obligatorio';
                } elseif (strlen($name) < 3) {
                    $error = 'El nombre del producto debe tener al menos 3 caracteres';
                } elseif ($price <= 0) {
                    $error = 'El precio debe ser mayor a 0';
                } elseif ($stock < 0) {
                    $error = 'El stock no puede ser negativo';
                } elseif ($category_id <= 0) {
                    $error = 'Debe seleccionar una categoría';
                }
                
                if (empty($error)) {
                    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock = ?, image = ?, is_featured = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$name, $description, $price, $category_id, $stock, $image_path, $is_featured, $product_id])) {
                        header('Location: productos.php?success=Producto actualizado exitosamente');
                        exit();
                    } else {
                        $error = 'Error al actualizar el producto';
                    }
                }
                break;
                
            case 'delete_product':
                $product_id = intval($_POST['product_id']);
                
                // Obtener imagen antes de eliminar
                $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                // Eliminar producto de la base de datos
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                if ($stmt->execute([$product_id])) {
                    // Eliminar imagen del servidor si existe
                    if ($product && $product['image'] && file_exists('../' . $product['image'])) {
                        unlink('../' . $product['image']);
                    }
                    header('Location: productos.php?success=Producto eliminado exitosamente');
                    exit();
                } else {
                    $error = 'Error al eliminar el producto';
                }
                break;
                
            case 'bulk_action':
                $product_ids = $_POST['product_ids'] ?? [];
                $bulk_action = $_POST['bulk_action'];
                
                if (!empty($product_ids)) {
                    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                    
                    switch ($bulk_action) {
                        case 'delete':
                            $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                            if ($stmt->execute($product_ids)) {
                                $success = count($product_ids) . ' productos eliminados';
                            }
                            break;
                        case 'feature':
                            $stmt = $conn->prepare("UPDATE products SET is_featured = 1 WHERE id IN ($placeholders)");
                            $stmt->execute($product_ids);
                            $success = count($product_ids) . ' productos marcados como destacados';
                            break;
                        case 'unfeature':
                            $stmt = $conn->prepare("UPDATE products SET is_featured = 0 WHERE id IN ($placeholders)");
                            $stmt->execute($product_ids);
                            $success = count($product_ids) . ' productos desmarcados como destacados';
                            break;
                    }
                }
                break;
        }
    }
}

// Obtener productos con paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "category_id = ?";
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where_clause 
        ORDER BY $sort_by $sort_order 
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Contar total de productos para paginación
$count_sql = "SELECT COUNT(*) FROM products p $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Mostrar mensajes de éxito/error desde URL
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Obtener categorías para filtros
$categories = getAllCategories($conn);

// Obtener estadísticas
$total_products_count = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$featured_products_count = $conn->query("SELECT COUNT(*) FROM products WHERE is_featured = 1")->fetchColumn();
$low_stock_products = $conn->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <h1><i class="fas fa-box"></i> Gestión de Productos</h1>
                    <p>Administra el catálogo de productos de tu tienda</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus"></i> Nuevo Producto
                    </button>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($total_products_count); ?></h3>
                            <p>Total Productos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($featured_products_count); ?></h3>
                            <p>Productos Destacados</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($low_stock_products); ?></h3>
                            <p>Stock Bajo</p>
                        </div>
                    </div>
                </div>

                <!-- Filtros y Búsqueda -->
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Buscar productos..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <select name="category">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select name="sort">
                                <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?>>Fecha de Creación</option>
                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Nombre</option>
                                <option value="price" <?php echo $sort_by == 'price' ? 'selected' : ''; ?>>Precio</option>
                                <option value="stock" <?php echo $sort_by == 'stock' ? 'selected' : ''; ?>>Stock</option>
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

                <!-- Lista de Productos -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Productos (<?php echo number_format($total_products); ?>)</h3>
                        <div class="bulk-actions">
                            <select id="bulkAction">
                                <option value="">Acciones en lote</option>
                                <option value="delete">Eliminar seleccionados</option>
                                <option value="feature">Marcar como destacados</option>
                                <option value="unfeature">Desmarcar como destacados</option>
                            </select>
                            <button class="btn btn-danger" onclick="executeBulkAction()">Aplicar</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Destacado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="9" class="no-data">No se encontraron productos</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>" class="product-checkbox">
                                            </td>
                                            <td>
                                                <div class="product-image">
                                                    <?php if ($product['image']): ?>
                                                        <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                    <?php else: ?>
                                                        <div class="no-image">
                                                            <i class="fas fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="product-info">
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <small><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Sin categoría'); ?></td>
                                            <td>
                                                <span class="price">S/ <?php echo number_format($product['price'], 2); ?></span>
                                            </td>
                                            <td>
                                                <span class="stock <?php echo $product['stock'] < 10 ? 'low-stock' : ''; ?>">
                                                    <?php echo $product['stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="featured <?php echo $product['is_featured'] ? 'yes' : 'no'; ?>">
                                                    <?php echo $product['is_featured'] ? 'Sí' : 'No'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>" class="page-link">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Agregar/Editar Producto -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nuevo Producto</h2>
                <span class="close" onclick="closeProductModal()">&times;</span>
            </div>
            <form id="productForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add_product">
                <input type="hidden" name="product_id" id="productId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nombre del Producto *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Categoría *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Seleccionar categoría</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Precio *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock *</label>
                        <input type="number" id="stock" name="stock" min="0" required>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-group full-width">
                    <label for="image">Imagen del Producto</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <div id="imagePreview" class="image-preview"></div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_featured" name="is_featured">
                        <span class="checkmark"></span>
                        Producto destacado
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        // Funciones específicas para productos
        function showAddProductModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Producto';
            document.getElementById('formAction').value = 'add_product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('imagePreview').innerHTML = '';
            const modal = document.getElementById('productModal');
            modal.classList.add('show');
            modal.style.display = 'flex';
        }

        function editProduct(productId) {
            // Cargar datos del producto via AJAX
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(product => {
                    if (product.success) {
                        const data = product.data;
                        document.getElementById('modalTitle').textContent = 'Editar Producto';
                        document.getElementById('formAction').value = 'update_product';
                        document.getElementById('productId').value = data.id;
                        document.getElementById('name').value = data.name;
                        document.getElementById('description').value = data.description;
                        document.getElementById('price').value = data.price;
                        document.getElementById('category_id').value = data.category_id;
                        document.getElementById('stock').value = data.stock;
                        document.getElementById('is_featured').checked = data.is_featured == 1;
                        
                        // Mostrar imagen actual si existe
                        if (data.image) {
                            document.getElementById('imagePreview').innerHTML = 
                                `<img src="../${data.image}" alt="Imagen actual" style="max-width: 200px; max-height: 200px;">
                                 <p><small>Imagen actual: ${data.image}</small></p>`;
                        } else {
                            document.getElementById('imagePreview').innerHTML = '';
                        }
                        
                        const modal = document.getElementById('productModal');
                        modal.classList.add('show');
                        modal.style.display = 'flex';
                    } else {
                        alert('Error al cargar el producto: ' + product.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el producto');
                });
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }

        function deleteProduct(productId, productName) {
            if (confirm(`¿Estás seguro de que quieres eliminar el producto "${productName}"?\n\n⚠️ Esta acción no se puede deshacer y también eliminará la imagen asociada.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_product">
                    <input type="hidden" name="product_id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const bulkActionBtn = document.querySelector('.bulk-actions .btn');
            bulkActionBtn.disabled = checkboxes.length === 0;
        }

        function executeBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            
            if (!action) {
                alert('Selecciona una acción');
                return;
            }
            
            if (checkboxes.length === 0) {
                alert('Selecciona al menos un producto');
                return;
            }
            
            if (confirm(`¿Estás seguro de que quieres ${action} ${checkboxes.length} productos?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_action">
                    <input type="hidden" name="bulk_action" value="${action}">
                    ${Array.from(checkboxes).map(cb => `<input type="hidden" name="product_ids[]" value="${cb.value}">`).join('')}
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar bulk actions cuando cambien los checkboxes
            document.querySelectorAll('.product-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActions);
            });

            // Preview de imagen
            document.getElementById('image').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('imagePreview');
                
                if (file) {
                    // Verificar tamaño
                    if (file.size > 5 * 1024 * 1024) {
                        alert('La imagen es demasiado grande. Máximo 5MB permitido.');
                        this.value = '';
                        preview.innerHTML = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = '';
                }
            });
            
            // Manejar envío del formulario
            document.getElementById('productForm').addEventListener('submit', function(e) {
                // Validación del lado del cliente
                const name = document.getElementById('name').value.trim();
                const price = parseFloat(document.getElementById('price').value);
                const stock = parseInt(document.getElementById('stock').value);
                const category = document.getElementById('category_id').value;
                
                if (name.length < 3) {
                    e.preventDefault();
                    alert('El nombre del producto debe tener al menos 3 caracteres');
                    return false;
                }
                
                if (price <= 0) {
                    e.preventDefault();
                    alert('El precio debe ser mayor a 0');
                    return false;
                }
                
                if (stock < 0) {
                    e.preventDefault();
                    alert('El stock no puede ser negativo');
                    return false;
                }
                
                if (!category) {
                    e.preventDefault();
                    alert('Debe seleccionar una categoría');
                    return false;
                }
            });
        });

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal || event.target.classList.contains('close')) {
                closeProductModal();
            }
        };
    </script>
</body>
</html>
