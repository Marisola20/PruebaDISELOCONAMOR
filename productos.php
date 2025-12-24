<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Obtener parámetros de filtrado y paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';

// Construir consulta con filtros
$where_conditions = ["p.is_active = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($price_min)) {
    $where_conditions[] = "p.price >= ?";
    $params[] = floatval($price_min);
}

if (!empty($price_max)) {
    $where_conditions[] = "p.price <= ?";
    $params[] = floatval($price_max);
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Consulta principal de productos
$sql = "SELECT p.*, c.name as category_name, 
        (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id) as avg_rating,
        (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) as review_count
        FROM products p 
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

// Obtener categorías para filtros
$categories = getAllCategories($conn);

// Obtener estadísticas de precios
$price_stats = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1")->fetch();
$min_price = $price_stats['min_price'] ?? 0;
$max_price = $price_stats['max_price'] ?? 1000;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos - DCA Perú</title>
    <meta name="description" content="Explora nuestro catálogo completo de productos de calidad. Encuentra lo que buscas con nuestros filtros avanzados y opciones de ordenamiento.">
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
                    <a href="productos.php" class="nav-link active">Productos</a>
                    <a href="#contacto" class="nav-link">Contacto</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="perfil.php" class="nav-link">Mi Perfil</a>
                        <a href="carrito.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count" id="cartCount">0</span>
                        </a>
                        <a href="logout.php" class="nav-link">Cerrar Sesión</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">Iniciar Sesión</a>
                        <a href="register.php" class="nav-link">Registrarse</a>
                    <?php endif; ?>
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
                <h1>Nuestro Catálogo</h1>
                <p>Descubre nuestra amplia selección de productos de calidad</p>
            </div>
        </div>
    </section>

    <!-- Filtros y Búsqueda -->
    <section class="filters-section">
        <div class="container">
            <div class="filters-container">
                <div class="search-box">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Buscar productos..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <div class="filters-toggle">
                    <button class="btn btn-secondary" onclick="toggleFilters()">
                        <i class="fas fa-filter"></i> Filtros
                    </button>
                </div>
            </div>
            
            <div class="filters-panel" id="filtersPanel">
                <form method="GET" class="filters-form">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    
                    <div class="filter-group">
                        <label for="category">Categoría</label>
                        <select name="category" id="category">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort">Ordenar por</label>
                        <select name="sort" id="sort">
                            <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?>>Más recientes</option>
                            <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Nombre A-Z</option>
                            <option value="price" <?php echo $sort_by == 'price' ? 'selected' : ''; ?>>Precio</option>
                            <option value="avg_rating" <?php echo $sort_by == 'avg_rating' ? 'selected' : ''; ?>>Mejor valorados</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="order">Orden</label>
                        <select name="order" id="order">
                            <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descendente</option>
                            <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascendente</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Rango de Precio</label>
                        <div class="price-range">
                            <input type="number" name="price_min" placeholder="Mín" value="<?php echo htmlspecialchars($price_min); ?>" min="0" step="0.01">
                            <span>-</span>
                            <input type="number" name="price_max" placeholder="Máx" value="<?php echo htmlspecialchars($price_max); ?>" min="0" step="0.01">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Aplicar Filtros
                        </button>
                        <a href="productos.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Resultados -->
    <section class="products-section">
        <div class="container">
            <div class="results-header">
                <div class="results-info">
                    <h2>Productos encontrados</h2>
                    <p><?php echo number_format($total_products); ?> productos</p>
                </div>
                <div class="view-options">
                    <button class="view-btn active" data-view="grid" onclick="changeView('grid')">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-btn" data-view="list" onclick="changeView('list')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>No se encontraron productos</h3>
                    <p>Intenta ajustar los filtros o realizar una nueva búsqueda</p>
                    <a href="productos.php" class="btn btn-primary">Ver todos los productos</a>
                </div>
            <?php else: ?>
                <div class="products-grid" id="productsContainer">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-view="grid">
                            <div class="product-image">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($product['is_featured']): ?>
                                    <div class="featured-badge">
                                        <i class="fas fa-star"></i> Destacado
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <button class="action-btn quick-view" onclick="quickView(<?php echo $product['id']; ?>)" title="Vista rápida">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>)" title="Agregar al carrito">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                    <button class="action-btn add-to-favorites" onclick="toggleFavorite(<?php echo $product['id']; ?>)" title="Agregar a favoritos">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <div class="product-category">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Sin categoría'); ?>
                                </div>
                                
                                <h3 class="product-name">
                                    <a href="producto.php?id=<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h3>
                                
                                <div class="product-rating">
                                    <?php if ($product['avg_rating']): ?>
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $product['avg_rating'] ? 'filled' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-text">(<?php echo $product['review_count']; ?> reseñas)</span>
                                    <?php else: ?>
                                        <span class="no-rating">Sin reseñas</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-price">
                                    <span class="current-price">S/ <?php echo number_format($product['price'], 2); ?></span>
                                </div>
                                
                                <div class="product-stock">
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="in-stock">
                                            <i class="fas fa-check"></i> En stock (<?php echo $product['stock']; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="out-of-stock">
                                            <i class="fas fa-times"></i> Sin stock
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&price_min=<?php echo urlencode($price_min); ?>&price_max=<?php echo urlencode($price_max); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&price_min=<?php echo urlencode($price_min); ?>&price_max=<?php echo urlencode($price_max); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&price_min=<?php echo urlencode($price_min); ?>&price_max=<?php echo urlencode($price_max); ?>" class="page-link">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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

    <!-- Modal de Vista Rápida -->
    <div id="quickViewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeQuickView()">&times;</span>
            <div id="quickViewContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Funciones específicas para la página de productos
        function toggleFilters() {
            const panel = document.getElementById('filtersPanel');
            panel.classList.toggle('active');
        }

        function changeView(viewType) {
            const container = document.getElementById('productsContainer');
            const viewBtns = document.querySelectorAll('.view-btn');
            
            // Actualizar botones
            viewBtns.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.view === viewType) {
                    btn.classList.add('active');
                }
            });
            
            // Cambiar vista
            container.dataset.view = viewType;
            
            // Guardar preferencia
            localStorage.setItem('productsView', viewType);
        }

        // Cargar vista preferida
        document.addEventListener('DOMContentLoaded', function() {
            const savedView = localStorage.getItem('productsView') || 'grid';
            changeView(savedView);
            
            // Actualizar contador del carrito
            updateCartCount();
        });

        // Cerrar filtros al hacer clic fuera
        document.addEventListener('click', function(e) {
            const filtersSection = document.querySelector('.filters-section');
            if (!filtersSection.contains(e.target)) {
                document.getElementById('filtersPanel').classList.remove('active');
            }
        });

        // Filtros automáticos
        document.getElementById('category').addEventListener('change', function() {
            this.form.submit();
        });

        document.getElementById('sort').addEventListener('change', function() {
            this.form.submit();
        });

        document.getElementById('order').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>
