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
            case 'add_promotion':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $old_price = floatval($_POST['old_price']);
                $new_price = floatval($_POST['new_price']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Calcular descuentos
                $discount_percent = 0;
                $discount_amount = 0;
                if ($old_price > 0) {
                    $discount_amount = $old_price - $new_price;
                    $discount_percent = ($discount_amount / $old_price) * 100;
                }
                
                // Procesar imagen
                $image_path = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../assets/images/promotions/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                            $error = 'La imagen es demasiado grande. Máximo 5MB permitido.';
                        } else {
                            $image_name = uniqid() . '.' . $file_extension;
                            $image_path = 'assets/images/promotions/' . $image_name;
                            
                            if (move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image_path)) {
                                // Imagen subida exitosamente
                            } else {
                                $error = 'Error al subir la imagen';
                            }
                        }
                    } else {
                        $error = 'Formato de imagen no válido';
                    }
                }
                
                // Validaciones
                if (empty($title)) {
                    $error = 'El título es obligatorio';
                } elseif ($new_price <= 0) {
                    $error = 'El precio nuevo debe ser mayor a 0';
                } elseif ($new_price >= $old_price) {
                    $error = 'El precio nuevo debe ser menor al precio anterior';
                } elseif (strtotime($start_date) >= strtotime($end_date)) {
                    $error = 'La fecha de inicio debe ser anterior a la fecha de fin';
                }
                
                if (empty($error)) {
                    $stmt = $conn->prepare("INSERT INTO promotions (title, description, image, old_price, new_price, discount_percent, discount_amount, start_date, end_date, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    if ($stmt->execute([$title, $description, $image_path, $old_price, $new_price, $discount_percent, $discount_amount, $start_date, $end_date, $is_active])) {
                        header('Location: promociones.php?success=Promoción agregada exitosamente');
                        exit();
                    } else {
                        $error = 'Error al agregar la promoción';
                    }
                }
                break;
                
            case 'update_promotion':
                $promotion_id = intval($_POST['promotion_id']);
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $old_price = floatval($_POST['old_price']);
                $new_price = floatval($_POST['new_price']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Calcular descuentos
                $discount_percent = 0;
                $discount_amount = 0;
                if ($old_price > 0) {
                    $discount_amount = $old_price - $new_price;
                    $discount_percent = ($discount_amount / $old_price) * 100;
                }
                
                // Obtener imagen actual
                $current_image = '';
                $stmt = $conn->prepare("SELECT image FROM promotions WHERE id = ?");
                $stmt->execute([$promotion_id]);
                $current_promotion = $stmt->fetch();
                if ($current_promotion) {
                    $current_image = $current_promotion['image'];
                }
                
                // Procesar nueva imagen
                $image_path = $current_image;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = '../assets/images/promotions/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                            $error = 'La imagen es demasiado grande. Máximo 5MB permitido.';
                        } else {
                            $image_name = uniqid() . '.' . $file_extension;
                            $image_path = 'assets/images/promotions/' . $image_name;
                            
                            if (move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image_path)) {
                                if ($current_image && $current_image !== $image_path && file_exists('../' . $current_image)) {
                                    unlink('../' . $current_image);
                                }
                            } else {
                                $error = 'Error al subir la imagen';
                            }
                        }
                    } else {
                        $error = 'Formato de imagen no válido';
                    }
                }
                
                // Validaciones
                if (empty($title)) {
                    $error = 'El título es obligatorio';
                } elseif ($new_price <= 0) {
                    $error = 'El precio nuevo debe ser mayor a 0';
                } elseif ($new_price >= $old_price) {
                    $error = 'El precio nuevo debe ser menor al precio anterior';
                } elseif (strtotime($start_date) >= strtotime($end_date)) {
                    $error = 'La fecha de inicio debe ser anterior a la fecha de fin';
                }
                
                if (empty($error)) {
                    $stmt = $conn->prepare("UPDATE promotions SET title = ?, description = ?, image = ?, old_price = ?, new_price = ?, discount_percent = ?, discount_amount = ?, start_date = ?, end_date = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$title, $description, $image_path, $old_price, $new_price, $discount_percent, $discount_amount, $start_date, $end_date, $is_active, $promotion_id])) {
                        header('Location: promociones.php?success=Promoción actualizada exitosamente');
                        exit();
                    } else {
                        $error = 'Error al actualizar la promoción';
                    }
                }
                break;
                
            case 'delete_promotion':
                $promotion_id = intval($_POST['promotion_id']);
                
                // Obtener imagen antes de eliminar
                $stmt = $conn->prepare("SELECT image FROM promotions WHERE id = ?");
                $stmt->execute([$promotion_id]);
                $promotion = $stmt->fetch();
                
                $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
                if ($stmt->execute([$promotion_id])) {
                    if ($promotion && $promotion['image'] && file_exists('../' . $promotion['image'])) {
                        unlink('../' . $promotion['image']);
                    }
                    header('Location: promociones.php?success=Promoción eliminada exitosamente');
                    exit();
                } else {
                    $error = 'Error al eliminar la promoción';
                }
                break;
        }
    }
}

