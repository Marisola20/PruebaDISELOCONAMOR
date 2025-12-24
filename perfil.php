<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                
                // Verificar si el email ya existe en otro usuario
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->rowCount() > 0) {
                    $error = 'El email ya está registrado por otro usuario';
                } else {
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$full_name, $email, $phone, $address, $user_id])) {
                        $success = 'Perfil actualizado exitosamente';
                        $_SESSION['full_name'] = $full_name;
                    } else {
                        $error = 'Error al actualizar el perfil';
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Verificar contraseña actual
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'La contraseña actual es incorrecta';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'Las contraseñas nuevas no coinciden';
                } elseif (strlen($new_password) < 6) {
                    $error = 'La nueva contraseña debe tener al menos 6 caracteres';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        $success = 'Contraseña cambiada exitosamente';
                    } else {
                        $error = 'Error al cambiar la contraseña';
                    }
                }
                break;
        }
    }
}

// Obtener información del usuario
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Obtener pedidos del usuario
$orders = getUserOrders($conn, $user_id);

// Obtener productos favoritos
$favorites = getUserFavorites($conn, $user_id);

// Obtener estadísticas
$total_orders = count($orders);
$total_spent = array_sum(array_column($orders, 'total_amount'));
$favorite_count = count($favorites);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - DCA Perú</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <div class="container">
                <div class="nav-brand">
                    <a href="index.php">DCA Perú</a>
                </div>
                <div class="nav-menu" id="navMenu">
                    <a href="index.php" class="nav-link">Inicio</a>
                    <a href="productos.php" class="nav-link">Productos</a>
                    <a href="#contacto" class="nav-link">Contacto</a>
                    <a href="perfil.php" class="nav-link active">Mi Perfil</a>
                    <a href="carrito.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cartCount">0</span>
                    </a>
                    <a href="logout.php" class="nav-link">Cerrar Sesión</a>
                </div>
                <div class="nav-toggle" id="navToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero hero-small">
        <div class="container">
            <div class="hero-content">
                <h1>Mi Perfil</h1>
                <p>Gestiona tu información personal y revisa tu actividad</p>
            </div>
        </div>
    </section>

    <!-- Perfil del Usuario -->
    <section class="profile-section">
        <div class="container">
            <!-- Mensajes -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="profile-grid">
                <!-- Información del Perfil -->
                <div class="profile-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Información Personal</h3>
                        <button class="btn btn-sm btn-primary" onclick="editProfile()">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                    <div class="profile-info">
                        <div class="info-item">
                            <label>Nombre completo:</label>
                            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Usuario:</label>
                            <span><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Teléfono:</label>
                            <span><?php echo htmlspecialchars($user['phone'] ?: 'No especificado'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Dirección:</label>
                            <span><?php echo htmlspecialchars($user['address'] ?: 'No especificada'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Miembro desde:</label>
                            <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="profile-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Mis Estadísticas</h3>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-content">
                                <h4><?php echo $total_orders; ?></h4>
                                <p>Pedidos realizados</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content">
                                <h4>S/ <?php echo number_format($total_spent, 2); ?></h4>
                                <p>Total gastado</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="stat-content">
                                <h4><?php echo $favorite_count; ?></h4>
                                <p>Productos favoritos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de Navegación -->
            <div class="profile-tabs">
                <button class="tab-btn active" onclick="showTab('orders')">
                    <i class="fas fa-shopping-bag"></i> Mis Pedidos
                </button>
                <button class="tab-btn" onclick="showTab('favorites')">
                    <i class="fas fa-heart"></i> Favoritos
                </button>
                <button class="tab-btn" onclick="showTab('settings')">
                    <i class="fas fa-cog"></i> Configuración
                </button>
            </div>

            <!-- Contenido de las Tabs -->
            <div class="tab-content">
                <!-- Tab de Pedidos -->
                <div id="ordersTab" class="tab-pane active">
                    <div class="orders-list">
                        <?php if (empty($orders)): ?>
                            <div class="no-data">
                                <i class="fas fa-shopping-bag"></i>
                                <h3>No tienes pedidos aún</h3>
                                <p>¡Haz tu primer pedido y comienza a disfrutar de nuestros productos!</p>
                                <a href="productos.php" class="btn btn-primary">Ver Productos</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <div class="order-item">
                                    <div class="order-header">
                                        <div class="order-info">
                                            <h4>Pedido #<?php echo htmlspecialchars($order['order_code']); ?></h4>
                                            <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                        </div>
                                        <div class="order-status">
                                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="order-details">
                                        <div class="order-amount">
                                            <strong>Total: S/ <?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </div>
                                        <div class="order-actions">
                                            <button class="btn btn-sm btn-primary" onclick="viewOrderDetails('<?php echo $order['order_code']; ?>')">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab de Favoritos -->
                <div id="favoritesTab" class="tab-pane">
                    <div class="favorites-grid">
                        <?php if (empty($favorites)): ?>
                            <div class="no-data">
                                <i class="fas fa-heart"></i>
                                <h3>No tienes productos favoritos</h3>
                                <p>¡Agrega productos a tus favoritos para encontrarlos fácilmente!</p>
                                <a href="productos.php" class="btn btn-primary">Ver Productos</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($favorites as $product): ?>
                                <div class="favorite-item">
                                    <div class="product-image">
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <p class="price">S/ <?php echo number_format($product['price'], 2); ?></p>
                                        <div class="product-actions">
                                            <button class="btn btn-sm btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-shopping-cart"></i> Agregar al Carrito
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="removeFavorite(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-heart-broken"></i> Quitar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab de Configuración -->
                <div id="settingsTab" class="tab-pane">
                    <div class="settings-grid">
                        <!-- Editar Perfil -->
                        <div class="settings-card">
                            <h3><i class="fas fa-user-edit"></i> Editar Perfil</h3>
                            <form method="POST" class="profile-form">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="full_name">Nombre completo *</label>
                                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="phone">Teléfono</label>
                                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="address">Dirección</label>
                                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar Perfil
                                </button>
                            </form>
                        </div>

                        <!-- Cambiar Contraseña -->
                        <div class="settings-card">
                            <h3><i class="fas fa-lock"></i> Cambiar Contraseña</h3>
                            <form method="POST" class="password-form">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label for="current_password">Contraseña actual *</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">Nueva contraseña *</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirmar nueva contraseña *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Cambiar Contraseña
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>DCA Perú</h3>
                    <p>Tu tienda online de confianza con los mejores productos y atención al cliente.</p>
                </div>
                <div class="footer-section">
                    <h3>Enlaces Rápidos</h3>
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="productos.php">Productos</a></li>
                        <li><a href="#contacto">Contacto</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contacto</h3>
                    <p><i class="fas fa-phone"></i> +51 999 999 999</p>
                    <p><i class="fas fa-envelope"></i> info@dcaperu.com</p>
                </div>
                <div class="footer-section">
                    <h3>Síguenos</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 DCA Perú. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Botón flotante de WhatsApp -->
    <div class="whatsapp-float">
        <a href="https://wa.me/51999999999?text=Hola, me gustaría obtener más información sobre sus productos" target="_blank">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Funciones específicas para el perfil
        function showTab(tabName) {
            // Ocultar todas las tabs
            const tabPanes = document.querySelectorAll('.tab-pane');
            const tabBtns = document.querySelectorAll('.tab-btn');
            
            tabPanes.forEach(pane => pane.classList.remove('active'));
            tabBtns.forEach(btn => btn.classList.remove('active'));
            
            // Mostrar la tab seleccionada
            document.getElementById(tabName + 'Tab').classList.add('active');
            event.target.classList.add('active');
        }

        function editProfile() {
            showTab('settings');
        }

        function viewOrderDetails(orderCode) {
            // Aquí podrías abrir un modal o redirigir a una página de detalles
            alert('Detalles del pedido: ' + orderCode);
        }

        function removeFavorite(productId) {
            if (confirm('¿Estás seguro de que quieres quitar este producto de tus favoritos?')) {
                // Aquí harías la llamada AJAX para quitar de favoritos
                location.reload();
            }
        }

        // Cargar datos iniciales
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>