// Obtener promociones con paginación
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
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== '') {
    $where_conditions[] = "is_active = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT * FROM promotions 
        $where_clause 
        ORDER BY $sort_by $sort_order 
        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$promotions = $stmt->fetchAll();

// Contar total para paginación
$count_sql = "SELECT COUNT(*) FROM promotions $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_promotions = $count_stmt->fetchColumn();
$total_pages = ceil($total_promotions / $limit);

// Estadísticas
$total_promotions_count = $conn->query("SELECT COUNT(*) FROM promotions")->fetchColumn();
$active_promotions_count = $conn->query("SELECT COUNT(*) FROM promotions WHERE is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()")->fetchColumn();

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
    <title>Gestión de Promociones - Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="content-header">
                <div class="header-content">
                    <h1><i class="fas fa-percentage"></i> Gestión de Promociones</h1>
                    <p>Administra las promociones y ofertas especiales</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="showAddPromotionModal()">
                        <i class="fas fa-plus"></i> Nueva Promoción
                    </button>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($total_promotions_count); ?></h3>
                            <p>Total Promociones</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($active_promotions_count); ?></h3>
                            <p>Promociones Activas</p>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Buscar promociones..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <select name="status">
                                <option value="">Todas</option>
                                <option value="1" <?php echo $status_filter == '1' ? 'selected' : ''; ?>>Activas</option>
                                <option value="0" <?php echo $status_filter == '0' ? 'selected' : ''; ?>>Inactivas</option>
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

                <!-- Lista de Promociones -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Promociones (<?php echo number_format($total_promotions); ?>)</h3>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Título</th>
                                    <th>Precio Anterior</th>
                                    <th>Precio Nuevo</th>
                                    <th>Descuento</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($promotions)): ?>
                                    <tr>
                                        <td colspan="9" class="no-data">No se encontraron promociones</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($promotions as $promotion): ?>
                                        <tr>
                                            <td>
                                                <?php if ($promotion['image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($promotion['image']); ?>" alt="<?php echo htmlspecialchars($promotion['title']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                                <?php else: ?>
                                                    <div class="no-image" style="width: 60px; height: 60px;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($promotion['title']); ?></strong>
                                                <?php if ($promotion['description']): ?>
                                                    <br><small><?php echo htmlspecialchars(substr($promotion['description'], 0, 50)) . (strlen($promotion['description']) > 50 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="price">S/ <?php echo number_format($promotion['old_price'], 2); ?></span></td>
                                            <td><span class="price" style="color: #e74c3c; font-weight: bold;">S/ <?php echo number_format($promotion['new_price'], 2); ?></span></td>
                                            <td>
                                                <span style="color: #2ecc71; font-weight: bold;">
                                                    -<?php echo number_format($promotion['discount_percent'], 0); ?>%
                                                </span>
                                                <br><small style="color: #666;">S/ <?php echo number_format($promotion['discount_amount'], 2); ?></small>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($promotion['start_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($promotion['end_date'])); ?></td>
                                            <td>
                                                <?php
                                                $now = date('Y-m-d');
                                                $is_currently_active = ($promotion['is_active'] == 1 && $promotion['start_date'] <= $now && $promotion['end_date'] >= $now);
                                                ?>
                                                <span class="featured <?php echo $is_currently_active ? 'yes' : 'no'; ?>">
                                                    <?php echo $is_currently_active ? 'Activa' : ($promotion['is_active'] ? 'Programada' : 'Inactiva'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="editPromotion(<?php echo $promotion['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deletePromotion(<?php echo $promotion['id']; ?>, '<?php echo htmlspecialchars($promotion['title']); ?>')">
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

    <!-- Modal Agregar/Editar Promoción -->
    <div id="promotionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Promoción</h2>
                <span class="close" onclick="closePromotionModal()">&times;</span>
            </div>
            <form id="promotionForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add_promotion">
                <input type="hidden" name="promotion_id" id="promotionId">
                
                <div class="form-group full-width">
                    <label for="title">Título de la Promoción *</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="old_price">Precio Anterior *</label>
                        <input type="number" id="old_price" name="old_price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="new_price">Precio Nuevo *</label>
                        <input type="number" id="new_price" name="new_price" step="0.01" min="0" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Fecha de Inicio *</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">Fecha de Fin *</label>
                        <input type="date" id="end_date" name="end_date" required>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="image">Imagen de la Promoción</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <div id="imagePreview" class="image-preview"></div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_active" name="is_active" checked>
                        <span class="checkmark"></span>
                        Promoción activa
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closePromotionModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Promoción</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        function showAddPromotionModal() {
            document.getElementById('modalTitle').textContent = 'Nueva Promoción';
            document.getElementById('formAction').value = 'add_promotion';
            document.getElementById('promotionForm').reset();
            document.getElementById('promotionId').value = '';
            document.getElementById('imagePreview').innerHTML = '';
            document.getElementById('is_active').checked = true;
            const modal = document.getElementById('promotionModal');
            modal.classList.add('show');
            modal.style.display = 'flex';
        }

        function editPromotion(promotionId) {
            fetch(`get_promotion.php?id=${promotionId}`)
                .then(response => response.json())
                .then(promotion => {
                    if (promotion.success) {
                        const data = promotion.data;
                        document.getElementById('modalTitle').textContent = 'Editar Promoción';
                        document.getElementById('formAction').value = 'update_promotion';
                        document.getElementById('promotionId').value = data.id;
                        document.getElementById('title').value = data.title;
                        document.getElementById('description').value = data.description || '';
                        document.getElementById('old_price').value = data.old_price;
                        document.getElementById('new_price').value = data.new_price;
                        document.getElementById('start_date').value = data.start_date;
                        document.getElementById('end_date').value = data.end_date;
                        document.getElementById('is_active').checked = data.is_active == 1;
                        
                        if (data.image) {
                            document.getElementById('imagePreview').innerHTML = 
                                `<img src="../${data.image}" alt="Imagen actual" style="max-width: 200px; max-height: 200px;">
                                 <p><small>Imagen actual</small></p>`;
                        } else {
                            document.getElementById('imagePreview').innerHTML = '';
                        }
                        
                        const modal = document.getElementById('promotionModal');
                        modal.classList.add('show');
                        modal.style.display = 'flex';
                    } else {
                        alert('Error al cargar la promoción: ' + promotion.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar la promoción');
                });
        }

        function closePromotionModal() {
            const modal = document.getElementById('promotionModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
        }

        function deletePromotion(promotionId, promotionTitle) {
            if (confirm(`¿Estás seguro de que quieres eliminar la promoción "${promotionTitle}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_promotion">
                    <input type="hidden" name="promotion_id" value="${promotionId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Preview de imagen
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
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

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('promotionModal');
            if (event.target === modal || event.target.classList.contains('close')) {
                closePromotionModal();
            }
        };
    </script>
</body>
</html>

